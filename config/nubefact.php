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
  '13' => [
    'api_url' => env('NUBEFACT_API_URL_CHICLAYO', 'https://api.nubefact.com/api/v1/'),
    'token' => env('NUBEFACT_TOKEN_CHICLAYO', ''),
    'ruc' => env('NUBEFACT_RUC_CHICLAYO', ''),
  ],
  '14' => [
    'api_url' => env('NUBEFACT_API_URL_PIMENTEL', 'https://api.nubefact.com/api/v1/'),
    'token' => env('NUBEFACT_TOKEN_PIMENTEL', ''),
    'ruc' => env('NUBEFACT_RUC_PIMENTEL', ''),
  ],
  '15' => [
    'api_url' => env('NUBEFACT_API_URL_JAEN', 'https://api.nubefact.com/api/v1/'),
    'token' => env('NUBEFACT_TOKEN_JAEN', ''),
    'ruc' => env('NUBEFACT_RUC_JAEN', ''),
  ],
  '18' => [
    'api_url' => env('NUBEFACT_API_URL_PIURA', 'https://api.nubefact.com/api/v1/'),
    'token' => env('NUBEFACT_TOKEN_PIURA', ''),
    'ruc' => env('NUBEFACT_RUC_PIURA', ''),
  ],
  '19' => [
    'api_url' => env('NUBEFACT_API_URL_CAJAMARCA', 'https://api.nubefact.com/api/v1/'),
    'token' => env('NUBEFACT_TOKEN_CAJAMARCA', ''),
    'ruc' => env('NUBEFACT_RUC_CAJAMARCA', ''),
  ],
  '29' => [
    'api_url' => env('NUBEFACT_API_URL_SALAVERRY', 'https://api.nubefact.com/api/v1/'),
    'token' => env('NUBEFACT_TOKEN_SALAVERRY', ''),
    'ruc' => env('NUBEFACT_RUC_SALAVERRY', ''),
  ],
  '30' => [
    'api_url' => env('NUBEFACT_API_URL_BOLOGNESI', 'https://api.nubefact.com/api/v1/'),
    'token' => env('NUBEFACT_TOKEN_BOLOGNESI', ''),
    'ruc' => env('NUBEFACT_RUC_BOLOGNESI', ''),
  ],
  '31' => [
    'api_url' => env('NUBEFACT_API_URL_TUMBES', 'https://api.nubefact.com/api/v1/'),
    'token' => env('NUBEFACT_TOKEN_TUMBES', ''),
    'ruc' => env('NUBEFACT_RUC_TUMBES', ''),
  ],


  /*
  |--------------------------------------------------------------------------
  | Environment
  |--------------------------------------------------------------------------
  |
  | Entorno de ejecución: 'production' o 'demo'
  | En demo se usa la URL de pruebas de Nubefact
  |
  */

  'environment' => env('NUBEFACT_ENVIRONMENT', 'demo'),

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
