<?php

/**
 * MODELO: PedidoDetalle
 * -------------------------------------------------------------------------
 * Este modelo representa un producto específico dentro de un pedido.
 * Es la tabla intermedia que conecta Pedidos con Productos.
 *
 * IMPORTANTE: El campo 'precio_unitario' se congela al momento de la compra.
 * Esto significa que si el precio del producto cambia en el futuro,
 * el precio del pedido ya realizado NO se ve afectado.
 *
 * Subtotal = cantidad × precio_unitario
 *
 * Relaciones:
 * - Un PedidoDetalle PERTENECE a un Pedido (belongsTo)
 * - Un PedidoDetalle PERTENECE a un Producto (belongsTo)
 *
 * Autor: Backend generado para Daikel Molina - Curso Fullstack UNET
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoDetalle extends Model
{
    use HasFactory;

    // Nombre de la tabla en la base de datos
    protected $table = 'pedido_detalles';

    // Campos que se pueden llenar masivamente
    protected $fillable = [
        'pedido_id',        // Referencia al pedido
        'producto_id',      // Referencia al producto
        'cantidad',         // Cantidad de unidades
        'precio_unitario',  // Precio al momento de la compra (congelado)
        'subtotal',         // Cantidad × Precio unitario
    ];

    // Convertir automáticamente estos campos a tipos de PHP
    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * RELACIÓN: Este detalle pertenece a un Pedido.
     *
     * Ejemplo de uso:
     *   $detalle = PedidoDetalle::find(1);
     *   $pedido = $detalle->pedido; // Devuelve el objeto Pedido completo
     *
     * @return BelongsTo - Un solo objeto Pedido
     */
    public function pedido(): BelongsTo
    {
        // 'pedido_id' es la llave foránea en esta tabla
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    /**
     * RELACIÓN: Este detalle pertenece a un Producto.
     *
     * Ejemplo de uso:
     *   $detalle = PedidoDetalle::find(1);
     *   $producto = $detalle->producto; // Devuelve la franela (Producto)
     *
     * @return BelongsTo - Un solo objeto Producto
     */
    public function producto(): BelongsTo
    {
        // 'producto_id' es la llave foránea en esta tabla
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}