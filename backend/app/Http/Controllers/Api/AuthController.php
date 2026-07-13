<?php

/**
 * CONTROLADOR: AuthController
 * -------------------------------------------------------------------------
 * Maneja el registro, inicio de sesión y cierre de sesión de usuarios
 * utilizando Laravel Sanctum para la generación y revocación de tokens.
 *
 * Endpoints:
 *   POST /api/register  → Registrar nuevo usuario
 *   POST /api/login     → Iniciar sesión y obtener token
 *   POST /api/logout    → Cerrar sesión y revocar token
 *
 * Autor: Backend generado para Daikel Molina - Curso Fullstack UNET
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * REGISTER — Registrar un nuevo usuario.
     *
     * Valida los datos, crea el usuario y devuelve un token de acceso.
     *
     * @param  Request $request  Debe contener: name, email, password, password_confirmation
     * @return JsonResponse      {success, message, data: {user, token}}
     */
    public function register(Request $request): JsonResponse
    {
        // Validar los campos requeridos
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Crear el usuario (la contraseña se hashea automáticamente por el cast en el modelo)
        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Generar un token de acceso personal para el usuario recién registrado
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Usuario registrado exitosamente',
            'data'    => [
                'user'  => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                ],
                'token' => $token,
            ],
        ], 201);
    }

    /**
     * LOGIN — Iniciar sesión con credenciales existentes.
     *
     * Valida credenciales contra la BD y devuelve un token de acceso.
     *
     * @param  Request $request  Debe contener: email, password
     * @return JsonResponse      {success, message, data: {user, token}}
     * @throws ValidationException si las credenciales son incorrectas
     */
    public function login(Request $request): JsonResponse
    {
        // Validar que los campos estén presentes
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // Buscar el usuario por email
        $user = User::where('email', $request->email)->first();

        // Verificar que el usuario existe y que la contraseña coincide
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas. Verifique su email y contraseña.',
            ], 401);
        }

        // Generar un nuevo token de acceso personal
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Sesión iniciada exitosamente',
            'data'    => [
                'user'  => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                ],
                'token' => $token,
            ],
        ], 200);
    }

    /**
     * LOGOUT — Cerrar sesión revocando el token actual.
     *
     * Elimina el token que se está usando en esta petición.
     * Requiere middleware auth:sanctum.
     *
     * @param  Request $request  Con header Authorization: Bearer {token}
     * @return JsonResponse      {success, message}
     */
    public function logout(Request $request): JsonResponse
    {
        // Revocar el token actual del usuario autenticado
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada exitosamente. Token revocado.',
        ], 200);
    }
}
