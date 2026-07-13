<?php

/**
 * TESTS: AuthTest
 * -------------------------------------------------------------------------
 * Pruebas de integración para los endpoints de autenticación:
 *   POST /api/register  → Registrar nuevo usuario
 *   POST /api/login     → Iniciar sesión y obtener token
 *   POST /api/logout    → Cerrar sesión y revocar token
 *
 * También verifica que las rutas protegidas requieran autenticación.
 *
 * Usa SQLite en memoria (configurado en phpunit.xml).
 *
 * Autor: Backend generado para Daikel Molina - Curso Fullstack UNET
 */

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // HELPERS
    // =========================================================================

    /** Payload válido para registro */
    private function payloadRegistro(array $override = []): array
    {
        return array_merge([
            'name'                  => 'Daikel Molina',
            'email'                 => 'daikel@correo.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ], $override);
    }

    // =========================================================================
    // TESTS: POST /api/register → register()
    // =========================================================================

    /** @test */
    public function test_register_exitoso_crea_usuario_y_devuelve_token(): void
    {
        $response = $this->postJson('/api/register', $this->payloadRegistro());

        // 201 Created con token
        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Usuario registrado exitosamente',
                 ])
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'user'  => ['id', 'name', 'email'],
                         'token',
                     ],
                 ]);

        // Verificar que el usuario quedó guardado en la BD
        $this->assertDatabaseHas('users', [
            'name'  => 'Daikel Molina',
            'email' => 'daikel@correo.com',
        ]);

        // Verificar que el token está presente y no está vacío
        $this->assertNotEmpty($response->json('data.token'));
    }

    /** @test */
    public function test_register_falla_con_email_duplicado(): void
    {
        // Creamos el usuario primero
        User::factory()->create(['email' => 'daikel@correo.com']);

        // Intentamos registrar con el mismo email
        $response = $this->postJson('/api/register', $this->payloadRegistro());

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function test_register_falla_si_passwords_no_coinciden(): void
    {
        $response = $this->postJson('/api/register', $this->payloadRegistro([
            'password'              => 'password123',
            'password_confirmation' => 'diferente456',
        ]));

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function test_register_falla_sin_campos_requeridos(): void
    {
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    // =========================================================================
    // TESTS: POST /api/login → login()
    // =========================================================================

    /** @test */
    public function test_login_exitoso_devuelve_token(): void
    {
        // Crear usuario directamente en BD
        User::factory()->create([
            'email'    => 'daikel@correo.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'daikel@correo.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Sesión iniciada exitosamente',
                 ])
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'user'  => ['id', 'name', 'email'],
                         'token',
                     ],
                 ]);

        // El token debe ser una cadena no vacía
        $this->assertNotEmpty($response->json('data.token'));
    }

    /** @test */
    public function test_login_falla_con_password_incorrecta(): void
    {
        User::factory()->create([
            'email'    => 'daikel@correo.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'daikel@correo.com',
            'password' => 'contrasena_incorrecta',
        ]);

        $response->assertStatus(401)
                 ->assertJson(['success' => false]);
    }

    /** @test */
    public function test_login_falla_con_email_inexistente(): void
    {
        $response = $this->postJson('/api/login', [
            'email'    => 'noexiste@correo.com',
            'password' => 'cualquierpassword',
        ]);

        $response->assertStatus(401)
                 ->assertJson(['success' => false]);
    }

    // =========================================================================
    // TESTS: POST /api/logout → logout()
    // =========================================================================

    /** @test */
    public function test_logout_revoca_el_token_del_usuario(): void
    {
        $user  = User::factory()->create();
        // Crear un token real en la BD para poder revocarlo
        $token = $user->createToken('auth_token')->plainTextToken;

        // Llamamos logout con el token en el header Authorization
        $response = $this->withHeader('Authorization', "Bearer {$token}")
                         ->postJson('/api/logout');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Sesión cerrada exitosamente. Token revocado.',
                 ]);

        // El token debe haber sido eliminado de la BD
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    /** @test */
    public function test_logout_sin_autenticacion_devuelve_401(): void
    {
        // Llamamos logout sin token
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    }

    // =========================================================================
    // TESTS: Rutas protegidas requieren autenticación
    // =========================================================================

    /** @test */
    public function test_ruta_pedidos_sin_token_devuelve_401(): void
    {
        // Sin actingAs ni token — debe rechazarse con 401
        $response = $this->getJson('/api/pedidos');

        $response->assertStatus(401);
    }

    /** @test */
    public function test_crear_pedido_exitoso_con_verificacion_de_stock(): void
    {
        // Crear usuario autenticado
        $user = User::factory()->create();

        // Crear producto con stock
        $producto = \App\Models\Producto::create([
            'nombre' => 'Brasil',
            'precio' => 65.00,
            'imagen' => 'brasil.webp',
            'stock'  => 20,
        ]);

        $payload = [
            'nombre_cliente' => 'Daikel Molina',
            'email'          => 'daikel@correo.com',
            'telefono'       => '0412-1234567',
            'productos'      => [
                ['producto_id' => $producto->id, 'cantidad' => 3],
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
                         ->postJson('/api/pedidos', $payload);

        // Verificar respuesta exitosa
        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Pedido creado exitosamente',
                 ]);

        // Verificar que el pedido está en la BD
        $this->assertDatabaseHas('pedidos', [
            'nombre_cliente' => 'Daikel Molina',
            'email'          => 'daikel@correo.com',
            'estado'         => 'pendiente',
        ]);

        // Verificar decremento de stock: 20 - 3 = 17
        $this->assertEquals(17, \App\Models\Producto::find($producto->id)->stock);
    }
}
