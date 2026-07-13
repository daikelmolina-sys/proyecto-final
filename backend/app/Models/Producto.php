<?php

/**
 * MODELO: Producto
 * -------------------------------------------------------------------------
 * Este modelo representa una franela del Mundial 2026 en la base de datos.
 * Se conecta con la tabla 'productos' y tiene una relación uno-a-muchos
 * con PedidoDetalle (un producto puede aparecer en muchos detalles de pedido).
 *
 * Relaciones:
 * - Un Producto tiene MUCHOS PedidoDetalle (hasMany)
 *
 * Autor: Backend generado para Daikel Molina - Curso Fullstack UNET
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    use HasFactory;

    // Nombre de la tabla en la base de datos
    protected $table = 'productos';

    // Campos que se pueden llenar masivamente (Mass Assignment)
    // Esto es por seguridad: solo estos campos se pueden asignar con create() o update()
    protected $fillable = [
        'nombre',       // Nombre de la selección (ej: "Argentina")
        'precio',       // Precio de la franela
        'imagen',       // Ruta de la imagen
        'stock',        // Cantidad disponible
    ];

    // Acesores calculados que se incluyen automáticamente en la serialización JSON
    protected $appends = [
        'disponible',   // true si hay stock > 0
    ];

    // Convertir automáticamente estos campos a tipos de PHP
    protected $casts = [
        'precio' => 'decimal:2',    // Precio con 2 decimales
        'stock' => 'integer',       // Stock como entero
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * RELACIÓN: Un producto tiene muchos detalles de pedido.
     *
     * Ejemplo de uso:
     *   $producto = Producto::find(1);
     *   $detalles = $producto->pedidoDetalles; // Devuelve todos los detalles
     *
     * @return HasMany - Colección de PedidoDetalle
     */
    public function pedidoDetalles(): HasMany
    {
        // 'producto_id' es la llave foránea en la tabla pedido_detalles
        return $this->hasMany(PedidoDetalle::class, 'producto_id');
    }

    /**
     * ACCESOR: Verifica si el producto está disponible (stock > 0).
     *
     * Se usa así: $producto->disponible  (sin paréntesis)
     *
     * @return bool true si hay stock, false si no hay
     */
    public function getDisponibleAttribute(): bool
    {
        return $this->stock > 0;
    }
}