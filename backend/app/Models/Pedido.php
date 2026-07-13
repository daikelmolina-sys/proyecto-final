<?php

/**
 * MODELO: Pedido
 * -------------------------------------------------------------------------
 * Este modelo representa un pedido realizado por un cliente.
 * Se conecta con la tabla 'pedidos' y tiene una relación uno-a-muchos
 * con PedidoDetalle (un pedido puede tener muchos productos/detalles).
 *
 * El campo 'estado' puede ser: 'pendiente', 'completado' o 'cancelado'.
 *
 * Relaciones:
 * - Un Pedido tiene MUCHOS PedidoDetalle (hasMany)
 *
 * Autor: Backend generado para Daikel Molina - Curso Fullstack UNET
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedido extends Model
{
    use HasFactory;

    // Nombre de la tabla en la base de datos
    protected $table = 'pedidos';

    // Campos que se pueden llenar masivamente
    protected $fillable = [
        'nombre_cliente',   // Nombre del cliente
        'email',            // Email del cliente
        'telefono',         // Teléfono (opcional)
        'total',            // Total del pedido
        'estado',           // 'pendiente', 'completado', 'cancelado'
    ];

    // Convertir automáticamente estos campos a tipos de PHP
    protected $casts = [
        'total' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * RELACIÓN: Un pedido tiene muchos detalles (productos comprados).
     *
     * Ejemplo de uso:
     *   $pedido = Pedido::find(1);
     *   $detalles = $pedido->detalles; // Todos los productos del pedido
     *
     * @return HasMany - Colección de PedidoDetalle
     */
    public function detalles(): HasMany
    {
        // 'pedido_id' es la llave foránea en la tabla pedido_detalles
        return $this->hasMany(PedidoDetalle::class, 'pedido_id');
    }

    /**
     * MÉTODO: Recalcular el total del pedido sumando todos los subtotales.
     *
     * Este método es útil después de agregar, modificar o eliminar detalles.
     * Actualiza el campo 'total' en la base de datos automáticamente.
     *
     * Ejemplo de uso:
     *   $pedido->recalcularTotal(); // Recalcula y guarda en la BD
     *
     * @return void
     */
    public function recalcularTotal(): void
    {
        // Sumamos el campo 'subtotal' de todos los detalles de este pedido
        $nuevoTotal = $this->detalles->sum('subtotal');

        // Actualizamos el campo 'total' del pedido
        $this->update([
            'total' => $nuevoTotal,
        ]);
    }

    /**
     * SCOPE: Filtrar pedidos por estado.
     *
     * Se usa así: Pedido::conEstado('pendiente')->get();
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $estado - 'pendiente', 'completado' o 'cancelado'
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeConEstado($query, string $estado)
    {
        return $query->where('estado', $estado);
    }
}