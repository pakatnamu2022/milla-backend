<?php

return [

  /*
  |--------------------------------------------------------------------------
  | Third Party Services
  |--------------------------------------------------------------------------
  |
  | This file is for storing the credentials for third party services such
  | as Mailgun, Postmark, AWS and more. This file provides the de facto
  | location for this type of information, allowing packages to have
  | a conventional file to locate the various service credentials.
  |
  */

  'postmark' => [
    'token' => env('POSTMARK_TOKEN'),
  ],

  'resend' => [
    'key' => env('RESEND_KEY'),
  ],

  'ses' => [
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
  ],

  'slack' => [
    'notifications' => [
      'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
      'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
    ],
  ],

  'factiliza' => [
    'base_url' => env('FACTILIZA_BASE_URL', 'https://api.factiliza.com/v1'),
    'token' => env('FACTILIZA_TOKEN'),
    'timeout' => env('FACTILIZA_TIMEOUT', 30),
  ],

  'document_validation' => [
    'cache_ttl' => env('DOCUMENT_VALIDATION_CACHE_TTL', 604800), // 1 week in seconds
    'default_provider' => env('DOCUMENT_VALIDATION_PROVIDER', 'factiliza'),

    // Provider mapping por tipo de documento
    'provider_mapping' => [
      // Ejemplos de configuración:
      //'dni' => 'reniec_direct',    // DNI usa ReniecDirect
      // 'ruc' => 'sunat_direct',     // RUC usa SunatDirect
      // 'license' => 'factiliza',    // Licencias usan Factiliza
      // 'ce' => 'migraciones_direct', // CE usa MigracionesDirect
      // Si no está mapeado, usa default_provider
    ],
  ],

];
