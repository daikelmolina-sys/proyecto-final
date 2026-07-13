<?php

/**
 * SEEDER: ProductoSeeder
 * -------------------------------------------------------------------------
 * Inserta los 6 productos de franelas del Mundial 2026.
 * Los nombres de imágenes están en MINÚSCULAS para coincidir con el
 * frontend y evitar problemas case-sensitive en servidores Linux.
 *
 * Uso:
 *   php artisan db:seed --class=ProductoSeeder
 */

namespace Database\Seeders;

use App\Models\Producto;
use Illuminate\Database\Seeder;

class ProductoSeeder extends Seeder
{
    public function run(): void
    {
        $productos = [
            [
                'nombre' => 'Argentina',
                'precio' => 80.00,
                'imagen' => 'argentina.webp',
                'stock'  => 50,
            ],
            [
                'nombre' => 'Brasil',
                'precio' => 65.00,
                'imagen' => 'brasil.webp',
                'stock'  => 50,
            ],
            [
                'nombre' => 'Alemania',
                'precio' => 70.00,
                'imagen' => 'alemania.webp',
                'stock'  => 50,
            ],
            [
                'nombre' => 'Portugal',
                'precio' => 60.00,
                'imagen' => 'portugal.webp',
                'stock'  => 50,
            ],
            [
                'nombre' => 'Colombia',
                'precio' => 75.00,
                'imagen' => 'colombia.webp',
                'stock'  => 50,
            ],
            [
                'nombre' => 'España',
                'precio' => 90.00,
                'imagen' => 'españa.webp',
                'stock'  => 50,
            ],
        ];

        foreach ($productos as $producto) {
            Producto::firstOrCreate(
                ['nombre' => $producto['nombre']],
                $producto
            );
        }

        $this->command->info('✅ Se insertaron los 6 productos del Mundial 2026 correctamente.');
    }
}