<?php

/**
 * TESTS UNITARIOS: PedidoTest
 * -------------------------------------------------------------------------
 * Pruebas unitarias para el modelo Pedido.
 * Verifica la lógica interna del modelo sin tocar endpoints HTTP:
 *   - Scope 'conEstado' para filtrar por estado
 *   - Método 'recalcularTotal'
 *   - Campos fillable y casts
 *   - Relación hasMany con PedidoDetalle
 *   - Valores por defecto y estados válidos
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

class PedidoTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function crearPedido(array $atributos = []): Pedido
    {
        return Pedido::create(array_merge([
            'nombre_cliente' => 'Cliente Test',
            'email'          => 'test@correo.com',
            'telefono'       => '0412-0000000',
            'total'          => 0,
            'estado'         => 'pendiente',
        ], $atributos));
    }

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
    // TESTS: Scope 'conEstado'
    // =========================================================================

    /** @test */
    public function scope_con_estado_filtra_pedidos_pendientes(): void
    {
        $this->crearPedido(['estado' => 'pendiente']);
        $this->crearPedido(['estado' => 'completado']);
        $this->crearPedido(['estado' => 'cancelado']);

        $pendientes = Pedido::conEstado('pendiente')->get();

        $this->assertCount(1, $pendientes);
        $this->assertEquals('pendiente', $pendientes->first()->estado);
    }

    /** @test */
    public function scope_con_estado_filtra_pedidos_completados(): void
    {
        $this->crearPedido(['estado' => 'pendiente']);
        $this->crearPedido(['estado' => 'completado']);
        $this->crearPedido(['estado' => 'completado']);

        $completados = Pedido::conEstado('completado')->get();

        $this->assertCount(2, $completados);
    }

    /** @test */
    public function scope_con_estado_devuelve_vacio_si_no_hay_pedidos_con_ese_estado(): void
    {
        $this->crearPedido(['estado' => 'pendiente']);

        $cancelados = Pedido::conEstado('cancelado')->get();

        $this->assertCount(0, $cancelados);
    }

    // =========================================================================
    // TESTS: Método 'recalcularTotal'
    // =========================================================================

    /** @test */
    public function recalcular_total_suma_correctamente_los_subtotales(): void
    {
        $pedido   = $this->crearPedido(['total' => 0]);
        $producto = $this->crearProducto(['precio' => 80.00]);

        // Creamos 2 detalles: subtotal 160 + subtotal 80 = 240
        PedidoDetalle::create([
            'pedido_id'       => $pedido->id,
            'producto_id'     => $producto->id,
            'cantidad'        => 2,
            'precio_unitario' => 80.00,
            'subtotal'        => 160.00,
        ]);

        PedidoDetalle::create([
            'pedido_id'       => $pedido->id,
            'producto_id'     => $producto->id,
            'cantidad'        => 1,
            'precio_unitario' => 80.00,
            'subtotal'        => 80.00,
        ]);

        // Recalculamos
        $pedido->recalcularTotal();
        $pedido->refresh();

        $this->assertEquals('240.00', $pedido->total);
    }

    /** @test */
    public function recalcular_total_es_cero_si_no_hay_detalles(): void
    {
        $pedido = $this->crearPedido(['total' => 100]);

        // Recalcular sin detalles asociados debe poner 0
        $pedido->recalcularTotal();
        $pedido->refresh();

        $this->assertEquals('0.00', $pedido->total);
    }

    // =========================================================================
    // TESTS: Campos fillable y casts
    // =========================================================================

    /** @test */
    public function puede_crear_pedido_con_mass_assignment(): void
    {
        $pedido = Pedido::create([
            'nombre_cliente' => 'Daikel Molina',
            'email'          => 'daikel@correo.com',
            'telefono'       => '0412-1234567',
            'total'          => 250.00,
            'estado'         => 'pendiente',
        ]);

        $this->assertDatabaseHas('pedidos', [
            'nombre_cliente' => 'Daikel Molina',
            'email'          => 'daikel@correo.com',
            'estado'         => 'pendiente',
        ]);
    }

    /** @test */
    public function total_se_castea_como_decimal_con_dos_decimales(): void
    {
        $pedido = $this->crearPedido(['total' => 250]);

        $this->assertEquals('250.00', $pedido->total);
    }

    // =========================================================================
    // TESTS: Tabla del modelo
    // =========================================================================

    /** @test */
    public function el_modelo_usa_la_tabla_pedidos(): void
    {
        $pedido = new Pedido();

        $this->assertEquals('pedidos', $pedido->getTable());
    }

    // =========================================================================
    // TESTS: Relación con PedidoDetalle
    // =========================================================================

    /** @test */
    public function pedido_tiene_relacion_has_many_con_detalles(): void
    {
        $pedido   = $this->crearPedido();
        $producto = $this->crearProducto();

        $pedido->detalles()->create([
            'producto_id'     => $producto->id,
            'cantidad'        => 2,
            'precio_unitario' => 80.00,
            'subtotal'        => 160.00,
        ]);

        $pedido->detalles()->create([
            'producto_id'     => $producto->id,
            'cantidad'        => 1,
            'precio_unitario' => 80.00,
            'subtotal'        => 80.00,
        ]);

        $this->assertCount(2, $pedido->detalles);
        $this->assertInstanceOf(PedidoDetalle::class, $pedido->detalles->first());
    }

    // =========================================================================
    // TESTS: Estados del pedido
    // =========================================================================

    /** @test */
    public function puede_actualizar_estado_del_pedido(): void
    {
        $pedido = $this->crearPedido(['estado' => 'pendiente']);

        $pedido->update(['estado' => 'completado']);
        $pedido->refresh();

        $this->assertEquals('completado', $pedido->estado);
    }

    /** @test */
    public function pedido_puede_tener_telefono_nulo(): void
    {
        $pedido = Pedido::create([
            'nombre_cliente' => 'Sin Teléfono',
            'email'          => 'sintel@correo.com',
            'total'          => 100.00,
            'estado'         => 'pendiente',
            'telefono'       => null,
        ]);

        $this->assertNull($pedido->telefono);
        $this->assertDatabaseHas('pedidos', [
            'nombre_cliente' => 'Sin Teléfono',
            'telefono'       => null,
        ]);
    }
}
