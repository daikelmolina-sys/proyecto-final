<?php

/**
 * TESTS: ProductoApiTest
 * -------------------------------------------------------------------------
 * Pruebas de integración para los endpoints de productos:
 *   GET /api/productos       → Listar todos los productos
 *   GET /api/productos/{id}  → Ver detalle de un producto
 *
 * Usa SQLite en memoria (configurado en phpunit.xml) para no afectar la BD real.
 */

namespace Tests\Feature;

use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductoApiTest extends TestCase
{
    // RefreshDatabase: migra y limpia la BD en memoria antes de cada test
    use RefreshDatabase;

    // =========================================================================
    // HELPER: Crea un producto de prueba con datos por defecto
    // =========================================================================
    private function crearProducto(array $atributos = []): Producto
    {
        return Producto::create(array_merge([
            'nombre' => 'Argentina',
            'precio' => 80.00,
            'imagen' => 'argentina.webp',
            'stock'  => 50,
        ], $atributos));
    }

    // =========================================================================
    // TESTS: GET /api/productos
    // =========================================================================

    /** @test */
    public function lista_todos_los_productos_cuando_existen(): void
    {
        // Arrange: creamos 3 productos
        $this->crearProducto(['nombre' => 'Argentina']);
        $this->crearProducto(['nombre' => 'Brasil',  'precio' => 65.00]);
        $this->crearProducto(['nombre' => 'Alemania', 'precio' => 70.00]);

        // Act: hacemos la solicitud GET
        $response = $this->getJson('/api/productos');

        // Assert: verificamos la respuesta
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         '*' => ['id', 'nombre', 'precio', 'imagen', 'stock']
                     ]
                 ])
                 ->assertJson(['success' => true])
                 ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function lista_productos_devuelve_arreglo_vacio_cuando_no_hay_productos(): void
    {
        // Act
        $response = $this->getJson('/api/productos');

        // Assert: 200 OK con data vacía
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data'    => [],
                 ]);
    }

    /** @test */
    public function lista_productos_contiene_campos_correctos(): void
    {
        $this->crearProducto(['nombre' => 'España', 'precio' => 90.00, 'stock' => 30]);

        $response = $this->getJson('/api/productos');

        $response->assertStatus(200);
        $data = $response->json('data.0');

        $this->assertEquals('España', $data['nombre']);
        $this->assertEquals('90.00', $data['precio']);
        $this->assertEquals(30, $data['stock']);
    }

    // =========================================================================
    // TESTS: GET /api/productos/{id}
    // =========================================================================

    /** @test */
    public function muestra_un_producto_existente_por_id(): void
    {
        $producto = $this->crearProducto(['nombre' => 'Colombia', 'precio' => 75.00]);

        $response = $this->getJson("/api/productos/{$producto->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id'     => $producto->id,
                         'nombre' => 'Colombia',
                         'precio' => '75.00',
                     ]
                 ]);
    }

    /** @test */
    public function devuelve_404_cuando_producto_no_existe(): void
    {
        $response = $this->getJson('/api/productos/9999');

        $response->assertStatus(404)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Producto no encontrado',
                 ]);
    }

    /** @test */
    public function producto_tiene_campo_disponible_true_cuando_hay_stock(): void
    {
        $producto = $this->crearProducto(['stock' => 10]);

        $response = $this->getJson("/api/productos/{$producto->id}");

        $response->assertStatus(200);
        // El accesor 'disponible' debe ser true
        $this->assertTrue($response->json('data.disponible'));
    }
}
