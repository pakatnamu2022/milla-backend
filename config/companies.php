<?php

return [
  /*
  |--------------------------------------------------------------------------
  | Companies Logo Configuration
  |--------------------------------------------------------------------------
  |
  | Configuración de logos de empresas/áreas
  | Los logos se encuentran en /public/companies
  |
  */

  'logos' => [
    'gp' => [
      'name' => 'Gestión de Personas',
      'path' => '/companies/gplogo.png',
      'url' => asset('companies/gplogo.png'),
    ],
    'ap' => [
      'name' => 'Administración y Proyectos',
      'path' => '/companies/aplogo.png',
      'url' => asset('companies/aplogo.png'),
    ],
    'tp' => [
      'name' => 'Tecnología y Proyectos',
      'path' => '/companies/tplogo.png',
      'url' => asset('companies/tplogo.png'),
    ],
    'dp' => [
      'name' => 'Dirección y Presidencia',
      'path' => '/companies/dplogo.png',
      'url' => asset('companies/dplogo.png'),
    ],
  ],

  /*
  |--------------------------------------------------------------------------
  | Company Names
  |--------------------------------------------------------------------------
  */

  'names' => [
    'gp' => 'Gestión de Personas',
    'ap' => 'Administración y Proyectos',
    'tp' => 'Tecnología y Proyectos',
    'dp' => 'Dirección y Presidencia',
  ],

  /*
  |--------------------------------------------------------------------------
  | Default Company
  |--------------------------------------------------------------------------
  */

  'default' => 'gp',

];
