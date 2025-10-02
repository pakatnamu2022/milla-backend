<?php

return [
  /*
  |--------------------------------------------------------------------------
  | Database Synchronization Configuration
  |--------------------------------------------------------------------------
  |
  | Este archivo define las configuraciones de sincronización para múltiples
  | bases de datos externas. Puedes definir el mapeo de columnas y las
  | conexiones que se utilizarán para sincronizar datos.
  |
  */

  'business_partners' => [
    // Primera base de datos externa (GPIN)
    'dbtp' => [
      'enabled' => env('SYNC_DBTP_ENABLED', true),
      'connection' => 'dbtp',
      'table' => 'business_partners', // Nombre de la tabla en la BD externa
      'mapping' => [
        // 'columna_local' => 'columna_externa'
        // Si es null, usa el mismo nombre de columna
        // Si es un closure, ejecuta la función para obtener el valor

        'EmpresaId' => 'CTEST',
        'DireccionCliente' => "FISCAL",
        'ProcesoEstado' => 0,
        'ProcesoError' => 0,
        'id' => 'partner_id',
        'full_name' => 'nombre_completo',
        'num_doc' => 'documento',
        'email' => 'correo',
        'phone' => 'telefono',
        'direction' => 'direccion',
        'type' => 'tipo_socio',
        'status_gp' => 'estado',

        // Ejemplo de transformación con closure
        // 'created_at' => fn($data) => now()->format('Y-m-d H:i:s'),
        // 'Nombre' => fn($data) => substr($data['full_name'] ?? '', 0, 64),

        // Valores estáticos
        // 'source' => 'MILLA_BACKEND',
      ],

      // Columnas opcionales: solo se sincronizan si existen en los datos
      'optional_mapping' => [
        'birth_date' => 'fecha_nacimiento',
        'company_id' => 'empresa_id',
        'district_id' => 'distrito_id',
      ],

      // Modo de sincronización: 'insert', 'update', 'upsert'
      'sync_mode' => 'upsert',

      // Clave única para upsert (si el modo es 'upsert')
      'unique_key' => 'documento', // Columna en BD externa

      // Control de sincronización por acción
      // Si no se especifica 'actions', se asume que todas están habilitadas
      'actions' => [
        'create' => true,  // Sincroniza cuando se crea un registro
        'update' => true,  // Sincroniza cuando se actualiza un registro
        'delete' => true,  // Sincroniza cuando se elimina un registro
      ],
    ],

    // Segunda base de datos externa (GPTRP)
    'dbtp2' => [
      'enabled' => env('SYNC_DBTP2_ENABLED', true),
      'connection' => 'dbtp2',
      'table' => 'socios_comerciales',
      'mapping' => [
        'id' => 'id_socio',
        'full_name' => 'nombre',
        'num_doc' => 'nro_documento',
        'email' => 'email',
        'phone' => 'telefono',
        'type' => 'tipo',
      ],
      'optional_mapping' => [
        'direction' => 'direccion',
        'birth_date' => 'fecha_nac',
      ],
      'sync_mode' => 'upsert',
      'unique_key' => 'nro_documento',

      // Control de sincronización por acción
      'actions' => [
        'create' => false,  // NO sincroniza en create
        'update' => true,   // SÍ sincroniza en update
        'delete' => true,   // SÍ sincroniza en delete
      ],
    ],

    // Puedes agregar más conexiones aquí fácilmente
    // 'dbtp3' => [
    //     'enabled' => env('SYNC_DBTP3_ENABLED', false),
    //     'connection' => 'dbtp3',
    //     'table' => 'partners',
    //     'mapping' => [...],
    // ],
  ],

  // Configuración para otras entidades
  // 'otra_entidad' => [
  //     'dbtp' => [...],
  // ],
];
