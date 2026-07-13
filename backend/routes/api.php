<?php

/**
 * RUTAS DE LA API - Franelas Mundial 2026
 * -------------------------------------------------------------------------
 * Este archivo define todas las rutas (endpoints) de la API REST.
 * Todas las rutas aquí tienen el prefijo automático '/api' que Laravel
 * asigna a este archivo.
 *
 * IMPORTANTE: Estas rutas NO necesitan el token CSRF porque
 * usan el middleware 'api' en vez de 'web'.
 *
 * RESUMEN DE ENDPOINTS:
 * ─────────────────────────────────────────────────────────────────
 * Método   Ruta                         Descripción             Auth
 * ─────────────────────────────────────────────────────────────────
 * POST     /api/register                Registrar usuario       No
 * POST     /api/login                   Iniciar sesión          No
 * POST     /api/logout                  Cerrar sesión           Sí
 * GET      /api/productos               Listar productos        No
 * GET      /api/productos/{id}          Ver producto            No
 * POST     /api/pedidos                 Crear pedido            Sí
 * GET      /api/pedidos                 Listar pedidos          Sí
 * GET      /api/pedidos/{id}            Ver detalle de pedido   Sí
 * PUT      /api/pedidos/{id}/estado     Cambiar estado          Sí
 * ─────────────────────────────────────────────────────────────────
 *
 * Autor: Backend generado para Daikel Molina - Curso Fullstack UNET
 */

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PedidoController;
use App\Http\Controllers\Api\ProductoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| RUTAS DE AUTENTICACIÓN (Públicas — sin middleware)
|--------------------------------------------------------------------------
*/

// POST /api/register → Crear nueva cuenta de usuario
Route::post('/register', [AuthController::class, 'register']);

// POST /api/login → Iniciar sesión y obtener token Bearer
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| RUTAS DE PRODUCTOS (Públicas — el catálogo es visible sin autenticación)
|--------------------------------------------------------------------------
*/

// GET /api/productos → Listar todos los productos del catálogo
Route::get('/productos', [ProductoController::class, 'index']);

// GET /api/productos/1 → Ver detalle del producto con ID 1
Route::get('/productos/{id}', [ProductoController::class, 'show']);

/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS (Requieren token Sanctum)
| Header requerido: Authorization: Bearer {token}
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // POST /api/logout → Cerrar sesión y revocar token actual
    Route::post('/logout', [AuthController::class, 'logout']);

    // GET  /api/pedidos → Listar todos los pedidos existentes
    Route::get('/pedidos', [PedidoController::class, 'index']);

    // GET  /api/pedidos/1 → Ver detalle completo del pedido con ID 1
    Route::get('/pedidos/{id}', [PedidoController::class, 'show']);

    // POST /api/pedidos → Crear un nuevo pedido (recibe JSON con datos del cliente y productos)
    Route::post('/pedidos', [PedidoController::class, 'store']);

    // PUT /api/pedidos/1/estado → Cambiar el estado del pedido (body: {"estado": "completado"})
    Route::put('/pedidos/{id}/estado', [PedidoController::class, 'actualizarEstado']);
});