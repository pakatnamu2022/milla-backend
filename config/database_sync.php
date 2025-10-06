<?php

use App\Models\ap\ApCommercialMasters;
use App\Models\ap\maestroGeneral\TaxClassTypes;

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

  // Configuración para la entidad "business_partners"
  'business_partners' => [
    // Primera base de datos externa (GPIN)
    'dbtp' => [
      'enabled' => env('SYNC_DBTP_ENABLED', true),
      'connection' => 'dbtp',
      'table' => 'neInTbCliente', // Nombre de la tabla en la BD externa
      'mapping' => [
        'EmpresaId' => fn($data) => 'CTEST',
        'DireccionCliente' => fn($data) => 'FISCAL',
        'ProcesoEstado' => 0,
        'ProcesoError' => 0,

        'Cliente' => fn($data) => $data['num_doc'] ?? '',
        'Nombre' => fn($data) => $data['full_name'] ?? '',
        'NombreCorto' => fn($data) => substr($data['full_name'] ?? '', 0, 50),
        'full_name' => 'RazonSocial',
        'TipoDocumento' => fn($data) => ApCommercialMasters::find($data['document_type_id'])?->description ?? '',
        'ClaseCliente' => fn($data) => TaxClassTypes::find($data['tax_class_type_id'])?->dyn_code ?? '',
        'num_doc' => 'NumeroDocumento',
        'Contribuyente' => fn($data) => ApCommercialMasters::find($data['type_person_id'])?->code ?? '01',
        'ApellidoPaterno' => fn($data) => $data['paternal_surname'] ?? '',
        'ApellidoMaterno' => fn($data) => $data['maternal_surname'] ?: '',
        'PrimerNombre' => fn($data) => $data['first_name'] ?? '',
        'SegundoNombre' => fn($data) => $data['middle_name'] ?? '',
      ],

      // Columnas opcionales: solo se sincronizan si existen en los datos
      'optional_mapping' => [
//        'birth_date' => 'fecha_nacimiento',
//        'company_id' => 'empresa_id',
//        'district_id' => 'distrito_id',
      ],

      // Modo de sincronización: 'insert', 'update', 'upsert'
      'sync_mode' => 'insert',

      // Clave única para upsert (si el modo es 'upsert')
      'unique_key' => 'NumeroDocumento', // Columna en BD externa

      // Control de sincronización por acción
      // Si no se especifica 'actions', se asume que todas están habilitadas
      'actions' => [
        'create' => true,  // Sincroniza cuando se crea un registro
        'update' => true,  // Sincroniza cuando se actualiza un registro
        'delete' => true,  // Sincroniza cuando se elimina un registro
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

  // Configuración para la entidad "business_partners_directions"
  'business_partners_directions' => [
    'dbtp' => [
      'enabled' => env('SYNC_DBTP_ENABLED', true),
      'connection' => 'dbtp',
      'table' => 'neInTbClienteDireccion',
      'mapping' => [
        'EmpresaId' => fn($data) => 'CTEST',
        'Cliente' => fn($data) => $data['num_doc'] ?? '',
        'Direccion' => fn($data) => 'FISCAL',
        'Contacto' => fn($data) => '',
        'Direccion1' => fn($data) => $data['direction'] ?? '',
        'Direccion2' => fn($data) => $data['direction'] ?? '',
        'Direccion3' => fn($data) => $data['direction'] ?? '',
        'Ciudad' => fn($data) => '',
        'Estado' => 0,
        'CodigoPostal' => fn($data) => '',
        'Pais' => fn($data) => '',
        'Telefono1' => fn($data) => '',
        'Telefono2' => fn($data) => '',
        'Telefono3' => fn($data) => '',
        'Fax' => fn($data) => '',
        'PlanImpuesto' => fn($data) => '',
        'MetodoEnvio' => fn($data) => 'CLIENTES',
        'CorreoElectronico' => fn($data) => '',
        'PaginaWeb' => fn($data) => '',
        'ProcesoEstado' => 0,
        'ProcesoError' => 0,
      ],
      'optional_mapping' => [
      ],
      'sync_mode' => 'insert',
      'unique_key' => 'Cliente',
      'actions' => [
        'create' => true,
        'update' => true,
        'delete' => true,
      ],
    ],
  ],
];
