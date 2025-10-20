<?php

namespace App\Policies\ap\comercial;

use App\Models\ap\comercial\VehiclePurchaseOrder;
use App\Models\User;
use App\Policies\BasePolicy;

/**
 * Policy para VehiclePurchaseOrder
 *
 * Combina permisos básicos (CRUD) con permisos granulares específicos
 * como export, resend, approve, etc.
 */
class VehiclePurchaseOrderPolicy extends BasePolicy
{
    /**
     * Módulo/vista para verificar permisos
     */
    protected string $module = 'vehicle_purchase_order';

    /**
     * Permiso para exportar órdenes de compra
     * Usa permiso granular: vehicle_purchase_order.export
     */
    public function export(User $user): bool
    {
        return $this->hasPermission($user, 'export');
    }

    /**
     * Permiso para reenviar una orden anulada
     * Combina permiso granular + validación de estado del modelo
     */
    public function resend(User $user, VehiclePurchaseOrder $order): bool
    {
        // Verificar permiso granular
        if (!$this->hasPermission($user, 'resend')) {
            return false;
        }

        // Lógica de negocio: solo se puede reenviar si está anulada
        // Ajusta según tu campo de estado
        return true; // Aquí puedes agregar: $order->status === 'anulado'
    }

    /**
     * Permiso para aprobar una orden de compra
     * Combina múltiples validaciones
     */
    public function approve(User $user, VehiclePurchaseOrder $order): bool
    {
        // Verificar permiso granular
        if (!$this->hasPermission($user, 'approve')) {
            return false;
        }

        // Lógica de negocio adicional
        // Ejemplo: solo se puede aprobar si está pendiente y el monto es menor a cierto límite
        // return $order->status === 'pendiente' && $order->total < 100000;

        return true;
    }

    /**
     * Permiso para rechazar una orden de compra
     */
    public function reject(User $user, VehiclePurchaseOrder $order): bool
    {
        return $this->hasPermission($user, 'reject');
    }

    /**
     * Permiso para importar órdenes de compra
     * Requiere tanto permiso de crear como permiso de importar
     */
    public function import(User $user): bool
    {
        return $this->hasBasicAndGranularPermission($user, 'crear', 'import');
    }

    /**
     * Permiso para ver reportes
     * Ejemplo de permiso que requiere múltiples permisos
     */
    public function viewReports(User $user): bool
    {
        // Usuario debe tener permiso de ver + permiso granular de reports
        return $user->hasAccessToView($this->module, 'ver')
            && $this->hasPermission($user, 'view_reports');
    }

    /**
     * Sobrescribe el método delete para agregar validación adicional
     */
    public function delete(User $user, VehiclePurchaseOrder $order): bool
    {
        // Verificar permiso básico de anular
        if (!parent::delete($user, $order)) {
            return false;
        }

        // Lógica de negocio: no se puede anular si ya fue aprobada o completada
        // return !in_array($order->status, ['aprobado', 'completado']);

        return true;
    }
}
