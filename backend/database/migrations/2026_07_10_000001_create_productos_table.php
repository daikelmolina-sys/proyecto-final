<?php

/**
 * MIGRACIÓN: Tabla 'productos'
 * -------------------------------------------------------------------------
 * Esta migración crea la tabla donde se almacenan todas las franelas
 * del Mundial 2026. Cada producto tiene un nombre, precio, ruta de imagen
 * y un campo de stock para controlar la disponibilidad.
 *
 * Autor: Backend generado para Daikel Molina - Curso Fullstack UNET
 */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración: crea la tabla 'productos'.
     * Se ejecuta con: php artisan migrate
     */
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            // Campo autoincremental - es la llave primaria
            $table->id();

            // Nombre del equipo/selección (ej: "Argentina", "Brasil")
            $table->string('nombre', 100)->comment('Nombre de la selección nacional');

            // Precio de la franela en dólares
            $table->decimal('precio', 8, 2)->comment('Precio unitario de la franela');

            // Ruta de la imagen del producto (se guarda en public/imagenes)
            $table->string('imagen', 255)->default('sin-imagen.webp')
                  ->comment('Ruta relativa de la imagen del producto');

            // Cantidad disponible en inventario
            $table->integer('stock')->default(50)
                  ->comment('Cantidad disponible en inventario');

            // Timestamps automáticos: created_at y updated_at
            $table->timestamps();
        });
    }

    /**
     * Revierte la migración: elimina la tabla 'productos'.
     * Se ejecuta con: php artisan migrate:rollback
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};