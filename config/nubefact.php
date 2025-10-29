<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Nubefact API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para la integración con Nubefact, proveedor de facturación
    | electrónica para SUNAT Perú.
    |
    */

    'api_url' => env('NUBEFACT_API_URL', 'https://api.nubefact.com/api/v1/'),

    'token' => env('NUBEFACT_TOKEN', ''),

    'ruc' => env('NUBEFACT_RUC', ''),

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | Entorno de ejecución: 'production' o 'sandbox'
    | En sandbox se usa la URL de pruebas de Nubefact
    |
    */

    'environment' => env('NUBEFACT_ENVIRONMENT', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | Tiempo máximo de espera para las peticiones HTTP en segundos
    |
    */

    'timeout' => env('NUBEFACT_TIMEOUT', 30),

];
