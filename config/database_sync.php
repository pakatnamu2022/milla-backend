<?php

use App\Models\ap\ApMasters;
use App\Models\ap\comercial\BusinessPartners;
use App\Models\ap\compras\PurchaseOrder;
use App\Models\ap\configuracionComercial\vehiculo\ApModelsVn;
use App\Models\ap\maestroGeneral\TaxClassTypes;
use App\Models\ap\maestroGeneral\TypeCurrency;
use App\Models\ap\maestroGeneral\Warehouse;
use App\Models\gp\gestionsistema\Company;
use Carbon\Carbon;

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

  /*
  |--------------------------------------------------------------------------
  | Configuración Global
  |--------------------------------------------------------------------------
  */
  'enabled' => env('SYNC_DBTP_ENABLED', false),

  /*
  |--------------------------------------------------------------------------
  | Configuraciones de Entidades
  |--------------------------------------------------------------------------
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
        'TipoDocumento' => fn($data) => ApMasters::find($data['document_type_id'])?->description ?? '',
        'ClaseCliente' => fn($data) => TaxClassTypes::find($data['tax_class_type_id'])?->dyn_code ?? '',
        'num_doc' => 'NumeroDocumento',
        'Contribuyente' => fn($data) => ApMasters::find($data['type_person_id'])?->code ?? '01',
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
        'EmpresaId' => fn($data) => substr(Company::AP_DYNAMICS, 0, 5),
        'Proveedor' => fn($data) => substr($data['num_doc'], 0, 50),
        'Nombre' => fn($data) => substr($data['full_name'], 0, 200),
        'NombreCorto' => fn($data) => substr($data['full_name'], 0, 100),
        'TitularCheque' => fn($data) => '',
        'ClaseId' => fn($data) => substr(TaxClassTypes::find($data['supplier_tax_class_id'])?->dyn_code ?? '', 0, 50),
        'CondicionPagoId' => fn($data) => 'CONTADO',
        'DireccionId' => fn($data) => 'FISCAL',
        'TipoDocumentoId' => fn($data) => substr(ApMasters::find($data['document_type_id'])?->description ?? '', 0, 50),
        'NumeroDocumento' => fn($data) => substr($data['num_doc'], 0, 50),
        'TipoContribuyenteId' => fn($data) => substr(ApMasters::find($data['type_person_id'])?->code ?? '01', 0, 50),
        'RazonSocial' => fn($data) => substr($data['full_name'], 0, 200),
        'NombreComercial' => fn($data) => substr($data['full_name'], 0, 200),
        'ApellidoPaterno' => fn($data) => substr($data['paternal_surname'] ?? '', 0, 50),
        'ApellidoMaterno' => fn($data) => substr($data['maternal_surname'] ?? '', 0, 50),
        'PrimerNombre' => fn($data) => substr($data['first_name'] ?? '', 0, 50),
        'SegundoNombre' => fn($data) => substr($data['middle_name'] ?? '', 0, 50),
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
        'PlanImpuesto' => fn($data) => BusinessPartners::find($data['id'])->supplierTaxClassType->tax_class,
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

  // Configuración para la entidad "ap_purchase_order" genérica
  'ap_purchase_order' => [
    'dbtp' => [
      'enabled' => env('SYNC_DBTP_ENABLED', false),
      'connection' => 'dbtp',
      'table' => 'neInTbOrdenCompra',
      'mapping' => [
        'EmpresaId' => fn($data) => $data['EmpresaId'],
        'OrdenCompraId' => fn($data) => $data['OrdenCompraId'],
        'ProveedorId' => fn($data) => $data['ProveedorId'],
        'FechaEmision' => fn($data) => $data['FechaEmision'],
        'MonedaId' => fn($data) => $data['MonedaId'],
        'TipoTasaId' => fn($data) => $data['TipoTasaId'],
        'TasaCambio' => fn($data) => $data['TasaCambio'],
        'PlanImpuestoId' => fn($data) => $data['PlanImpuestoId'],
        'UsuarioId' => fn($data) => $data['UsuarioId'],
        'Procesar' => fn($data) => $data['Procesar'],
        'ProcesoEstado' => fn($data) => $data['ProcesoEstado'],
        'ProcesoError' => fn($data) => $data['ProcesoError'],
      ],
      'optional_mapping' => [
      ],
      'sync_mode' => 'insert',
      'unique_key' => 'OrdenCompraId',
      'actions' => [
        'create' => true,
        'update' => true,
        'delete' => false,
      ],
    ]
  ],

  // Configuración para la entidad "ap_purchase_order_item" (Detalle de OC genérica)
  'ap_purchase_order_item' => [
    'dbtp' => [
      'enabled' => env('SYNC_DBTP_ENABLED', false),
      'connection' => 'dbtp',
      'table' => 'neInTbOrdenCompraDet',
      'mapping' => [
        'EmpresaId' => fn($data) => $data['EmpresaId'],
        'OrdenCompraId' => fn($data) => $data['OrdenCompraId'],
        'Linea' => fn($data) => $data['Linea'],
        'ArticuloId' => fn($data) => $data['ArticuloId'],
        'SitioId' => fn($data) => $data['SitioId'],
        'UnidadMedidaId' => fn($data) => $data['UnidadMedidaId'],
        'Cantidad' => fn($data) => $data['Cantidad'],
        'CostoUnitario' => fn($data) => $data['CostoUnitario'],
        'CuentaNumeroInventario' => fn($data) => $data['CuentaNumeroInventario'] ?? '',
        'CodigoDimension1' => fn($data) => $data['CodigoDimension1'] ?? '',
        'CodigoDimension2' => fn($data) => $data['CodigoDimension2'] ?? '',
        'CodigoDimension3' => fn($data) => $data['CodigoDimension3'] ?? '',
        'CodigoDimension4' => fn($data) => $data['CodigoDimension4'] ?? '',
        'CodigoDimension5' => fn($data) => $data['CodigoDimension5'] ?? '',
        'CodigoDimension6' => fn($data) => $data['CodigoDimension6'] ?? '',
        'CodigoDimension7' => fn($data) => $data['CodigoDimension7'] ?? '',
        'CodigoDimension8' => fn($data) => $data['CodigoDimension8'] ?? '',
        'CodigoDimension9' => fn($data) => $data['CodigoDimension9'] ?? '',
        'CodigoDimension10' => fn($data) => $data['CodigoDimension10'] ?? ''
      ],
      'optional_mapping' => [
      ],
      'sync_mode' => 'upsert',
      'unique_key' => ['EmpresaId', 'OrdenCompraId', 'Linea'],
      'actions' => [
        'create' => true,
        'update' => true,
        'delete' => false,
      ],
    ]
  ],

//  Configuración para la entidad "ap_vehicle_purchase_order_reception"
  'ap_vehicle_purchase_order_reception' => [
    'dbtp' => [
      'enabled' => env('SYNC_DBTP_ENABLED', false),
      'connection' => 'dbtp',
      'table' => 'neInTbRecepcion',
      'mapping' => [
        'EmpresaId' => fn($data) => $data['EmpresaId'],
        'RecepcionId' => fn($data) => $data['RecepcionId'],
        'ProveedorId' => fn($data) => $data['ProveedorId'],
        'FechaEmision' => fn($data) => $data['FechaEmision'],
        'FechaContable' => fn($data) => $data['FechaContable'],
        'TipoComprobanteId' => fn($data) => $data['TipoComprobanteId'],
        'Serie' => fn($data) => $data['Serie'],
        'Correlativo' => fn($data) => $data['Correlativo'],
        'Procesar' => fn($data) => $data['Procesar'],
        'ProcesoEstado' => fn($data) => $data['ProcesoEstado'],
        'ProcesoError' => fn($data) => $data['ProcesoError'],
        'FechaProceso' => fn($data) => $data['FechaProceso'],
      ],
      'optional_mapping' => [
      ],
      'sync_mode' => 'insert',
      'unique_key' => 'RecepcionId',
      'actions' => [
        'create' => true,
        'update' => true,
        'delete' => false,
      ],
    ]
  ],

//  Configuración para la entidad "ap_vehicle_purchase_order_reception_det"
  'ap_vehicle_purchase_order_reception_det' => [
    'dbtp' => [
      'enabled' => env('SYNC_DBTP_ENABLED', false),
      'connection' => 'dbtp',
      'table' => 'neInTbRecepcionDt',
      'mapping' => [
        'EmpresaId' => fn($data) => $data['EmpresaId'],
        'RecepcionId' => fn($data) => $data['RecepcionId'],
        'Linea' => fn($data) => $data['Linea'],
        'OrdenCompraId' => fn($data) => $data['OrdenCompraId'],
        'LineaOC' => fn($data) => $data['LineaOC'],
        'ArticuloId' => fn($data) => $data['ArticuloId'],
        'SitioId' => fn($data) => $data['SitioId'],
        'UnidadMedidaId' => fn($data) => $data['UnidadMedidaId'],
        'Cantidad' => fn($data) => $data['Cantidad'],
      ],
      'optional_mapping' => [
      ],
      'sync_mode' => 'insert',
      'unique_key' => 'RecepcionId',
      'actions' => [
        'create' => true,
        'update' => true,
        'delete' => false,
      ],
    ]
  ],

//  Configuración para la entidad "ap_vehicle_purchase_order_reception_det_s"
  'ap_vehicle_purchase_order_reception_det_s' => [
    'dbtp' => [
      'enabled' => env('SYNC_DBTP_ENABLED', false),
      'connection' => 'dbtp',
      'table' => 'neInTbRecepcionDtS',
      'mapping' => [
        'EmpresaId' => fn($data) => $data['EmpresaId'],
        'RecepcionId' => fn($data) => $data['RecepcionId'],
        'Linea' => fn($data) => $data['Linea'],
        'Serie' => fn($data) => $data['Serie'],
        'ArticuloId' => fn($data) => $data['ArticuloId'],
        'DatoUsuario1' => fn($data) => $data['DatoUsuario1'],
        'DatoUsuario2' => fn($data) => $data['DatoUsuario2'],
      ],
      'optional_mapping' => [
      ],
      'sync_mode' => 'insert',
      'unique_key' => 'Serie',
      'actions' => [
        'create' => true,
        'update' => true,
        'delete' => false,
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

//  ENVIAR UN PRODUCT_ARTICLE_RESOURCE A 'neInTbArticulo'
  'article_product' => [
    'dbtp' => [
      'enabled' => env('SYNC_DBTP_ENABLED', false),
      'connection' => 'dbtp',
      'table' => 'neInTbArticulo',
      'mapping' => [
        'EmpresaId' => fn($data) => Company::AP_DYNAMICS,
        'Articulo' => fn($data) => $data['code'],
        'Nombre' => fn($data) => substr($data['name'], 0, 100),
        'DescripcionBreve' => fn($data) => substr($data['description'] ?? $data['name'], 0, 100),
        'DescripcionGenerica' => fn($data) => substr($data['description'] ?? $data['name'], 0, 200),
        'Nota' => fn($data) => '',
        'ClaseArticulo' => fn($data) => $data['class_dyn'] ?? '',
        'PlanUnidadMedida' => fn($data) => 'UNIDAD',
        'CostoEstandar' => fn($data) => $data['cost_price'] ?? 0,
        'CostoActual' => fn($data) => $data['cost_price'] ?? 0,
        'PesoEnvio' => 0,
        'Seguimiento' => 1, // PARA VEHICULOS ES 2 Y PARA REPUESTOS/PRODUCTOS ES 1
        'Sitio' => fn($data) => '',
        'UnidadMedidaCompra' => fn($data) => $data['unit_measurement'] ?? 'UND',
        'MetodoPrecio' => 1,
        'UnidadMedidaVenta' => fn($data) => $data['unit_measurement'] ?? 'UND',
        'NivelPrecio' => fn($data) => 'LISTA',
        'GrupoPrecio' => fn($data) => 'GRUPO',
        'CategoriaArticulo1' => fn($data) => $data['brand_dyn'] ?? '',
        'CategoriaArticulo2' => fn($data) => $data['category'] ?? '',
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
        'delete' => false,
      ],
    ]
  ],

//  Configuración para la entidad "ap_sales_receipts"
  'ap_sales_receipts' => [
    'dbtp' => [
      'enabled' => env('SYNC_DBTP_ENABLED', false),
      'connection' => 'dbtp',
      'table' => 'neInTbOrdenCompra',
      'mapping' => [
        'EmpresaId' => fn($data) => Company::AP_DYNAMICS,
        'TipoId' => 1, //  TODO:FXX1
        'DocumentoId' => 1, //  TODO:FXX1-00000003
        'LoteId' => 1, // TODO:74202439
        'ClienteId' => 1, // TODO:06740217
        'TerritorioId' => fn($data) => '',
        'VendedorId' => fn($data) => '',
        'FechaEmision' => fn($data) => $data['emission_date'],
        'FechaContable' => fn($data) => $data['emission_date'],
        'TipoComprobanteId' => 1, // TODO:FAC o BOL
        'Serie' => 1, // TODO: FXX1
        'Correlativo' => 1, // TODO: 4002
        'MonedaId' => fn($data) => TypeCurrency::find($data['currency_id'])->code,
        'TipoTasaId' => fn($data) => PurchaseOrder::find($data['id'])->exchangeRate->type,
        'TasaCambio' => fn($data) => PurchaseOrder::find($data['id'])->exchangeRate->rate,
        'PlanImpuestoId' => fn($data) => PurchaseOrder::find($data['id'])->supplier?->supplierTaxClassType?->tax_class ?? throw new \Exception("Supplier or TaxClassType not found for PO {$data['id']}"),
        'TipoOperacionDetraccionId' => fn($data) => '01',  // TODO: confirmar
        'CategoriaDetraccionId' => 1, // TODO: vacio o confirmar
        'SitioPredeterminadoId' => 1, // TODO: ALM-VN-CIX
        'UsuarioId' => fn($data) => 'USUGP',
        'Procesar' => 1,
        'ProcesoEstado' => 0,
        'ProcesoError' => fn($data) => '',
        'FechaProceso' => 1,
        'Total' => 1,
        'Detraccion' => 1,
        'EsAnticipo' => 1,
        'ApAnticipo' => 1,
      ],
      'optional_mapping' => [
      ],
      'sync_mode' => 'insert',
      'unique_key' => 'OrdenCompraId',
      'actions' => [
        'create' => true,
        'update' => true,
        'delete' => false,
      ],
    ]
  ],


  //  Configuración para la entidad "inventory_transfer"
  'inventory_transfer' => [
    'dbtp' => [
      'enabled' => env('SYNC_DBTP_ENABLED', false),
      'connection' => 'dbtp',
      'table' => 'neInTbTransferenciaInventario',
      'mapping' => [
        'EmpresaId' => fn($data) => Company::AP_DYNAMICS,
        'TransferenciaId' => fn($data) => $data['TransferenciaId'],
        'FechaEmision' => fn($data) => $data['FechaEmision'],
        'FechaContable' => fn($data) => $data['FechaContable'],
        'Procesar' => fn($data) => $data['Procesar'],
        'ProcesoEstado' => fn($data) => $data['ProcesoEstado'],
        'ProcesoError' => fn($data) => $data['ProcesoError'],
        'FechaProceso' => fn($data) => $data['FechaProceso'],
      ],
      'optional_mapping' => [
      ],
      'sync_mode' => 'insert',
      'unique_key' => 'TransferenciaId',
      'actions' => [
        'create' => true,
        'update' => false,
        'delete' => false,
      ],
    ]
  ],

  //  Configuración para la entidad "inventory_transfer_dt"
  'inventory_transfer_dt' => [
    'dbtp' => [
      'enabled' => env('SYNC_DBTP_ENABLED', false),
      'connection' => 'dbtp',
      'table' => 'neInTbTransferenciaInventarioDet',
      'mapping' => [
        'EmpresaId' => fn($data) => Company::AP_DYNAMICS,
        'TransferenciaId' => fn($data) => $data['TransferenciaId'],
        'Linea' => fn($data) => $data['Linea'],
        'ArticuloId' => fn($data) => $data['ArticuloId'],
        'Motivo' => fn($data) => $data['Motivo'],
        'UnidadMedidaId' => fn($data) => $data['UnidadMedidaId'],
        'Cantidad' => fn($data) => $data['Cantidad'],
        'AlmacenId_Ini' => fn($data) => $data['AlmacenId_Ini'],
        'AlmacenId_Fin' => fn($data) => $data['AlmacenId_Fin'],
        'CuentaInventario' => fn($data) => $data['CuentaInventario'],
        'CuentaContrapartida' => fn($data) => $data['CuentaContrapartida'],
      ],
      'optional_mapping' => [
      ],
      'sync_mode' => 'insert',
      'unique_key' => 'TransferenciaId',
      'actions' => [
        'create' => true,
        'update' => false,
        'delete' => false,
      ],
    ]
  ],

  //  Configuración para la entidad "inventory_transfer_dts"
  'inventory_transfer_dts' => [
    'dbtp' => [
      'enabled' => env('SYNC_DBTP_ENABLED', false),
      'connection' => 'dbtp',
      'table' => 'neInTbTransferenciaInventarioDtS',
      'mapping' => [
        'EmpresaId' => fn($data) => Company::AP_DYNAMICS,
        'TransferenciaId' => fn($data) => $data['TransferenciaId'],
        'Linea' => fn($data) => $data['Linea'],
        'Serie' => fn($data) => $data['Serie'],
        'ArticuloId' => fn($data) => $data['ArticuloId'],
        'DatoUsuario1' => fn($data) => $data['DatoUsuario1'],
        'DatoUsuario2' => fn($data) => $data['DatoUsuario2'],
      ],
      'optional_mapping' => [
      ],
      'sync_mode' => 'insert',
      'unique_key' => 'TransferenciaId',
      'actions' => [
        'create' => true,
        'update' => false,
        'delete' => false,
      ],
    ]
  ],

  //  Configuración para la entidad "inventory_transaction"
  'inventory_transaction' => [
    'dbtp' => [
      'enabled' => env('SYNC_DBTP_ENABLED', false),
      'connection' => 'dbtp',
      'table' => 'neInTbTransaccionInventario',
      'mapping' => [
        'EmpresaId' => fn($data) => Company::AP_DYNAMICS,
        'TransaccionId' => fn($data) => $data['TransaccionId'],
        'FechaEmision' => fn($data) => $data['FechaEmision'],
        'FechaContable' => fn($data) => $data['FechaContable'],
        'Procesar' => fn($data) => $data['Procesar'],
        'ProcesoEstado' => fn($data) => $data['ProcesoEstado'],
        'ProcesoError' => fn($data) => $data['ProcesoError'],
        'FechaProceso' => fn($data) => $data['FechaProceso'],
      ],
      'optional_mapping' => [
      ],
      'sync_mode' => 'insert',
      'unique_key' => 'TransaccionId',
      'actions' => [
        'create' => true,
        'update' => false,
        'delete' => false,
      ],
    ]
  ],

  //  Configuración para la entidad "inventory_transaction_dt"
  'inventory_transaction_dt' => [
    'dbtp' => [
      'enabled' => env('SYNC_DBTP_ENABLED', false),
      'connection' => 'dbtp',
      'table' => 'neInTbTransaccionInventarioDet',
      'mapping' => [
        'EmpresaId' => fn($data) => Company::AP_DYNAMICS,
        'TransaccionId' => fn($data) => $data['TransaccionId'],
        'Linea' => fn($data) => $data['Linea'],
        'ArticuloId' => fn($data) => $data['ArticuloId'],
        'Motivo' => fn($data) => $data['Motivo'],
        'UnidadMedidaId' => fn($data) => $data['UnidadMedidaId'],
        'Cantidad' => fn($data) => $data['Cantidad'],
        'AlmacenId' => fn($data) => $data['AlmacenId'],
        'CostoUnitario' => fn($data) => $data['CostoUnitario'],
        'CuentaInventario' => fn($data) => $data['CuentaInventario'],
        'CuentaContrapartida' => fn($data) => $data['CuentaContrapartida'],
      ],
      'optional_mapping' => [
      ],
      'sync_mode' => 'insert',
      'unique_key' => 'TransaccionId',
      'actions' => [
        'create' => true,
        'update' => false,
        'delete' => false,
      ],
    ]
  ],

  //  Configuración para la entidad "inventory_transaction_dts"
  'inventory_transaction_dts' => [
    'dbtp' => [
      'enabled' => env('SYNC_DBTP_ENABLED', false),
      'connection' => 'dbtp',
      'table' => 'neInTbTransaccionInventarioDtS',
      'mapping' => [
        'EmpresaId' => fn($data) => Company::AP_DYNAMICS,
        'TransaccionId' => fn($data) => $data['TransaccionId'],
        'Linea' => fn($data) => $data['Linea'],
        'Serie' => fn($data) => $data['Serie'],
        'ArticuloId' => fn($data) => $data['ArticuloId'],
        'DatoUsuario1' => fn($data) => $data['DatoUsuario1'],
        'DatoUsuario2' => fn($data) => $data['DatoUsuario2'],
      ],
      'optional_mapping' => [
      ],
      'sync_mode' => 'insert',
      'unique_key' => 'TransaccionId',
      'actions' => [
        'create' => true,
        'update' => false,
        'delete' => false,
      ],
    ]
  ],

  //  Configuración para la entidad "sales_document" (Cabecera de Venta)
  'sales_document' => [
    'dbtp' => [
      'enabled' => env('SYNC_DBTP_ENABLED', false),
      'connection' => 'dbtp',
      'table' => 'neInTbVenta',
      'mapping' => [
        'EmpresaId' => fn($data) => $data['EmpresaId'],
        'TipoId' => fn($data) => $data['TipoId'],
        'DocumentoId' => fn($data) => $data['DocumentoId'],
        'LoteId' => fn($data) => $data['LoteId'],
        'ClienteId' => fn($data) => $data['ClienteId'],
        'TerritorioId' => fn($data) => $data['TerritorioId'],
        'VendedorId' => fn($data) => $data['VendedorId'],
        'FechaEmision' => fn($data) => $data['FechaEmision'],
        'FechaContable' => fn($data) => $data['FechaContable'],
        'TipoComprobanteId' => fn($data) => $data['TipoComprobanteId'],
        'Serie' => fn($data) => $data['Serie'],
        'Correlativo' => fn($data) => $data['Correlativo'],
        'MonedaId' => fn($data) => $data['MonedaId'],
        'TipoTasaId' => fn($data) => $data['TipoTasaId'],
        'TasaCambio' => fn($data) => $data['TasaCambio'],
        'PlanImpuestoId' => fn($data) => $data['PlanImpuestoId'],
        'TipoOperacionDetraccionId' => fn($data) => $data['TipoOperacionDetraccionId'] ?? '',
        'CategoriaDetraccionId' => fn($data) => $data['CategoriaDetraccionId'] ?? '',
        'SitioPredeterminadoId' => fn($data) => $data['SitioPredeterminadoId'],
        'UsuarioId' => fn($data) => $data['UsuarioId'],
        'Procesar' => fn($data) => $data['Procesar'],
        'ProcesoEstado' => fn($data) => $data['ProcesoEstado'],
        'ProcesoError' => fn($data) => $data['ProcesoError'],
        'FechaProceso' => fn($data) => $data['FechaProceso'],
        'Total' => fn($data) => $data['Total'],
        'Detraccion' => fn($data) => $data['Detraccion'],
        'EsAnticipo' => fn($data) => $data['EsAnticipo'],
        'ApAnticipo' => fn($data) => $data['ApAnticipo'],
      ],
      'optional_mapping' => [
      ],
      'sync_mode' => 'insert',
      'unique_key' => 'DocumentoId',
      'actions' => [
        'create' => true,
        'update' => false,
        'delete' => false,
      ],
    ]
  ],

  //  Configuración para la entidad "sales_document_detail" (Detalle de Venta)
  'sales_document_detail' => [
    'dbtp' => [
      'enabled' => env('SYNC_DBTP_ENABLED', false),
      'connection' => 'dbtp',
      'table' => 'neInTbVentaDt',
      'mapping' => [
        'EmpresaId' => fn($data) => $data['EmpresaId'],
        'DocumentoId' => fn($data) => $data['DocumentoId'],
        'Linea' => fn($data) => $data['Linea'],
        'ArticuloId' => fn($data) => $data['ArticuloId'],
        'ArticuloDescripcionCorta' => fn($data) => $data['ArticuloDescripcionCorta'],
        'ArticuloDescripcionLarga' => fn($data) => $data['ArticuloDescripcionLarga'],
        'SitioId' => fn($data) => $data['SitioId'],
        'UnidadMedidaId' => fn($data) => $data['UnidadMedidaId'],
        'Cantidad' => fn($data) => $data['Cantidad'],
        'PrecioUnitario' => fn($data) => $data['PrecioUnitario'],
        'DescuentoUnitario' => fn($data) => $data['DescuentoUnitario'],
        'PrecioTotal' => fn($data) => $data['PrecioTotal'],
      ],
      'optional_mapping' => [
      ],
      'sync_mode' => 'insert',
      'unique_key' => 'DocumentoId',
      'actions' => [
        'create' => true,
        'update' => false,
        'delete' => false,
      ],
    ]
  ],

  // Configuración para asientos contables - Cabecera
  'accounting_entry_header' => [
    'dbtp' => [
      'enabled' => env('SYNC_DBTP_ENABLED', false),
      'connection' => 'dbtp',
      'table' => 'neInTbIntegracionAsientoCab',
      'mapping' => [
        'Asiento' => fn($data) => $data['Asiento'],
        'EmpresaId' => fn($data) => $data['EmpresaId'],
        'LoteId' => fn($data) => $data['LoteId'],
        'Referencia' => fn($data) => $data['Referencia'],
        'Fecha' => fn($data) => $data['Fecha'],
        'MonedaId' => fn($data) => $data['MonedaId'],
        'TipoTasaId' => fn($data) => $data['TipoTasaId'],
        'TipoCambio' => fn($data) => $data['TipoCambio'],
        'Error' => fn($data) => $data['Error'],
        'Estado' => fn($data) => $data['Estado'],
        'FechaEstado' => fn($data) => $data['FechaEstado'],
      ],
      'optional_mapping' => [],
      'sync_mode' => 'insert',
      'unique_key' => 'Asiento',
      'actions' => [
        'create' => true,
        'update' => false,
        'delete' => false,
      ],
    ]
  ],

  // Configuración para asientos contables - Detalle
  'accounting_entry_detail' => [
    'dbtp' => [
      'enabled' => env('SYNC_DBTP_ENABLED', false),
      'connection' => 'dbtp',
      'table' => 'neInTbIntegracionAsientoDet',
      'mapping' => [
        'Asiento' => fn($data) => $data['Asiento'],
        'Linea' => fn($data) => $data['Linea'],
        'CuentaNumero' => fn($data) => $data['CuentaNumero'],
        'Debito' => fn($data) => $data['Debito'],
        'Credito' => fn($data) => $data['Credito'],
        'Descripcion' => fn($data) => $data['Descripcion'],
      ],
      'optional_mapping' => [],
      'sync_mode' => 'insert',
      'unique_key' => ['Asiento', 'Linea'],
      'actions' => [
        'create' => true,
        'update' => false,
        'delete' => false,
      ],
    ]
  ],
];
