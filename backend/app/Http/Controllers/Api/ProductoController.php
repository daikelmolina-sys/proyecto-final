<?php

/**
 * CONTROLADOR API: ProductoController
 * -------------------------------------------------------------------------
 * Este controlador maneja todas las operaciones CRUD de los productos
 * a través de la API REST. Devuelve respuestas en formato JSON puro.
 *
 * Endpoints:
 *   GET    /api/productos          → Listar todos los productos
 *   GET    /api/productos/{id}     → Ver detalle de un producto
 *
 * Autor: Backend generado para Daikel Molina - Curso Fullstack UNET
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    /**
     * MÉTODO: index - Listar todos los productos.
     *
     * Ruta: GET /api/productos
     *
     * Este método obtiene todos los productos de la base de datos y los
     * devuelve en formato JSON. Se usa para llenar el catálogo del frontend.
     *
     * Respuesta exitosa (200):
     * {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "nombre": "Argentina",
     *       "precio": "80.00",
     *       "imagen": "argentina.webp",
     *       "stock": 50,
     *       "disponible": true
     *     },
     *     ...
     *   ]
     * }
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        // Obtenemos todos los productos ordenados por nombre alfabéticamente
        $productos = Producto::orderBy('nombre', 'asc')->get();

        // Devolvemos respuesta JSON con la lista de productos
        return response()->json([
            'success' => true,
            'data'    => $productos,
        ], 200); // 200 = OK
    }

    /**
     * MÉTODO: show - Ver detalle de un producto específico.
     *
     * Ruta: GET /api/productos/{id}
     *
     * Busca un producto por su ID. Si no existe, devuelve un error 404.
     *
     * Respuesta exitosa (200):
     * {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "nombre": "Argentina",
     *     "precio": "80.00",
     *     "imagen": "argentina.webp",
     *     "stock": 50,
     *     "disponible": true
     *   }
     * }
     *
     * Respuesta error (404):
     * {
     *   "success": false,
     *   "message": "Producto no encontrado"
     * }
     *
     * @param int $id - ID del producto a buscar
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        // Buscamos el producto por su ID
        $producto = Producto::find($id);

        // Si no existe, devolvemos un error 404 (Not Found)
        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado',
            ], 404); // 404 = Not Found
        }

        // Devolvemos el producto encontrado
        return response()->json([
            'success' => true,
            'data'    => $producto,
        ], 200); // 200 = OK
    }
}