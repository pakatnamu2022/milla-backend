<?php

return [
  /*
  |--------------------------------------------------------------------------
  | Acciones de Permisos Permitidas
  |--------------------------------------------------------------------------
  |
  | Define todas las acciones disponibles para permisos en el sistema.
  | Sincronizado con el frontend (PERMISSION_ACTIONS).
  |
  */

  'actions' => [
    'view' => [
      'label' => 'Ver',
      'description' => 'Permite visualizar información',
      'icon' => 'Eye',
      'policy_method' => 'view',
    ],

    'create' => [
      'label' => 'Crear',
      'description' => 'Permite crear nuevos registros',
      'icon' => 'Plus',
      'policy_method' => 'create',
    ],

    'update' => [
      'label' => 'Editar',
      'description' => 'Permite modificar registros existentes',
      'icon' => 'Pencil',
      'policy_method' => 'update',
    ],

    'delete' => [
      'label' => 'Eliminar',
      'description' => 'Permite eliminar o anular registros',
      'icon' => 'Trash2',
      'policy_method' => 'delete',
    ],

    'export' => [
      'label' => 'Exportar',
      'description' => 'Permite exportar datos',
      'icon' => 'Download',
      'policy_method' => 'export',
    ],

    'import' => [
      'label' => 'Importar',
      'description' => 'Permite importar datos',
      'icon' => 'Upload',
      'policy_method' => 'import',
    ],

    'authorize' => [
      'label' => 'Autorizar',
      'description' => 'Permite autorizar solicitudes o acciones',
      'icon' => 'CheckCircle',
      'policy_method' => 'authorize',
    ],

    'approve' => [
      'label' => 'Aprobar',
      'description' => 'Permite aprobar documentos o procesos',
      'icon' => 'ThumbsUp',
      'policy_method' => 'approve',
    ],

    'reject' => [
      'label' => 'Rechazar',
      'description' => 'Permite rechazar solicitudes',
      'icon' => 'ThumbsDown',
      'policy_method' => 'reject',
    ],

    'print' => [
      'label' => 'Imprimir',
      'description' => 'Permite imprimir documentos',
      'icon' => 'Printer',
      'policy_method' => 'print',
    ],

    'send' => [
      'label' => 'Enviar',
      'description' => 'Permite enviar información',
      'icon' => 'Send',
      'policy_method' => 'send',
    ],

    'duplicate' => [
      'label' => 'Duplicar',
      'description' => 'Permite duplicar registros',
      'icon' => 'Copy',
      'policy_method' => 'duplicate',
    ],

    'view_advisors' => [
      'label' => 'Ver Asesores',
      'description' => 'Permite visualizar la lista de asesores',
      'icon' => 'Users',
      'policy_method' => 'viewAdvisors',
    ],
  ],

  /*
  |--------------------------------------------------------------------------
  | Tipo de Permisos
  |--------------------------------------------------------------------------
  |
  | Tipos disponibles para categorizar permisos
  |
  */

  'types' => [
    'basic' => 'Básico (CRUD)',
    'advanced' => 'Avanzado',
    'special' => 'Especial',
    'admin' => 'Administrador',
  ],
];
