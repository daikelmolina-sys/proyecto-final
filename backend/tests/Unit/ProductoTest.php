<?php

/**
 * TESTS UNITARIOS: ProductoTest
 * -------------------------------------------------------------------------
 * Pruebas unitarias para el modelo Producto.
 * Verifica la lógica interna del modelo sin tocar endpoints HTTP:
 *   - Accesor 'disponible' (true/false según stock)
 *   - Campos fillable
 *   - Casts de atributos
 *   - Relación hasMany con PedidoDetalle
 *
 * Usa SQLite en memoria (configurado en phpunit.xml).
 *
 * Autor: Backend generado para Daikel Molina - Curso Fullstack UNET
 */

namespace Tests\Unit;

use App\Models\Pedido;
use App\Models\PedidoDetalle;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductoTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // HELPER
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
    // TESTS: Accesor 'disponible'
    // =========================================================================

    /** @test */
    public function disponible_es_true_cuando_stock_es_mayor_a_cero(): void
    {
        $producto = $this->crearProducto(['stock' => 10]);

        $this->assertTrue($producto->disponible);
    }

    /** @test */
    public function disponible_es_false_cuando_stock_es_cero(): void
    {
        $producto = $this->crearProducto(['stock' => 0]);

        $this->assertFalse($producto->disponible);
    }

    /** @test */
    public function disponible_cambia_cuando_stock_se_agota(): void
    {
        $producto = $this->crearProducto(['stock' => 1]);

        $this->assertTrue($producto->disponible);

        // Decrementamos el stock a 0
        $producto->decrement('stock', 1);
        $producto->refresh();

        $this->assertFalse($producto->disponible);
    }

    // =========================================================================
    // TESTS: Campos fillable
    // =========================================================================

    /** @test */
    public function puede_crear_producto_con_mass_assignment(): void
    {
        $producto = Producto::create([
            'nombre' => 'Brasil',
            'precio' => 65.00,
            'imagen' => 'brasil.webp',
            'stock'  => 30,
        ]);

        $this->assertDatabaseHas('productos', [
            'nombre' => 'Brasil',
            'precio' => '65.00',
            'imagen' => 'brasil.webp',
            'stock'  => 30,
        ]);
    }

    // =========================================================================
    // TESTS: Casts de atributos
    // =========================================================================

    /** @test */
    public function precio_se_castea_como_decimal_con_dos_decimales(): void
    {
        $producto = $this->crearProducto(['precio' => 80]);

        // El cast 'decimal:2' debe retornar el precio como string con 2 decimales
        $this->assertEquals('80.00', $producto->precio);
    }

    /** @test */
    public function stock_se_castea_como_entero(): void
    {
        $producto = $this->crearProducto(['stock' => '25']);

        $this->assertIsInt($producto->stock);
        $this->assertEquals(25, $producto->stock);
    }

    // =========================================================================
    // TESTS: Tabla del modelo
    // =========================================================================

    /** @test */
    public function el_modelo_usa_la_tabla_productos(): void
    {
        $producto = new Producto();

        $this->assertEquals('productos', $producto->getTable());
    }

    // =========================================================================
    // TESTS: Relación con PedidoDetalle
    // =========================================================================

    /** @test */
    public function producto_tiene_relacion_has_many_con_pedido_detalles(): void
    {
        $producto = $this->crearProducto();

        // Creamos un pedido y un detalle asociado al producto
        $pedido = Pedido::create([
            'nombre_cliente' => 'Test Cliente',
            'email'          => 'test@correo.com',
            'total'          => 160.00,
            'estado'         => 'pendiente',
        ]);

        PedidoDetalle::create([
            'pedido_id'       => $pedido->id,
            'producto_id'     => $producto->id,
            'cantidad'        => 2,
            'precio_unitario' => 80.00,
            'subtotal'        => 160.00,
        ]);

        // Verificamos la relación
        $this->assertCount(1, $producto->pedidoDetalles);
        $this->assertInstanceOf(PedidoDetalle::class, $producto->pedidoDetalles->first());
    }

    // =========================================================================
    // TESTS: Accesor 'disponible' en serialización JSON
    // =========================================================================

    /** @test */
    public function disponible_se_incluye_en_la_serializacion_json(): void
    {
        $producto = $this->crearProducto(['stock' => 5]);
        $array = $producto->toArray();

        $this->assertArrayHasKey('disponible', $array);
        $this->assertTrue($array['disponible']);
    }
}
