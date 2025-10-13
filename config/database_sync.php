<?php

use App\Models\ap\ApCommercialMasters;
use App\Models\ap\comercial\BusinessPartners;
use App\Models\ap\comercial\VehiclePurchaseOrder;
use App\Models\ap\configuracionComercial\vehiculo\ApModelsVn;
use App\Models\ap\maestroGeneral\TaxClassTypes;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\gp\gestionsistema\Company;

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
      'enabled' => env('SYNC_DBTP_ENABLED', false),
      'connection' => 'dbtp',
      'table' => 'neInTbCliente', // Nombre de la tabla en la BD externa
      'mapping' => [
        'EmpresaId' => fn($data) => Company::AP_DYNAMICS,
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
        'update' => false,  // Sincroniza cuando se actualiza un registro
        'delete' => false,  // Sincroniza cuando se elimina un registro
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

  // Configuración para la entidad "business_partners_ap_supplier"
  'business_partners_ap_supplier' => [
    // Primera base de datos externa (GPIN)
    'dbtp' => [
      'enabled' => env('SYNC_DBTP_ENABLED', false),
      'connection' => 'dbtp',
      'table' => 'neInTbProveedor', // Nombre de la tabla en la BD externa
      'mapping' => [
        'EmpresaId' => fn($data) => Company::AP_DYNAMICS,
        'Proveedor' => fn($data) => $data['num_doc'],
        'Nombre' => fn($data) => $data['full_name'],
        'NombreCorto' => fn($data) => $data['full_name'],
        'TitularCheque' => fn($data) => '',
        'ClaseId' => fn($data) => TaxClassTypes::find($data['tax_class_type_id'])?->dyn_code,
        'CondicionPagoId' => fn($data) => 'CONTADO',
        'DireccionId' => fn($data) => 'FISCAL',
        'TipoDocumentoId' => fn($data) => ApCommercialMasters::find($data['document_type_id'])?->description,
        'NumeroDocumento' => fn($data) => $data['num_doc'],
        'TipoContribuyenteId' => fn($data) => ApCommercialMasters::find($data['type_person_id'])?->code ?? '01',
        'RazonSocial' => fn($data) => $data['full_name'],
        'NombreComercial' => fn($data) => $data['full_name'],
        'ApellidoPaterno' => fn($data) => $data['paternal_surname'] ?? '',
        'ApellidoMaterno' => fn($data) => $data['maternal_surname'] ?: '',
        'PrimerNombre' => fn($data) => $data['first_name'] ?? '',
        'SegundoNombre' => fn($data) => $data['middle_name'] ?? '',
        'ProcesoEstado' => 0,
        'ProcesoError' => 0,
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
        'update' => false,  // Sincroniza cuando se actualiza un registro
        'delete' => false,  // Sincroniza cuando se elimina un registro
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
      'enabled' => env('SYNC_DBTP_ENABLED', false),
      'connection' => 'dbtp',
      'table' => 'neInTbClienteDireccion',
      'mapping' => [
        'EmpresaId' => fn($data) => Company::AP_DYNAMICS,
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
        'Telefono1' => fn($data) => $data['phone'] ?? '',
        'Telefono2' => fn($data) => '',
        'Telefono3' => fn($data) => '',
        'Fax' => fn($data) => '',
        'PlanImpuesto' => fn($data) => BusinessPartners::find($data['id'])->taxClassType->tax_class,
        'MetodoEnvio' => fn($data) => BusinessPartners::DYNAMICS_CLIENT,
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
        'update' => false,
        'delete' => false,
      ],
    ],
  ],

  // Configuración para la entidad "business_partners_directions_ap_supplier"
  'business_partners_directions_ap_supplier' => [
    'dbtp' => [
      'enabled' => env('SYNC_DBTP_ENABLED', false),
      'connection' => 'dbtp',
      'table' => 'neInTbProveedorDireccion',
      'mapping' => [
        'EmpresaId' => fn($data) => Company::AP_DYNAMICS,
        'Proveedor' => fn($data) => $data['num_doc'],
        'DireccionId' => fn($data) => 'FISCAL',
        'Contacto' => fn($data) => '',
        'Direccion' => fn($data) => $data['direction'],
        'Ciudad' => fn($data) => '',
        'Estado' => 0,
        'CodigoPostal' => fn($data) => '',
        'Pais' => fn($data) => '',
        'Telefono1' => fn($data) => $data['phone'] ?? '',
        'Telefono2' => fn($data) => $data['secondary_phone'] ?? '',
        'Telefono3' => fn($data) => '',
        'Fax' => fn($data) => '',
        'PlanImpuesto' => fn($data) => BusinessPartners::find($data['id'])->taxClassType->tax_class,
        'MetodoEnvio' => fn($data) => BusinessPartners::DYNAMICS_SUPPLIER,
        'CorreoElectronico' => fn($data) => '',
        'ProcesoEstado' => 0,
        'ProcesoError' => 0,
      ],
      'optional_mapping' => [
      ],
      'sync_mode' => 'insert',
      'unique_key' => 'Proveedor',
      'actions' => [
        'create' => true,
        'update' => false,
        'delete' => false,
      ],
    ],
  ],

  // Configuración para la entidad "ap_purchase_order"
  'ap_vehicle_purchase_order' => [
    'dbtp' => [
      'enabled' => env('SYNC_DBTP_ENABLED', false),
      'connection' => 'dbtp',
      'table' => 'neInTbOrdenCompra',
      'mapping' => [
        'EmpresaId' => fn($data) => Company::AP_DYNAMICS,
        'OrdenCompraId' => fn($data) => $data['number'],
        'ProveedorId' => fn($data) => BusinessPartners::find($data['supplier_id'])->num_doc,
        'FechaEmision' => fn($data) => $data['emission_date'],
        'MonedaId' => fn($data) => TypeCurrency::find($data['currency_id'])->code,
        'TipoTasaId' => fn($data) => VehiclePurchaseOrder::find($data['id'])->exchangeRate->type,
        'TasaCambio' => fn($data) => VehiclePurchaseOrder::find($data['id'])->exchangeRate->rate,
        'PlanImpuestoId' => fn($data) => VehiclePurchaseOrder::find($data['id'])->supplier->taxClassType->tax_class,
        'UsuarioId' => fn($data) => 'USUGP',
        'Procesar' => 1,
        'ProcesoEstado' => 0,
        'ProcesoError' => fn($data) => '',
      ],
      'optional_mapping' => [
      ],
      'sync_mode' => 'insert',
      'unique_key' => 'OrdenCompraId',
      'actions' => [
        'create' => true,
        'update' => false,
        'delete' => false, // Por ejemplo, no sincronizar eliminaciones
      ],
    ]
  ],

  // Configuración para la entidad "ap_purchase_order_det"
  'ap_vehicle_purchase_order_det' => [
    'dbtp' => [
      'enabled' => env('SYNC_DBTP_ENABLED', false),
      'connection' => 'dbtp',
      'table' => 'neInTbOrdenCompraDet',
      'mapping' => [
        'EmpresaId' => fn($data) => Company::AP_DYNAMICS,
        'OrdenCompraId' => fn($data) => $data['number'],
        'Linea' => 1, // TODO: Aquí deberías implementar la lógica para obtener la línea correcta
        'ArticuloId' => fn($data) => ApModelsVn::find($data['ap_models_vn_id'])->code,
        'SitioId' => fn($data) => Warehouse::find($data['warehouse_id'])->dyn_code,
        'UnidadMedidaId' => fn($data) => 'UND', // TODO: Asumiendo que siempre es 'UND', ajusta según sea necesario
        'Cantidad' => 1, // TODO: Aquí deberías implementar la lógica para obtener la cantidad correcta
        'CostoUnitario' => fn($data) => $data['subtotal'],
        'CuentaNumeroInventario' => fn($data) => '',
        'CodigoDimension1' => fn($data) => '',
        'CodigoDimension2' => fn($data) => '',
        'CodigoDimension3' => fn($data) => '',
        'CodigoDimension4' => fn($data) => '',
        'CodigoDimension5' => fn($data) => '',
        'CodigoDimension6' => fn($data) => '',
        'CodigoDimension7' => fn($data) => '',
        'CodigoDimension8' => fn($data) => '',
        'CodigoDimension9' => fn($data) => '',
        'CodigoDimension10' => fn($data) => ''
      ],
      'optional_mapping' => [
      ],
      'sync_mode' => 'insert',
      'unique_key' => 'OrdenCompraId',
      'actions' => [
        'create' => true,
        'update' => false,
        'delete' => false, // Por ejemplo, no sincronizar eliminaciones
      ],
    ]
  ],

  'ap_vehicle_purchase_order_reception' => [
    'dbtp' => [
      'enabled' => env('SYNC_DBTP_ENABLED', false),
      'connection' => 'dbtp',
      'table' => 'neInTbRecepcion',
      'mapping' => [
        'EmpresaId' => fn($data) => Company::AP_DYNAMICS,
        'RecepcionId' => fn($data) => $data['number_guide'],
        'ProveedorId' => fn($data) => BusinessPartners::find($data['supplier_id'])->num_doc,
        'FechaEmision' => fn($data) => $data['emission_date'],
        'FechaContable' => fn($data) => $data['emission_date'],
        'TipoComprobanteId' => fn($data) => 'GRM',
        'Serie' => fn($data) => $data['invoice_series'],
        'Correlativo' => fn($data) => $data['invoice_number'],
        'Procesar' => 1,
        'ProcesoEstado' => 0,
        'ProcesoError' => fn($data) => '',
        'FechaProceso' => fn($data) => now(),
      ],
      'optional_mapping' => [
      ],
      'sync_mode' => 'insert',
      'unique_key' => 'RecepcionId',
      'actions' => [
        'create' => true,
        'update' => false,
        'delete' => false, // Por ejemplo, no sincronizar eliminaciones
      ],
    ]
  ],

  'ap_vehicle_purchase_order_reception_det' => [
    'dbtp' => [
      'enabled' => env('SYNC_DBTP_ENABLED', false),
      'connection' => 'dbtp',
      'table' => 'neInTbRecepcionDt',
      'mapping' => [
        'EmpresaId' => fn($data) => Company::AP_DYNAMICS,
        'RecepcionId' => fn($data) => $data['number_guide'],
        'Linea' => 1,
        'OrdenCompraId' => fn($data) => $data['number'],
        'LineaOC' => fn($data) => '',
        'ArticuloId' => fn($data) => ApModelsVn::find($data['ap_models_vn_id'])->code,
        'SitioId' => fn($data) => Warehouse::find($data['warehouse_id'])->code,
        'UnidadMedidaId' => fn($data) => 'UND', // TODO: Asumiendo que siempre es 'UND', ajusta según sea necesario
        'Cantidad' => 1, // TODO: Aquí deberías implementar la lógica para obtener la cantidad correcta
      ],
      'optional_mapping' => [
      ],
      'sync_mode' => 'insert',
      'unique_key' => 'RecepcionId',
      'actions' => [
        'create' => true,
        'update' => false,
        'delete' => false, // Por ejemplo, no sincronizar eliminaciones
      ],
    ]
  ],

  'ap_vehicle_purchase_order_reception_det_s' => [
    'dbtp' => [
      'enabled' => env('SYNC_DBTP_ENABLED', false),
      'connection' => 'dbtp',
      'table' => 'neInTbRecepcionDtS',
      'mapping' => [
        'EmpresaId' => fn($data) => Company::AP_DYNAMICS,
        'RecepcionId' => fn($data) => $data['number_guide'],
        'Linea' => 1, // TODO: Aquí deberías implementar la lógica para obtener la línea correcta
        'Serie' => fn($data) => $data['vin'],
        'ArticuloId' => fn($data) => ApModelsVn::find($data['ap_models_vn_id'])->code,
        'DatoUsuario1' => fn($data) => $data['vin'],
        'DatoUsuario2' => fn($data) => $data['vin'],
      ],
      'optional_mapping' => [
      ],
      'sync_mode' => 'insert',
      'unique_key' => 'Serie',
      'actions' => [
        'create' => true,
        'update' => false,
        'delete' => false, // Por ejemplo, no sincronizar eliminaciones
      ],
    ]
  ],

//  ENVIAR UN MODEL_VN_RESOURCE A 'neInTbArticulo'
  'article_model' => [
    'dbtp' => [
      'enabled' => env('SYNC_DBTP_ENABLED', false),
      'connection' => 'dbtp',
      'table' => 'neInTbArticulo',
      'mapping' => [
        'EmpresaId' => fn($data) => Company::AP_DYNAMICS,
        'Articulo' => fn($data) => $data['code'],
        'Nombre' => fn($data) => $data['version'],
        'DescripcionBreve' => fn($data) => $data['version'],
        'DescripcionGenerica' => fn($data) => $data['version'],
        'Nota' => fn($data) => '',
        'ClaseArticulo' => fn($data) => $data['class_dyn'],
        'PlanUnidadMedida' => fn($data) => 'UNIDAD',
        'CostoEstandar' => 0,
        'CostoActual' => 0,
        'PesoEnvio' => 0,
        'Seguimiento' => 2, // PARA VEHICULOS ES 2 Y PARA REPUESTOS ES 1
        'Sitio' => fn($data) => '',
        'UnidadMedidaCompra' => fn($data) => 'UND',
        'MetodoPrecio' => 1,
        'UnidadMedidaVenta' => fn($data) => '',
        'NivelPrecio' => fn($data) => 'LISTA',
        'GrupoPrecio' => fn($data) => 'GRUPO',
        'CategoriaArticulo1' => fn($data) => $data['marca_dyn'],
        'CategoriaArticulo2' => fn($data) => $data['family_dyn'],
        'CategoriaArticulo3' => fn($data) => '',
        'CategoriaArticulo4' => fn($data) => '',
        'CategoriaArticulo5' => fn($data) => '',
        'CategoriaArticulo6' => fn($data) => '',
        'CodigoABC' => 1,
        'TipoArticulo' => 1,
        'DetraccionId' => fn($data) => '',
        'CuentaInventario' => fn($data) => '',
        'CuentaContrapartida' => fn($data) => '',
        'ProcesoEstado' => 0,
        'ProcesoError' => fn($data) => '',
      ],
      'optional_mapping' => [
      ],
      'sync_mode' => 'insert',
      'unique_key' => 'Articulo',
      'actions' => [
        'create' => true,
        'update' => false,
        'delete' => false, // Por ejemplo, no sincronizar eliminaciones
      ],
    ]
  ],
];
