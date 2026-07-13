<?php

/**
 * TESTS UNITARIOS: PedidoDetalleTest
 * -------------------------------------------------------------------------
 * Pruebas unitarias para el modelo PedidoDetalle.
 * Verifica la lógica interna del modelo sin tocar endpoints HTTP:
 *   - Relación belongsTo con Pedido
 *   - Relación belongsTo con Producto
 *   - Campos fillable y casts
 *   - Cálculo de subtotal (cantidad × precio_unitario)
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

class PedidoDetalleTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // HELPERS
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

    private function crearPedido(array $atributos = []): Pedido
    {
        return Pedido::create(array_merge([
            'nombre_cliente' => 'Cliente Test',
            'email'          => 'test@correo.com',
            'total'          => 0,
            'estado'         => 'pendiente',
        ], $atributos));
    }

    private function crearDetalle(Pedido $pedido, Producto $producto, int $cantidad = 2): PedidoDetalle
    {
        return PedidoDetalle::create([
            'pedido_id'       => $pedido->id,
            'producto_id'     => $producto->id,
            'cantidad'        => $cantidad,
            'precio_unitario' => $producto->precio,
            'subtotal'        => $producto->precio * $cantidad,
        ]);
    }

    // =========================================================================
    // TESTS: Relación belongsTo con Pedido
    // =========================================================================

    /** @test */
    public function detalle_pertenece_a_un_pedido(): void
    {
        $pedido   = $this->crearPedido();
        $producto = $this->crearProducto();
        $detalle  = $this->crearDetalle($pedido, $producto);

        $this->assertInstanceOf(Pedido::class, $detalle->pedido);
        $this->assertEquals($pedido->id, $detalle->pedido->id);
    }

    // =========================================================================
    // TESTS: Relación belongsTo con Producto
    // =========================================================================

    /** @test */
    public function detalle_pertenece_a_un_producto(): void
    {
        $pedido   = $this->crearPedido();
        $producto = $this->crearProducto(['nombre' => 'Brasil']);
        $detalle  = $this->crearDetalle($pedido, $producto);

        $this->assertInstanceOf(Producto::class, $detalle->producto);
        $this->assertEquals('Brasil', $detalle->producto->nombre);
    }

    // =========================================================================
    // TESTS: Campos fillable
    // =========================================================================

    /** @test */
    public function puede_crear_detalle_con_mass_assignment(): void
    {
        $pedido   = $this->crearPedido();
        $producto = $this->crearProducto(['precio' => 65.00]);

        $detalle = PedidoDetalle::create([
            'pedido_id'       => $pedido->id,
            'producto_id'     => $producto->id,
            'cantidad'        => 3,
            'precio_unitario' => 65.00,
            'subtotal'        => 195.00,
        ]);

        $this->assertDatabaseHas('pedido_detalles', [
            'pedido_id'       => $pedido->id,
            'producto_id'     => $producto->id,
            'cantidad'        => 3,
            'precio_unitario' => '65.00',
            'subtotal'        => '195.00',
        ]);
    }

    // =========================================================================
    // TESTS: Casts de atributos
    // =========================================================================

    /** @test */
    public function cantidad_se_castea_como_entero(): void
    {
        $pedido   = $this->crearPedido();
        $producto = $this->crearProducto();
        $detalle  = $this->crearDetalle($pedido, $producto, 3);

        $this->assertIsInt($detalle->cantidad);
        $this->assertEquals(3, $detalle->cantidad);
    }

    /** @test */
    public function precio_unitario_se_castea_como_decimal(): void
    {
        $pedido   = $this->crearPedido();
        $producto = $this->crearProducto(['precio' => 80.00]);
        $detalle  = $this->crearDetalle($pedido, $producto);

        $this->assertEquals('80.00', $detalle->precio_unitario);
    }

    /** @test */
    public function subtotal_se_castea_como_decimal(): void
    {
        $pedido   = $this->crearPedido();
        $producto = $this->crearProducto(['precio' => 80.00]);
        $detalle  = $this->crearDetalle($pedido, $producto, 2);

        // subtotal = 80 × 2 = 160.00
        $this->assertEquals('160.00', $detalle->subtotal);
    }

    // =========================================================================
    // TESTS: Tabla del modelo
    // =========================================================================

    /** @test */
    public function el_modelo_usa_la_tabla_pedido_detalles(): void
    {
        $detalle = new PedidoDetalle();

        $this->assertEquals('pedido_detalles', $detalle->getTable());
    }

    // =========================================================================
    // TESTS: Precio congelado
    // =========================================================================

    /** @test */
    public function precio_unitario_se_congela_al_crear_detalle(): void
    {
        $pedido   = $this->crearPedido();
        $producto = $this->crearProducto(['precio' => 80.00]);
        $detalle  = $this->crearDetalle($pedido, $producto, 1);

        // Verificar que el precio se congeló a 80.00
        $this->assertEquals('80.00', $detalle->precio_unitario);

        // Cambiamos el precio del producto
        $producto->update(['precio' => 120.00]);

        // El precio del detalle NO debe cambiar (está congelado)
        $detalle->refresh();
        $this->assertEquals('80.00', $detalle->precio_unitario);
    }
}
