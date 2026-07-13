<?php

/**
 * CONFIGURACIÓN: CORS (Cross-Origin Resource Sharing)
 * -------------------------------------------------------------------------
 * Este archivo permite que tu frontend (que corre en XAMPP en
 * http://localhost o http://localhost:8080) pueda hacer peticiones
 * a tu API Laravel (que corre en http://localhost:8000) sin que
 * el navegador bloquee la solicitud por política de origen cruzado.
 *
 * SIN esta configuración, verás este error en la consola del navegador:
 *   "Access-Control-Allow-Origin" header is missing
 *
 * Autor: Backend generado para Daikel Molina - Curso Fullstack UNET
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Rutas permitidas (CORS Paths)
    |--------------------------------------------------------------------------
    | Define qué rutas de tu aplicación están sujetas a las reglas CORS.
    | Usamos 'api/*' para cubrir todos los endpoints de la API.
    */
    'paths' => ['api/*'],

    /*
    |--------------------------------------------------------------------------
    | Métodos HTTP permitidos
    |--------------------------------------------------------------------------
    | Métodos que el navegador puede usar al hacer peticiones cruzadas.
    */
    'allowed_methods' => ['*'],  // * = todos (GET, POST, PUT, DELETE, etc.)

    /*
    |--------------------------------------------------------------------------
    | Orígenes permitidos (Allowed Origins)
    |--------------------------------------------------------------------------
    | URLs desde las cuales se permiten las peticiones.
    | Agrega aquí la URL de tu frontend en XAMPP.
    */
    'allowed_origins' => [
        'http://localhost',           // Frontend en XAMPP (puerto 80 por defecto)
        'http://localhost:8080',      // Frontend en XAMPP (puerto alternativo)
        'http://localhost:8000',      // PHP artisan serve (mismo host)
        'http://127.0.0.1',          // Alternativa a localhost
        'http://127.0.0.1:5500',     //Live server
        'http://127.0.0.1:8080',     // Alternativa a localhost:8080
        'http://127.0.0.1:8000',     // Alternativa artisan serve
        'null',                       // file:// origin (abrir HTML directo)
    ],

    /*
    |--------------------------------------------------------------------------
    | Patrones de orígenes permitidos
    |--------------------------------------------------------------------------
    | Permite orígenes que coincidan con estos patrones (regex).
    */
    'allowed_origins_patterns' => [],

    /*
    |--------------------------------------------------------------------------
    | Cabeceras permitidas (Allowed Headers)
    |--------------------------------------------------------------------------
    | Headers que el navegador puede enviar en las peticiones.
    */
    'allowed_headers' => ['*'],  // * = todas las cabeceras

    /*
    |--------------------------------------------------------------------------
    | Cabeceras expuestas (Exposed Headers)
    |--------------------------------------------------------------------------
    | Headers que el navegador puede leer en la respuesta.
    */
    'exposed_headers' => [],

    /*
    |--------------------------------------------------------------------------
    | Permitir credenciales (Allow Credentials)
    |--------------------------------------------------------------------------
    | Debe ser true cuando el frontend envía tokens en los headers
    | (Authorization: Bearer {token}) al usar Laravel Sanctum.
    */
    'max_age' => 0,

    /*
    |--------------------------------------------------------------------------
    | Soporte de credenciales
    |--------------------------------------------------------------------------
    | true = Requerido por Sanctum para que los headers de autenticación
    |        sean aceptados en peticiones de origen cruzado.
    */
    'supports_credentials' => true,

];