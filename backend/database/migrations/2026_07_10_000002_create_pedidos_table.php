<?php

/**
 * MIGRACIÓN: Tabla 'pedidos'
 * -------------------------------------------------------------------------
 * Esta migración crea la tabla que almacena cada pedido realizado por
 * los clientes. Cada pedido tiene datos del cliente, un total calculado
 * y un estado que puede ser: pendiente, completado o cancelado.
 *
 * Autor: Backend generado para Daikel Molina - Curso Fullstack UNET
 */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración: crea la tabla 'pedidos'.
     */
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            // Campo autoincremental - llave primaria
            $table->id();

            // Datos del cliente que realiza el pedido
            $table->string('nombre_cliente', 150)
                  ->comment('Nombre completo del cliente');

            $table->string('email', 150)
                  ->comment('Correo electrónico de contacto del cliente');

            $table->string('telefono', 20)->nullable()
                  ->comment('Teléfono de contacto (opcional)');

            // Total del pedido (suma de todos los subtotales de los detalles)
            $table->decimal('total', 10, 2)->default(0)
                  ->comment('Monto total del pedido');

            // Estado del pedido usando un ENUM
            // 'pendiente' = recién creado
            // 'completado' = pagado y enviado
            // 'cancelado' = anulado por el cliente o admin
            $table->enum('estado', ['pendiente', 'completado', 'cancelado'])
                  ->default('pendiente')
                  ->comment('Estado actual del pedido');

            // Timestamps automáticos
            $table->timestamps();

            // Índice para buscar pedidos por estado rápidamente
            $table->index('estado');
        });
    }

    /**
     * Revierte la migración: elimina la tabla 'pedidos'.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};