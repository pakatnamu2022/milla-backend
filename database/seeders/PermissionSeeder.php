<?php

namespace Database\Seeders;

use App\Models\gp\gestionsistema\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Crea permisos granulares iniciales organizados por módulo
     */
    public function run(): void
    {
        $permissions = [
            // ========================================
            // VEHICLE PURCHASE ORDERS
            // ========================================
            [
                'code' => 'vehicle_purchase_order.export',
                'name' => 'Exportar Órdenes de Compra de Vehículos',
                'description' => 'Permite exportar listado de órdenes de compra a Excel/PDF',
                'module' => 'vehicle_purchase_order',
                'policy_method' => 'export',
                'type' => 'special',
                'is_active' => true,
            ],
            [
                'code' => 'vehicle_purchase_order.resend',
                'name' => 'Reenviar OC Anulada',
                'description' => 'Permite reenviar una orden de compra que fue anulada con datos corregidos',
                'module' => 'vehicle_purchase_order',
                'policy_method' => 'resend',
                'type' => 'custom',
                'is_active' => true,
            ],
            [
                'code' => 'vehicle_purchase_order.approve',
                'name' => 'Aprobar Orden de Compra',
                'description' => 'Permite aprobar órdenes de compra pendientes',
                'module' => 'vehicle_purchase_order',
                'policy_method' => 'approve',
                'type' => 'special',
                'is_active' => true,
            ],
            [
                'code' => 'vehicle_purchase_order.reject',
                'name' => 'Rechazar Orden de Compra',
                'description' => 'Permite rechazar órdenes de compra',
                'module' => 'vehicle_purchase_order',
                'policy_method' => 'reject',
                'type' => 'special',
                'is_active' => true,
            ],
            [
                'code' => 'vehicle_purchase_order.import',
                'name' => 'Importar Órdenes de Compra',
                'description' => 'Permite importar órdenes de compra desde archivos externos',
                'module' => 'vehicle_purchase_order',
                'policy_method' => 'import',
                'type' => 'special',
                'is_active' => true,
            ],
            [
                'code' => 'vehicle_purchase_order.view_reports',
                'name' => 'Ver Reportes de OC',
                'description' => 'Permite acceder a reportes y análisis de órdenes de compra',
                'module' => 'vehicle_purchase_order',
                'policy_method' => 'viewReports',
                'type' => 'special',
                'is_active' => true,
            ],

            // ========================================
            // OPPORTUNITIES
            // ========================================
            [
                'code' => 'opportunity.export',
                'name' => 'Exportar Oportunidades',
                'description' => 'Permite exportar listado de oportunidades',
                'module' => 'opportunity',
                'policy_method' => 'export',
                'type' => 'special',
                'is_active' => true,
            ],
            [
                'code' => 'opportunity.assign',
                'name' => 'Asignar Oportunidad',
                'description' => 'Permite asignar oportunidades a otros usuarios/vendedores',
                'module' => 'opportunity',
                'policy_method' => 'assign',
                'type' => 'custom',
                'is_active' => true,
            ],
            [
                'code' => 'opportunity.transfer',
                'name' => 'Transferir Oportunidad',
                'description' => 'Permite transferir oportunidades entre sucursales o equipos',
                'module' => 'opportunity',
                'policy_method' => 'transfer',
                'type' => 'custom',
                'is_active' => true,
            ],
            [
                'code' => 'opportunity.close',
                'name' => 'Cerrar Oportunidad',
                'description' => 'Permite cerrar oportunidades (ganadas o perdidas)',
                'module' => 'opportunity',
                'policy_method' => 'close',
                'type' => 'special',
                'is_active' => true,
            ],
            [
                'code' => 'opportunity.reopen',
                'name' => 'Reabrir Oportunidad',
                'description' => 'Permite reabrir oportunidades cerradas',
                'module' => 'opportunity',
                'policy_method' => 'reopen',
                'type' => 'custom',
                'is_active' => true,
            ],
            [
                'code' => 'opportunity.view_all_users',
                'name' => 'Ver Oportunidades de Todos los Usuarios',
                'description' => 'Permite ver y filtrar oportunidades de cualquier usuario (no solo las propias). Habilita el filtro por usuario en la vista de oportunidades.',
                'module' => 'opportunity',
                'policy_method' => 'viewAllUsers',
                'type' => 'custom',
                'is_active' => true,
            ],

            // ========================================
            // EVALUATIONS
            // ========================================
            [
                'code' => 'evaluation.export',
                'name' => 'Exportar Evaluaciones',
                'description' => 'Permite exportar resultados de evaluaciones',
                'module' => 'evaluation',
                'policy_method' => 'export',
                'type' => 'special',
                'is_active' => true,
            ],
            [
                'code' => 'evaluation.publish',
                'name' => 'Publicar Evaluación',
                'description' => 'Permite publicar evaluaciones para que sean visibles',
                'module' => 'evaluation',
                'policy_method' => 'publish',
                'type' => 'special',
                'is_active' => true,
            ],
            [
                'code' => 'evaluation.approve_results',
                'name' => 'Aprobar Resultados',
                'description' => 'Permite aprobar resultados de evaluaciones',
                'module' => 'evaluation',
                'policy_method' => 'approveResults',
                'type' => 'special',
                'is_active' => true,
            ],
            [
                'code' => 'evaluation.export_metrics',
                'name' => 'Exportar Métricas',
                'description' => 'Permite exportar métricas y análisis de evaluaciones',
                'module' => 'evaluation',
                'policy_method' => 'exportMetrics',
                'type' => 'special',
                'is_active' => true,
            ],

            // ========================================
            // USERS
            // ========================================
            [
                'code' => 'user.export',
                'name' => 'Exportar Usuarios',
                'description' => 'Permite exportar listado de usuarios',
                'module' => 'user',
                'policy_method' => 'export',
                'type' => 'special',
                'is_active' => true,
            ],
            [
                'code' => 'user.reset_password',
                'name' => 'Resetear Contraseña',
                'description' => 'Permite resetear contraseñas de otros usuarios',
                'module' => 'user',
                'policy_method' => 'resetPassword',
                'type' => 'custom',
                'is_active' => true,
            ],
            [
                'code' => 'user.change_role',
                'name' => 'Cambiar Rol de Usuario',
                'description' => 'Permite cambiar el rol asignado a un usuario',
                'module' => 'user',
                'policy_method' => 'changeRole',
                'type' => 'custom',
                'is_active' => true,
            ],
            [
                'code' => 'user.impersonate',
                'name' => 'Suplantar Usuario',
                'description' => 'Permite iniciar sesión como otro usuario (con fines de soporte)',
                'module' => 'user',
                'policy_method' => 'impersonate',
                'type' => 'custom',
                'is_active' => true,
            ],

            // ========================================
            // ROLES & PERMISSIONS
            // ========================================
            [
                'code' => 'permission.assign',
                'name' => 'Asignar Permisos',
                'description' => 'Permite asignar permisos a roles',
                'module' => 'permission',
                'policy_method' => 'assign',
                'type' => 'custom',
                'is_active' => true,
            ],
            [
                'code' => 'permission.export',
                'name' => 'Exportar Permisos',
                'description' => 'Permite exportar matriz de permisos',
                'module' => 'permission',
                'policy_method' => 'export',
                'type' => 'special',
                'is_active' => true,
            ],

            // ========================================
            // REPORTS (GENERAL)
            // ========================================
            [
                'code' => 'report.view_financial',
                'name' => 'Ver Reportes Financieros',
                'description' => 'Permite acceder a reportes financieros confidenciales',
                'module' => 'report',
                'policy_method' => 'viewFinancial',
                'type' => 'custom',
                'is_active' => true,
            ],
            [
                'code' => 'report.view_analytics',
                'name' => 'Ver Analytics',
                'description' => 'Permite acceder a dashboards y análisis avanzados',
                'module' => 'report',
                'policy_method' => 'viewAnalytics',
                'type' => 'custom',
                'is_active' => true,
            ],
            [
                'code' => 'report.export_all',
                'name' => 'Exportar Todos los Reportes',
                'description' => 'Permite exportar cualquier reporte del sistema',
                'module' => 'report',
                'policy_method' => 'exportAll',
                'type' => 'special',
                'is_active' => true,
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['code' => $permission['code']],
                $permission
            );
        }

        $this->command->info('✅ Permisos creados exitosamente: ' . count($permissions));
    }
}
