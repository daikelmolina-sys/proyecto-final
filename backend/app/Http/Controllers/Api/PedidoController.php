<?php

/**
 * CONTROLADOR API: PedidoController
 * -------------------------------------------------------------------------
 * Este controlador maneja todas las operaciones de los pedidos
 * a través de la API REST. Devuelve respuestas en formato JSON puro.
 *
 * Endpoints:
 *   POST   /api/pedidos              → Crear un nuevo pedido
 *   GET    /api/pedidos              → Listar todos los pedidos
 *   GET    /api/pedidos/{id}         → Ver detalle de un pedido con sus items
 *   PUT    /api/pedidos/{id}/estado  → Cambiar estado de un pedido
 *
 * Autor: Backend generado para Daikel Molina - Curso Fullstack UNET
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePedidoRequest;
use App\Models\Pedido;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PedidoController extends Controller
{
    /**
     * MÉTODO: index - Listar todos los pedidos.
     *
     * Ruta: GET /api/pedidos
     *
     * Devuelve todos los pedidos con sus detalles (productos comprados).
     * Se ordenan del más reciente al más antiguo.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        // Obtenemos todos los pedidos con sus detalles y productos asociados
        // El método 'with()' evita el problema N+1 (hace una sola consulta)
        $pedidos = Pedido::with('detalles.producto')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $pedidos,
        ], 200);
    }

    /**
     * MÉTODO: store - Crear un nuevo pedido.
     *
     * Ruta: POST /api/pedidos
     *
     * Este es el método más complejo. Recibe los datos del cliente y
     * una lista de productos con cantidades. Hace lo siguiente:
     *
     * 1. Valida los datos (nombre, email, productos, cantidades)
     * 2. Verifica que cada producto exista y tenga stock suficiente
     * 3. Crea el pedido con los datos del cliente
     * 4. Crea cada detalle del pedido (producto + cantidad + precio + subtotal)
     * 5. Descuenta el stock de cada producto
     * 6. Calcula y guarda el total del pedido
     *
     * BODY del request (JSON):
     * {
     *   "nombre_cliente": "Daikel Molina",
     *   "email": "daikel@correo.com",
     *   "telefono": "0412-1234567",
     *   "productos": [
     *     { "id": 1, "cantidad": 2 },
     *     { "id": 3, "cantidad": 1 }
     *   ]
     * }
     *
     * @param StorePedidoRequest $request - Request con validaciones ya aplicadas
     * @return JsonResponse
     */
    public function store(StorePedidoRequest $request): JsonResponse
    {
        // Obtenemos los datos validados del request
        $datosValidados = $request->validated();

        // Usamos una TRANSACCIÓN de base de datos:
        // Si algo falla, TODOS los cambios se revierten automáticamente.
        // Esto evita crear un pedido sin detalles, o descontar stock sin pedido.
        try {
            $pedido = DB::transaction(function () use ($datosValidados) {
                // PASO 1: Crear el pedido con estado 'pendiente' y total 0
                $pedido = Pedido::create([
                    'nombre_cliente' => $datosValidados['nombre_cliente'],
                    'email'          => $datosValidados['email'],
                    'telefono'       => $datosValidados['telefono'] ?? null,
                    'total'          => 0, // Se calculará después
                    'estado'         => 'pendiente',
                ]);

                // PASO 2: Crear los detalles del pedido
                $totalCalculado = 0;

                foreach ($datosValidados['productos'] as $item) {
                    // Buscar el producto en la base de datos
                    $producto = Producto::find($item['producto_id']);

                    // Verificar que el producto exista
                    if (!$producto) {
                        throw new \Exception("El producto con ID {$item['producto_id']} no existe.");
                    }

                    // Verificar que haya stock suficiente
                    if ($producto->stock < $item['cantidad']) {
                        throw new \Exception("Stock insuficiente para '{$producto->nombre}'. Disponible: {$producto->stock}, Solicitado: {$item['cantidad']}.");
                    }

                    // Calcular el subtotal de esta línea
                    $subtotal = $producto->precio * $item['cantidad'];
                    $totalCalculado += $subtotal;

                    // Crear el detalle del pedido
                    $pedido->detalles()->create([
                        'producto_id'    => $producto->id,
                        'cantidad'       => $item['cantidad'],
                        'precio_unitario' => $producto->precio, // Se congela el precio
                        'subtotal'       => $subtotal,
                    ]);

                    // PASO 3: Descontar el stock del producto
                    $producto->decrement('stock', $item['cantidad']);
                }

                // PASO 4: Actualizar el total del pedido
                $pedido->update([
                    'total' => $totalCalculado,
                ]);

                return $pedido;
            });

            // Cargamos los detalles para la respuesta
            $pedido->load('detalles.producto');

            // Devolvemos el pedido creado con código 201 (Created)
            return response()->json([
                'success' => true,
                'message' => 'Pedido creado exitosamente',
                'data'    => $pedido,
            ], 201); // 201 = Created

        } catch (\Exception $e) {
            // Si algo falló dentro de la transacción, se revierte todo
            // y devolvemos un error 400 (Bad Request)
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el pedido: ' . $e->getMessage(),
            ], 400); // 400 = Bad Request
        }
    }

    /**
     * MÉTODO: show - Ver detalle de un pedido específico.
     *
     * Ruta: GET /api/pedidos/{id}
     *
     * Devuelve el pedido con todos sus detalles (productos comprados,
     * cantidades, precios unitarios y subtotales).
     *
     * @param int $id - ID del pedido
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        // Buscamos el pedido con sus detalles y productos asociados
        $pedido = Pedido::with('detalles.producto')->find($id);

        // Si no existe, devolvemos error 404
        if (!$pedido) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $pedido,
        ], 200);
    }

    /**
     * MÉTODO: actualizarEstado - Cambiar el estado de un pedido.
     *
     * Ruta: PUT /api/pedidos/{id}/estado
     *
     * Permite cambiar el estado de un pedido entre:
     * 'pendiente' → 'completado' o 'cancelado'
     *
     * Si se cancela un pedido, se DEVUELVE el stock de los productos.
     * Si se completa, no se hace nada extra.
     *
     * BODY del request (JSON):
     * {
     *   "estado": "completado"
     * }
     *
     * @param Request $request
     * @param int $id - ID del pedido
     * @return JsonResponse
     */
    public function actualizarEstado(Request $request, int $id): JsonResponse
    {
        // Validar que el estado enviado sea válido
        $request->validate([
            'estado' => 'required|in:pendiente,completado,cancelado',
        ]);

        // Buscar el pedido
        $pedido = Pedido::with('detalles')->find($id);

        if (!$pedido) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado',
            ], 404);
        }

        $nuevoEstado = $request->input('estado');

        // Si se está cancelando el pedido, devolver el stock
        if ($nuevoEstado === 'cancelado' && $pedido->estado !== 'cancelado') {
            try {
                DB::transaction(function () use ($pedido) {
                    foreach ($pedido->detalles as $detalle) {
                        // Incrementamos el stock del producto
                        Producto::where('id', $detalle->producto_id)
                            ->increment('stock', $detalle->cantidad);
                    }

                    // Actualizamos el estado del pedido
                    $pedido->update(['estado' => 'cancelado']);
                });

                return response()->json([
                    'success' => true,
                    'message' => 'Pedido cancelado. Stock devuelto correctamente.',
                    'data'    => $pedido->fresh(),
                ], 200);

            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al cancelar el pedido: ' . $e->getMessage(),
                ], 400);
            }
        }

        // Para cualquier otro cambio de estado (pendiente → completado, etc.)
        $pedido->update(['estado' => $nuevoEstado]);

        return response()->json([
            'success' => true,
            'message' => "Estado del pedido actualizado a '{$nuevoEstado}'",
            'data'    => $pedido->fresh()->load('detalles.producto'),
        ], 200);
    }
}