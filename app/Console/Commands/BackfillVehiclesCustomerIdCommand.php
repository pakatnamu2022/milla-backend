<?php

namespace App\Console\Commands;

use App\Models\ap\ApMasters;
use App\Models\ap\comercial\BusinessPartners;
use App\Models\ap\comercial\Vehicles;
use App\Models\ap\facturacion\ElectronicDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfill de Vehicles.customer_id (y corrección de is_paid) para vehículos comerciales
 * (type_operation_id = ApMasters::TIPO_OPERACION_COMERCIAL) que quedaron con customer_id
 * NULL, o con is_paid=true desincronizado, antes de que ElectronicDocumentService
 * sincronizara estos campos al facturar/anular.
 *
 * Regla (verificando el SALDO NETO real: facturas finales - NC + ND, no solo el flag
 * is_paid, porque una NC puede haber anulado la venta sin que is_paid se revirtiera):
 *   - Si el saldo neto de sus facturas/boletas finales (no anticipo) aceptadas es > 0
 *     -> tomar el client_id de la última de esas facturas y marcar is_paid=true.
 *   - Si el saldo neto es <= 0 (nunca facturado, o facturado y luego anulado por NC)
 *     -> asignar BusinessPartners::AUTOMOTORES_PAKATNAMU_ID (AP, id 17) y is_paid=false.
 */
class BackfillVehiclesCustomerIdCommand extends Command
{
  protected $signature = 'vehicles:backfill-customer-id
    {--dry-run : Solo mostrar el resultado, sin escribir en la base de datos}
    {--limit= : Procesar como máximo N vehículos (para probar antes de correr todo)}
    {--all : Además de los customer_id en NULL, revisar también los ya asignados por si is_paid quedó desincronizado con el saldo neto}';

  protected $description = 'Rellena/corrige Vehicles.customer_id e is_paid (comerciales) según el saldo neto real de sus facturas finales (factura - NC + ND)';

  public function handle(): int
  {
    $dryRun = (bool) $this->option('dry-run');
    $limit = $this->option('limit') ? (int) $this->option('limit') : null;
    $all = (bool) $this->option('all');

    $query = Vehicles::where('type_operation_id', ApMasters::TIPO_OPERACION_COMERCIAL)
      ->orderBy('id');

    if (!$all) {
      $query->whereNull('customer_id');
    }

    if ($limit) {
      $query->limit($limit);
    }

    $vehicles = $query->get();

    $this->info("Vehículos comerciales a revisar: {$vehicles->count()}" . ($dryRun ? ' (DRY RUN, no se escribirá nada)' : ''));

    $updatedFromInvoice = 0;
    $updatedToAp = 0;
    $unchanged = 0;
    $rows = [];

    foreach ($vehicles as $vehicle) {
      $finalDocs = ElectronicDocument::whereHas('vehicleMovement', function ($q) use ($vehicle) {
        $q->where('ap_vehicle_id', $vehicle->id);
      })
        ->where('is_advance_payment', false)
        ->whereIn('sunat_concept_document_type_id', [
          ElectronicDocument::TYPE_FACTURA,
          ElectronicDocument::TYPE_BOLETA,
        ])
        // aceptada_por_sunat y status a veces quedan desincronizados (uno se
        // actualiza y el otro no), así que se acepta cualquiera de los dos.
        ->where(function ($q) {
          $q->where('aceptada_por_sunat', true)
            ->orWhere('status', ElectronicDocument::STATUS_ACCEPTED);
        })
        ->orderBy('fecha_de_emision')
        ->orderBy('id')
        ->get();

      $netTotal = 0;
      $lastClientId = null;

      if ($finalDocs->isNotEmpty()) {
        $invoiceIds = $finalDocs->pluck('id');

        $totalInvoiced = $finalDocs->sum('total');
        $totalCreditNotes = ElectronicDocument::whereIn('original_document_id', $invoiceIds)
          ->where('sunat_concept_document_type_id', ElectronicDocument::TYPE_NOTA_CREDITO)
          ->where('aceptada_por_sunat', true)
          ->sum('total');
        $totalDebitNotes = ElectronicDocument::whereIn('original_document_id', $invoiceIds)
          ->where('sunat_concept_document_type_id', ElectronicDocument::TYPE_NOTA_DEBITO)
          ->where('aceptada_por_sunat', true)
          ->sum('total');

        $netTotal = $totalInvoiced - $totalCreditNotes + $totalDebitNotes;
        $lastClientId = $finalDocs->last()->client_id;
      }

      // Nota: facturas de regularización con total=0 (el monto ya se cobró por
      // anticipo, no ligado al vehículo) también cuentan como venta vigente,
      // por eso el criterio real es "¿hay factura final aceptada sin NC que la
      // anule?", no solo el monto. Solo se descarta cuando SÍ hubo importe y una
      // NC lo neteó a <= tolerancia.
      $hasVoidingCreditNote = $finalDocs->isNotEmpty()
        && $finalDocs->sum('total') > 0
        && round($netTotal, 2) <= ElectronicDocument::ROUNDING_TOLERANCE;

      $isEffectivelySold = $finalDocs->isNotEmpty() && !$hasVoidingCreditNote;

      $targetCustomerId = $isEffectivelySold ? $lastClientId : BusinessPartners::AUTOMOTORES_PAKATNAMU_ID;
      $targetIsPaid = $isEffectivelySold;

      $needsCustomerFix = (int) $vehicle->customer_id !== (int) $targetCustomerId;
      $needsPaidFix = (bool) $vehicle->is_paid !== $targetIsPaid;

      if (!$needsCustomerFix && !$needsPaidFix) {
        $unchanged++;
        continue;
      }

      $estado = $isEffectivelySold
        ? 'PAGADO'
        : ($finalDocs->isEmpty() ? 'SIN FACTURA (stock/no vendido)' : 'ANULADO POR NC');

      $rows[] = [
        $vehicle->id,
        $vehicle->vin,
        $estado,
        $finalDocs->last()->full_number ?? '-',
        $vehicle->customer_id ?? 'null',
        $targetCustomerId,
        $vehicle->is_paid ? '1' : '0',
        $targetIsPaid ? '1' : '0',
      ];

      if (!$dryRun) {
        $vehicle->update(['customer_id' => $targetCustomerId, 'is_paid' => $targetIsPaid]);
      }

      if ($isEffectivelySold) {
        $updatedFromInvoice++;
      } else {
        $updatedToAp++;
      }
    }

    $this->table(
      ['ap_vehicle_id', 'vin', 'estado', 'factura_final', 'customer_id_antes', 'customer_id_nuevo', 'is_paid_antes', 'is_paid_nuevo'],
      $rows
    );

    $this->newLine();
    $this->info("Asignados/corregidos desde factura final vigente: {$updatedFromInvoice}");
    $this->info("Asignados/corregidos a AP (17): {$updatedToAp}");
    $this->info("Sin cambios (ya estaban correctos): {$unchanged}");

    return self::SUCCESS;
  }
}
