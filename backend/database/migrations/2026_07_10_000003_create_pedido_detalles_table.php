<?php

/**
 * MIGRACIÓN: Tabla 'pedido_detalles'
 * -------------------------------------------------------------------------
 * Esta es la tabla intermedia (tabla pivote) que conecta los pedidos
 * con los productos. Cada fila representa UN producto dentro de UN pedido,
 * incluyendo la cantidad comprada, el precio unitario al momento de la
 * compra y el subtotal calculado (cantidad × precio_unitario).
 *
 * Esto permite que un pedido tenga múltiples productos y que el precio
 * se guarde congelado aunque el precio del producto cambie después.
 *
 * Autor: Backend generado para Daikel Molina - Curso Fullstack UNET
 */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración: crea la tabla 'pedido_detalles'.
     */
    public function up(): void
    {
        Schema::create('pedido_detalles', function (Blueprint $table) {
            // Campo autoincremental - llave primaria
            $table->id();

            // Relación con la tabla 'pedidos'
            // cascadeOnDelete: si se elimina el pedido, se eliminan sus detalles
            $table->foreignId('pedido_id')
                  ->constrained('pedidos')
                  ->cascadeOnDelete()
                  ->comment('Referencia al pedido al que pertenece este detalle');

            // Relación con la tabla 'productos'
            // restrictOnDelete: no se puede eliminar un producto si tiene pedidos
            $table->foreignId('producto_id')
                  ->constrained('productos')
                  ->restrictOnDelete()
                  ->comment('Referencia al producto comprado');

            // Cantidad de unidades de este producto en el pedido
            $table->integer('cantidad')
                  ->comment('Cantidad de unidades compradas');

            // Precio unitario al momento de la compra (se congela)
            // Esto es importante: aunque el precio del producto cambie,
            // el precio del pedido ya realizado NO debe cambiar
            $table->decimal('precio_unitario', 8, 2)
                  ->comment('Precio unitario al momento de la compra');

            // Subtotal = cantidad × precio_unitario
            $table->decimal('subtotal', 10, 2)
                  ->comment('Subtotal del detalle (cantidad × precio_unitario)');

            // Timestamps automáticos
            $table->timestamps();

            // Índice compuesto para buscar detalles por pedido rápidamente
            $table->index(['pedido_id', 'producto_id']);
        });
    }

    /**
     * Revierte la migración: elimina la tabla 'pedido_detalles'.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedido_detalles');
    }
};