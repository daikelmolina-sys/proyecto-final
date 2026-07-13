<?php

/**
 * TESTS: PedidoApiTest
 * -------------------------------------------------------------------------
 * Pruebas de integración para todos los endpoints de pedidos:
 *   POST /api/pedidos                 → Crear pedido
 *   GET  /api/pedidos                 → Listar pedidos
 *   GET  /api/pedidos/{id}            → Ver detalle de pedido
 *   PUT  /api/pedidos/{id}/estado     → Actualizar estado
 *
 * Usa SQLite en memoria (configurado en phpunit.xml).
 */

namespace Tests\Feature;

use App\Models\Pedido;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PedidoApiTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // HELPERS
    // =========================================================================

    /** Crea un usuario de prueba y retorna la instancia autenticada para Sanctum */
    private function autenticarUsuario(): User
    {
        return User::factory()->create();
    }

    /** Crea un producto de prueba con stock disponible */
    private function crearProducto(array $atributos = []): Producto
    {
        return Producto::create(array_merge([
            'nombre' => 'Argentina',
            'precio' => 80.00,
            'imagen' => 'argentina.webp',
            'stock'  => 50,
        ], $atributos));
    }

    /** Crea un pedido completo con sus detalles */
    private function crearPedidoCompleto(Producto $producto, int $cantidad = 2): Pedido
    {
        $subtotal = $producto->precio * $cantidad;

        $pedido = Pedido::create([
            'nombre_cliente' => 'Cliente Test',
            'email'          => 'test@correo.com',
            'telefono'       => '0412-0000000',
            'total'          => $subtotal,
            'estado'         => 'pendiente',
        ]);

        $pedido->detalles()->create([
            'producto_id'     => $producto->id,
            'cantidad'        => $cantidad,
            'precio_unitario' => $producto->precio,
            'subtotal'        => $subtotal,
        ]);

        $producto->decrement('stock', $cantidad);

        return $pedido;
    }

    /** Payload válido para crear un pedido */
    private function payloadValido(int $productoId, int $cantidad = 2): array
    {
        return [
            'nombre_cliente' => 'Daikel Molina',
            'email'          => 'daikel@correo.com',
            'telefono'       => '0412-1234567',
            'productos'      => [
                ['producto_id' => $productoId, 'cantidad' => $cantidad],
            ],
        ];
    }

    // =========================================================================
    // TESTS: POST /api/pedidos → store()
    // =========================================================================

    /** @test */
    public function crea_un_pedido_exitosamente_con_datos_validos(): void
    {
        $user     = $this->autenticarUsuario();
        $producto = $this->crearProducto(['precio' => 80.00, 'stock' => 50]);

        $response = $this->actingAs($user, 'sanctum')
                         ->postJson('/api/pedidos', $this->payloadValido($producto->id, 2));

        // Código 201 Created
        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Pedido creado exitosamente',
                 ])
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'id', 'nombre_cliente', 'email', 'telefono', 'total', 'estado',
                         'detalles' => [
                             '*' => ['id', 'producto_id', 'cantidad', 'precio_unitario', 'subtotal']
                         ]
                     ]
                 ]);

        // Verificar que el pedido quedó guardado en la BD
        $this->assertDatabaseHas('pedidos', [
            'nombre_cliente' => 'Daikel Molina',
            'email'          => 'daikel@correo.com',
            'estado'         => 'pendiente',
        ]);
    }

    /** @test */
    public function el_total_del_pedido_se_calcula_correctamente(): void
    {
        $user     = $this->autenticarUsuario();
        $producto = $this->crearProducto(['precio' => 80.00, 'stock' => 50]);

        $response = $this->actingAs($user, 'sanctum')
                         ->postJson('/api/pedidos', $this->payloadValido($producto->id, 2));

        $response->assertStatus(201);
        // 80.00 × 2 = 160.00
        $this->assertEquals('160.00', $response->json('data.total'));
    }

    /** @test */
    public function el_stock_se_descuenta_al_crear_el_pedido(): void
    {
        $user     = $this->autenticarUsuario();
        $producto = $this->crearProducto(['stock' => 50]);

        $this->actingAs($user, 'sanctum')
             ->postJson('/api/pedidos', $this->payloadValido($producto->id, 3));

        // Stock debería ser 50 - 3 = 47
        $this->assertDatabaseHas('productos', [
            'id'    => $producto->id,
            'stock' => 47,
        ]);
    }

    /** @test */
    public function crea_pedido_sin_telefono_porque_es_opcional(): void
    {
        $user     = $this->autenticarUsuario();
        $producto = $this->crearProducto();

        $payload = [
            'nombre_cliente' => 'Sin Telefono',
            'email'          => 'sintel@correo.com',
            // Sin teléfono
            'productos' => [
                ['producto_id' => $producto->id, 'cantidad' => 1],
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
                         ->postJson('/api/pedidos', $payload);

        $response->assertStatus(201)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('pedidos', [
            'nombre_cliente' => 'Sin Telefono',
            'telefono'       => null,
        ]);
    }

    /** @test */
    public function crea_pedido_con_multiples_productos(): void
    {
        $user = $this->autenticarUsuario();
        $p1   = $this->crearProducto(['nombre' => 'Argentina', 'precio' => 80.00, 'stock' => 20]);
        $p2   = $this->crearProducto(['nombre' => 'Brasil',    'precio' => 65.00, 'stock' => 20]);

        $payload = [
            'nombre_cliente' => 'Cliente Multi',
            'email'          => 'multi@correo.com',
            'productos'      => [
                ['producto_id' => $p1->id, 'cantidad' => 1],
                ['producto_id' => $p2->id, 'cantidad' => 2],
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
                         ->postJson('/api/pedidos', $payload);

        $response->assertStatus(201);
        // Total = 80×1 + 65×2 = 80 + 130 = 210
        $this->assertEquals('210.00', $response->json('data.total'));
        // Deben existir 2 detalles
        $this->assertCount(2, $response->json('data.detalles'));
    }

    /** @test */
    public function falla_si_no_hay_stock_suficiente(): void
    {
        $user     = $this->autenticarUsuario();
        $producto = $this->crearProducto(['stock' => 1]);

        // Intentamos pedir 5 unidades pero solo hay 1
        $response = $this->actingAs($user, 'sanctum')
                         ->postJson('/api/pedidos', $this->payloadValido($producto->id, 5));

        $response->assertStatus(400)
                 ->assertJson(['success' => false])
                 ->assertJsonFragment(['success' => false]);

        // La BD no debe tener pedidos (transacción revertida)
        $this->assertDatabaseCount('pedidos', 0);
        // El stock no debe haber cambiado
        $this->assertDatabaseHas('productos', ['id' => $producto->id, 'stock' => 1]);
    }

    /** @test */
    public function falla_validacion_sin_nombre_cliente(): void
    {
        $user     = $this->autenticarUsuario();
        $producto = $this->crearProducto();

        $response = $this->actingAs($user, 'sanctum')
                         ->postJson('/api/pedidos', [
            // Sin 'nombre_cliente'
            'email'     => 'test@correo.com',
            'productos' => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ]);

        // 422 Unprocessable Entity (validación fallida)
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['nombre_cliente']);
    }

    /** @test */
    public function falla_validacion_con_email_invalido(): void
    {
        $user     = $this->autenticarUsuario();
        $producto = $this->crearProducto();

        $response = $this->actingAs($user, 'sanctum')
                         ->postJson('/api/pedidos', [
            'nombre_cliente' => 'Test',
            'email'          => 'no-es-un-email',
            'productos'      => [['producto_id' => $producto->id, 'cantidad' => 1]],
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function falla_validacion_sin_productos(): void
    {
        $user = $this->autenticarUsuario();

        $response = $this->actingAs($user, 'sanctum')
                         ->postJson('/api/pedidos', [
            'nombre_cliente' => 'Test',
            'email'          => 'test@correo.com',
            // Sin 'productos'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['productos']);
    }

    /** @test */
    public function falla_validacion_con_cantidad_mayor_a_10(): void
    {
        $user     = $this->autenticarUsuario();
        $producto = $this->crearProducto(['stock' => 100]);

        $response = $this->actingAs($user, 'sanctum')
                         ->postJson('/api/pedidos', [
            'nombre_cliente' => 'Test',
            'email'          => 'test@correo.com',
            'productos'      => [['producto_id' => $producto->id, 'cantidad' => 11]],
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['productos.0.cantidad']);
    }

    /** @test */
    public function falla_validacion_con_cantidad_cero(): void
    {
        $user     = $this->autenticarUsuario();
        $producto = $this->crearProducto();

        $response = $this->actingAs($user, 'sanctum')
                         ->postJson('/api/pedidos', [
            'nombre_cliente' => 'Test',
            'email'          => 'test@correo.com',
            'productos'      => [['producto_id' => $producto->id, 'cantidad' => 0]],
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['productos.0.cantidad']);
    }

    /** @test */
    public function falla_si_producto_id_no_existe_en_bd(): void
    {
        $user = $this->autenticarUsuario();

        $response = $this->actingAs($user, 'sanctum')
                         ->postJson('/api/pedidos', [
            'nombre_cliente' => 'Test',
            'email'          => 'test@correo.com',
            'productos'      => [['producto_id' => 9999, 'cantidad' => 1]],
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['productos.0.producto_id']);
    }

    // =========================================================================
    // TESTS: GET /api/pedidos → index()
    // =========================================================================

    /** @test */
    public function lista_todos_los_pedidos_ordenados_por_fecha(): void
    {
        $user     = $this->autenticarUsuario();
        $producto = $this->crearProducto();
        $this->crearPedidoCompleto($producto, 1);
        $this->crearPedidoCompleto($producto, 1);
        $this->crearPedidoCompleto($producto, 1);

        $response = $this->actingAs($user, 'sanctum')
                         ->getJson('/api/pedidos');

        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonCount(3, 'data')
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         '*' => [
                             'id', 'nombre_cliente', 'email', 'total', 'estado',
                             'detalles'
                         ]
                     ]
                 ]);
    }

    /** @test */
    public function lista_pedidos_devuelve_arreglo_vacio_si_no_hay_pedidos(): void
    {
        $user     = $this->autenticarUsuario();

        $response = $this->actingAs($user, 'sanctum')
                         ->getJson('/api/pedidos');

        $response->assertStatus(200)
                 ->assertJson(['success' => true, 'data' => []]);
    }

    // =========================================================================
    // TESTS: GET /api/pedidos/{id} → show()
    // =========================================================================

    /** @test */
    public function muestra_un_pedido_existente_con_sus_detalles(): void
    {
        $user     = $this->autenticarUsuario();
        $producto = $this->crearProducto(['precio' => 80.00]);
        $pedido   = $this->crearPedidoCompleto($producto, 2);

        $response = $this->actingAs($user, 'sanctum')
                         ->getJson("/api/pedidos/{$pedido->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id'     => $pedido->id,
                         'estado' => 'pendiente',
                     ]
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'detalles' => [
                             '*' => ['id', 'producto_id', 'cantidad', 'precio_unitario', 'subtotal', 'producto']
                         ]
                     ]
                 ]);
    }

    /** @test */
    public function devuelve_404_cuando_pedido_no_existe(): void
    {
        $user     = $this->autenticarUsuario();

        $response = $this->actingAs($user, 'sanctum')
                         ->getJson('/api/pedidos/9999');

        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Pedido no encontrado',
                 ]);
    }

    // =========================================================================
    // TESTS: PUT /api/pedidos/{id}/estado → actualizarEstado()
    // =========================================================================

    /** @test */
    public function cambia_estado_de_pendiente_a_completado(): void
    {
        $user     = $this->autenticarUsuario();
        $producto = $this->crearProducto();
        $pedido   = $this->crearPedidoCompleto($producto, 2);

        $response = $this->actingAs($user, 'sanctum')
                         ->putJson("/api/pedidos/{$pedido->id}/estado", [
            'estado' => 'completado',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data'    => ['estado' => 'completado'],
                 ]);

        $this->assertDatabaseHas('pedidos', [
            'id'     => $pedido->id,
            'estado' => 'completado',
        ]);
    }

    /** @test */
    public function cancelar_pedido_devuelve_el_stock_de_productos(): void
    {
        $user     = $this->autenticarUsuario();
        $producto = $this->crearProducto(['stock' => 50]);
        $pedido   = $this->crearPedidoCompleto($producto, 5);

        // Verificamos que el stock bajó a 45
        $this->assertDatabaseHas('productos', ['id' => $producto->id, 'stock' => 45]);

        // Cancelamos el pedido
        $response = $this->actingAs($user, 'sanctum')
                         ->putJson("/api/pedidos/{$pedido->id}/estado", [
            'estado' => 'cancelado',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data'    => ['estado' => 'cancelado'],
                 ]);

        // El stock debe haber vuelto a 50
        $this->assertDatabaseHas('productos', [
            'id'    => $producto->id,
            'stock' => 50,
        ]);
    }

    /** @test */
    public function cancelar_pedido_ya_cancelado_no_devuelve_stock_doble(): void
    {
        $user     = $this->autenticarUsuario();
        $producto = $this->crearProducto(['stock' => 50]);
        $pedido   = $this->crearPedidoCompleto($producto, 5);

        // Primera cancelación: stock vuelve a 50
        $this->actingAs($user, 'sanctum')
             ->putJson("/api/pedidos/{$pedido->id}/estado", ['estado' => 'cancelado']);

        // Intentamos cancelar de nuevo
        $this->actingAs($user, 'sanctum')
             ->putJson("/api/pedidos/{$pedido->id}/estado", ['estado' => 'cancelado']);

        // El stock NO debe duplicarse (debe quedar en 50, no en 55)
        $this->assertDatabaseHas('productos', [
            'id'    => $producto->id,
            'stock' => 50,
        ]);
    }

    /** @test */
    public function falla_validacion_con_estado_invalido(): void
    {
        $user     = $this->autenticarUsuario();
        $producto = $this->crearProducto();
        $pedido   = $this->crearPedidoCompleto($producto);

        $response = $this->actingAs($user, 'sanctum')
                         ->putJson("/api/pedidos/{$pedido->id}/estado", [
            'estado' => 'inexistente',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['estado']);
    }

    /** @test */
    public function falla_actualizar_estado_si_pedido_no_existe(): void
    {
        $user = $this->autenticarUsuario();

        $response = $this->actingAs($user, 'sanctum')
                         ->putJson('/api/pedidos/9999/estado', [
            'estado' => 'completado',
        ]);

        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Pedido no encontrado',
                 ]);
    }

    /** @test */
    public function el_estado_inicial_de_un_pedido_es_pendiente(): void
    {
        $user     = $this->autenticarUsuario();
        $producto = $this->crearProducto();

        $response = $this->actingAs($user, 'sanctum')
                         ->postJson('/api/pedidos', $this->payloadValido($producto->id));

        $response->assertStatus(201);
        $this->assertEquals('pendiente', $response->json('data.estado'));
    }
}
