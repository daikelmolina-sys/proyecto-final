<?php

/**
 * FORM REQUEST: StorePedidoRequest
 * -------------------------------------------------------------------------
 * Esta clase se encarga de VALIDAR los datos que llegan cuando se crea
 * un pedido nuevo. En lugar de poner la validación directamente en el
 * controlador, Laravel nos permite separar la lógica de validación
 * en una clase dedicada.
 *
 * ¿Por qué usar FormRequest en vez de validar en el controlador?
 * 1. El controlador queda más limpio y fácil de leer
 * 2. Se puede reutilizar la misma validación en otros lugares
 * 3. Si la validación falla, Laravel devuelve automáticamente un error 422
 *    con los mensajes en español (gracias a los mensajes personalizados)
 *
 * Autor: Backend generado para Daikel Molina - Curso Fullstack UNET
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePedidoRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a hacer esta petición.
     * En este caso, devolvemos true para permitir que cualquiera pueda
     * crear pedidos (no hay autenticación en este proyecto).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * REGLAS DE VALIDACIÓN para crear un pedido.
     *
     * Cada campo tiene reglas separadas por '|':
     * - required    = El campo es obligatorio
     * - string      = Debe ser texto
     * - email       = Debe ser un correo electrónico válido
     * - max:X       = Máximo X caracteres
     * - nullable    = El campo es opcional (puede ser nulo)
     * - array       = Debe ser un arreglo (lista)
     * - min:X       = Mínimo X elementos
     * - *           = Aplica las reglas a cada elemento del arreglo
     * - integer     = Debe ser un número entero
     * - exists:tabla,columna = El valor debe existir en esa tabla/columna
     * - min:1       = El valor debe ser al menos 1
     * - max:10      = El valor máximo es 10 (igual que en tu frontend)
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // --- Datos del cliente ---
            'nombre_cliente' => 'required|string|max:150',
            'email'          => 'required|email|max:150',
            'telefono'       => 'nullable|string|max:20',

            // --- Productos del pedido ---
            // 'productos' debe ser un arreglo con al menos 1 elemento
            'productos' => 'required|array|min:1',

            // Cada elemento dentro del arreglo 'productos' debe tener:
            'productos.*.producto_id' => 'required|integer|exists:productos,id',
            'productos.*.cantidad'    => 'required|integer|min:1|max:10',
        ];
    }

    /**
     * MENSAJES PERSONALIZADOS de error en español.
     *
     * Cuando una validación falla, Laravel busca aquí el mensaje
     * correspondiente. Si no está definido, usa el mensaje en inglés.
     *
     * Formato: 'campo.reglita' => 'Mensaje personalizado'
     * El :attribute se reemplaza por el nombre del campo
     * El :max se reemplaza por el valor máximo
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // --- Mensajes de nombre_cliente ---
            'nombre_cliente.required' => 'El nombre del cliente es obligatorio.',
            'nombre_cliente.max'      => 'El nombre no puede tener más de 150 caracteres.',

            // --- Mensajes de email ---
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email'    => 'Debes ingresar un correo electrónico válido.',
            'email.max'      => 'El correo no puede tener más de 150 caracteres.',

            // --- Mensajes de telefono ---
            'telefono.max' => 'El teléfono no puede tener más de 20 caracteres.',

            // --- Mensajes de productos (arreglo) ---
            'productos.required' => 'Debes agregar al menos un producto al pedido.',
            'productos.array'    => 'El formato de productos no es válido.',
            'productos.min'      => 'Debes agregar al menos un producto al pedido.',

            // --- Mensajes de cada producto ---
            'productos.*.producto_id.required' => 'Cada producto debe tener un ID.',
            'productos.*.producto_id.integer'  => 'El ID del producto debe ser un número entero.',
            'productos.*.producto_id.exists'   => 'Uno de los productos seleccionados no existe.',

            'productos.*.cantidad.required' => 'La cantidad de cada producto es obligatoria.',
            'productos.*.cantidad.integer'  => 'La cantidad debe ser un número entero.',
            'productos.*.cantidad.min'      => 'La cantidad mínima es 1 unidad.',
            'productos.*.cantidad.max'      => 'La cantidad máxima por producto es 10 unidades.',
        ];
    }
}