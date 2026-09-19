<?php

namespace App\Http\Services\ap\comercial;

use App\Models\ap\comercial\VehicleMovement;
use App\Models\ap\configuracionComercial\vehiculo\ApVehicleStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Detecta vehículos que hoy NO figuran como vendidos pero tienen un comprobante de venta vigente
 * (o cuya salida de un estado de venta fue provocada por una operación logística), explica qué
 * pasó con cada uno, por qué está mal, cómo se corregiría el dato y cómo se evita en código.
 *
 * SOLO LECTURA: simula la corrección (dry run) y no escribe nada en la base.
 */
class VehicleSaleStatusAuditService
{
  private const CAUSE_LATE_ARRIVAL = 'LLEGADA_TARDIA';
  private const CAUSE_CREDIT_NOTE = 'NC_SOBRE_REFACTURA';
  private const CAUSE_GUIDE_ON_SOLD = 'GUIA_SOBRE_VENDIDO';
  private const CAUSE_CANCELLATION = 'ANULACION_SOBRE_VENTA_VIGENTE';
  private const CAUSE_SILENT = 'CAMBIO_SIN_MOVIMIENTO';
  private const CAUSE_OTHER = 'SIN_CLASIFICAR';

  /** Salidas de un estado de venta que son legítimas por sí mismas. */
  private const LEGIT_EXIT_TYPES = ['DESASIGNACION', 'EN CONSIGNACION', 'EN TRAVESIA DEVUELTO'];

  /** Salidas legítimas solo si el vehículo no conserva un comprobante vigente. */
  private const CANCEL_EXIT_TYPES = ['CANCELACION_FACTURA', 'REVERSION_NOTA_CREDITO'];

  private array $statusNames = [];
  private array $warehouseNames = [];

  /**
   * @param string[] $vins Limitar a uno o más VIN (vacío = todos)
   */
  public function generate(array $vins = []): array
  {
    $vins = array_values(array_filter($vins));
    $sale = ApVehicleStatus::SALE_STATUSES;
    $this->statusNames = DB::table('ap_vehicle_status')->pluck('description', 'id')->all();
    $this->warehouseNames = DB::table('warehouse')->pluck('description', 'id')->all();

    // ---- Candidatos: comprobante vigente con estado de no venta, o salida logística de venta ----
    $validByVehicle = DB::table('ap_vehicle_movement as m')
      ->join('ap_vehicles as v', 'v.id', '=', 'm.ap_vehicle_id')
      ->join('ap_billing_electronic_documents as d', 'd.ap_vehicle_movement_id', '=', 'm.id')
      ->where('m.movement_type', 'VENTA')
      ->whereNull('m.deleted_at')->whereNull('v.deleted_at')->whereNull('d.deleted_at')
      ->whereNotIn('v.ap_vehicle_status_id', $sale)
      ->where('d.anulado', 0)->whereNull('d.credit_note_id')->where('d.status', 'accepted')
      ->where('d.is_advance_payment', 0)
      ->whereIn('d.sunat_concept_document_type_id', [29, 30])
      ->when($vins, fn($q) => $q->whereIn('v.vin', $vins))
      ->get(['v.id as vid', 'd.full_number', 'd.fecha_de_emision', 'd.total', 'd.cliente_denominacion', 'm.id as movement_id'])
      ->groupBy('vid');

    $exitVehicleIds = DB::table('ap_vehicle_movement as m')
      ->join('ap_vehicles as v', 'v.id', '=', 'm.ap_vehicle_id')
      ->whereIn('m.previous_status_id', $sale)
      ->whereNotIn('m.new_status_id', $sale)
      ->whereNotIn('m.movement_type', array_merge(self::LEGIT_EXIT_TYPES, self::CANCEL_EXIT_TYPES))
      ->whereNotIn('v.ap_vehicle_status_id', $sale)
      ->whereNull('m.deleted_at')->whereNull('v.deleted_at')
      ->when($vins, fn($q) => $q->whereIn('v.vin', $vins))
      ->pluck('m.ap_vehicle_id');

    $vehicleIds = $validByVehicle->keys()->merge($exitVehicleIds)->unique()->values();
    $vehicles = DB::table('ap_vehicles')->whereIn('id', $vehicleIds)->get()->keyBy('id');

    $rows = [];
    foreach ($vehicleIds as $vid) {
      $rows[] = $this->analyze($vehicles[$vid], $validByVehicle->get($vid, collect())->all());
    }
    usort($rows, fn($a, $b) => [$a['cause'], $a['exit_at']] <=> [$b['cause'], $b['exit_at']]);

    $count = fn(string $action) => count(array_filter($rows, fn($r) => $r['action'] === $action));
    $causes = [];
    foreach ($this->causeInfo() as $code => $info) {
      $causes[] = ['code' => $code, 'count' => count(array_filter($rows, fn($r) => $r['cause'] === $code))] + $info;
    }

    return [
      'generated_at' => now()->toDateTimeString(),
      'summary'      => [
        'total'  => count($rows),
        'fix'    => $count('CORREGIR'),
        'review' => $count('REVISAR'),
        'none'   => $count('SIN ACCIÓN'),
      ],
      'causes'       => array_values(array_filter($causes, fn($c) => $c['count'] > 0)),
      'rows'         => $rows,
    ];
  }

  // ─────────────────────────────────────────────────────────────────────────
  // Análisis por vehículo
  // ─────────────────────────────────────────────────────────────────────────

  private function analyze(object $vehicle, array $validInvoices): array
  {
    $sale = ApVehicleStatus::SALE_STATUSES;
    $movements = DB::table('ap_vehicle_movement')
      ->where('ap_vehicle_id', $vehicle->id)->whereNull('deleted_at')->orderBy('id')->get();

    $validMovementIds = array_map(fn($i) => $i->movement_id, $validInvoices);
    $isFlagged = function (object $m) use ($sale, $validMovementIds): bool {
      if (!in_array($m->previous_status_id, $sale) || in_array($m->new_status_id, $sale)) {
        return false;
      }
      if (in_array($m->movement_type, self::LEGIT_EXIT_TYPES, true)) {
        return false;
      }
      if (in_array($m->movement_type, self::CANCEL_EXIT_TYPES, true)) {
        // Cancelación con un comprobante vigente emitido antes: no debió dejarlo en inventario
        return (bool) array_filter($validMovementIds, fn($id) => $id < $m->id);
      }
      return true;
    };

    $flagged = $movements->filter($isFlagged)->values();
    $lastExit = $flagged->last();
    $lastSaleMovement = $movements->last(fn($m) => in_array($m->new_status_id, $sale));

    $cause = self::CAUSE_OTHER;
    if ($lastExit) {
      if ($lastExit->movement_type === VehicleMovement::INTERNAL_TRANSFER || $lastExit->movement_type === VehicleMovement::INVENTORY) {
        $cause = self::CAUSE_LATE_ARRIVAL;
      } elseif ($lastExit->movement_type === VehicleMovement::CREDIT_NOTE_REVERT) {
        $cause = self::CAUSE_CREDIT_NOTE;
      } elseif ($lastExit->movement_type === 'CANCELACION_FACTURA') {
        $cause = self::CAUSE_CANCELLATION;
      } elseif (in_array($lastExit->movement_type, ['TRAVESIA', 'EN TRAVESIA'], true)) {
        $cause = self::CAUSE_GUIDE_ON_SOLD;
      }
    } elseif ($movements->isNotEmpty() && in_array($movements->last()->new_status_id, $sale)
      && !in_array($vehicle->ap_vehicle_status_id, $sale)) {
      // El último movimiento lo dejó vendido pero el vehículo figura en otro estado
      $cause = self::CAUSE_SILENT;
    }

    // Venta que cuenta la historia: la última VENTA antes de la salida (no una entrega posterior)
    $storySale = $movements->last(fn($m) => $m->movement_type === 'VENTA' && (!$lastExit || $m->id < $lastExit->id))
      ?? $lastSaleMovement;

    // Comprobantes de venta anulados o con nota de crédito ligados a movimientos del vehículo
    $cancelledInvoices = DB::table('ap_billing_electronic_documents')
      ->whereIn('ap_vehicle_movement_id', $movements->pluck('id'))
      ->whereIn('sunat_concept_document_type_id', [29, 30])
      ->where(fn($q) => $q->where('anulado', 1)->orWhereNotNull('credit_note_id'))
      ->whereNull('deleted_at')
      ->get(['full_number', 'is_advance_payment', 'total', 'fecha_de_emision', 'credit_note_id'])
      ->map(fn($i) => [
        'n'     => $i->full_number,
        'date'  => substr((string) $i->fecha_de_emision, 0, 10),
        'total' => $i->total,
        'kind'  => $i->is_advance_payment ? 'anticipo' : 'venta',
        'how'   => $i->credit_note_id ? 'con nota de crédito' : 'anulado',
      ])->all();

    // ---- Guía implicada y guías creadas mientras el traslado seguía abierto ----
    $guide = null;
    $overlap = [];
    $guideRef = null;
    if ($lastExit && preg_match('/Guía(?: interna| COMPRA| de remisión registrada)?: ([A-Z0-9-]+)/u', (string) $lastExit->observation, $mm)) {
      $guideRef = $mm[1];
      $guide = DB::table('shipping_guides')->where('document_number', $guideRef)->first();
    }
    if ($guide && $cause === self::CAUSE_LATE_ARRIVAL) {
      $overlap = DB::table('shipping_guides as g')
        ->join('ap_vehicle_movement as gm', 'gm.id', '=', 'g.vehicle_movement_id')
        ->where('gm.ap_vehicle_id', $vehicle->id)->where('g.id', '!=', $guide->id)
        ->where('g.status', true)->where('g.is_annulled', false)->whereNull('g.deleted_at')
        ->where('g.created_at', '>', $guide->created_at)->where('g.created_at', '<', $lastExit->created_at)
        ->orderBy('g.id')
        ->get(['g.document_number', 'g.transfer_reason_id', 'g.document_type', 'g.created_at'])
        ->map(fn($g) => ['doc' => $g->document_number, 'kind' => $this->guideKind($g), 'at' => $g->created_at])
        ->all();
    }

    // ---- Nota de crédito y refacturación ----
    $creditNote = null;
    if ($cause === self::CAUSE_CREDIT_NOTE && preg_match('/Documento: ([A-Z0-9]+)-(\d+)/u', (string) $lastExit->observation, $mm)) {
      $nc = DB::table('ap_billing_electronic_documents')->where('serie', $mm[1])->where('numero', (int) $mm[2])->first();
      $orig = $nc && $nc->original_document_id
        ? DB::table('ap_billing_electronic_documents')->where('id', $nc->original_document_id)->first() : null;
      $creditNote = ['number' => "{$mm[1]}-{$mm[2]}", 'annuls' => $orig->full_number ?? null, 'issued' => $nc->fecha_de_emision ?? null];
    }

    // ---- Estado/almacén propuestos y decisión ----
    $targetStatusId = $lastSaleMovement?->new_status_id;
    $targetWarehouseId = $movements->last(fn($m) => in_array($m->new_status_id, $sale) && $m->warehouse_id)?->warehouse_id
      ?? $vehicle->warehouse_id;

    $manual = [];
    if (!$lastExit) {
      $manual[] = $cause === self::CAUSE_SILENT
        ? 'el estado cambió sin movimiento: verificar en la auditoría del vehículo antes de corregir'
        : 'no se identificó el movimiento que lo sacó de venta';
    }
    // Sin comprobante vigente y con la venta anulada después: el estado actual es correcto
    $noAction = !$validInvoices && $cancelledInvoices;
    if (!$validInvoices && !$noAction) {
      $manual[] = 'no tiene comprobante de venta vigente: verificar si la salida fue legítima';
    }
    if ($lastExit && !$noAction) {
      $after = $movements->filter(fn($m) => $m->id > $lastExit->id
        && !str_contains((string) $m->observation, 'Por asignación desde cotización'));
      if ($after->isNotEmpty()) {
        $manual[] = $after->count() . ' movimiento(s) posterior(es): ' . $after->pluck('movement_type')->unique()->implode(', ');
      }
      if ((int) $vehicle->ap_vehicle_status_id !== (int) $lastExit->new_status_id) {
        $manual[] = 'el estado actual no es el que dejó ese movimiento';
      }
    }
    if ($targetStatusId === null) {
      $manual[] = 'el vehículo nunca estuvo en un estado de venta en el historial';
    }
    if ($noAction) {
      $manual = [];
      $targetStatusId = $vehicle->ap_vehicle_status_id;
      $targetWarehouseId = $vehicle->warehouse_id;
    }

    // ---- Línea de tiempo: desde la primera guía implicada (de cualquier salida) o la venta previa ----
    $guideMovementIds = $flagged->map(function ($m) {
      return preg_match('/Guía(?: interna| COMPRA| de remisión registrada)?: ([A-Z0-9-]+)/u', (string) $m->observation, $g)
        ? DB::table('shipping_guides')->where('document_number', $g[1])->value('vehicle_movement_id') : null;
    })->filter()->all();
    $startId = $lastExit
      ? min(array_merge($guideMovementIds, [
        $movements->last(fn($m) => $m->id < $flagged->first()->id && in_array($m->new_status_id, $sale))?->id ?? $flagged->first()->id,
      ]))
      : ($movements->slice(-8)->first()->id ?? 0);
    $flaggedIds = $flagged->pluck('id')->all();
    $timeline = $movements->filter(fn($m) => $m->id >= $startId)->slice(-22)->map(fn($m) => [
      'at'     => substr((string) $m->created_at, 5, 11),
      'type'   => $m->movement_type,
      'status' => $this->statusShort($m->previous_status_id) . ' → ' . $this->statusShort($m->new_status_id),
      'wh'     => ($m->origin_warehouse_id || $m->warehouse_id)
        ? $this->whShort($m->origin_warehouse_id) . ' → ' . $this->whShort($m->warehouse_id) : '',
      'note'   => mb_substr((string) $m->observation, 0, 96),
      'flag'   => in_array($m->id, $flaggedIds) ? 'bad' : (in_array($m->new_status_id, $sale) && !in_array($m->previous_status_id, $sale) ? 'sale' : ''),
      'id'     => $m->id,
    ])->values()->all();

    $saleDoc = $storySale && preg_match('/Documento: (\S+)/u', (string) $storySale->observation, $d) ? $d[1] : null;

    $row = [
      'vin'          => $vehicle->vin,
      'cause'        => $cause,
      'current'      => $this->statusNames[$vehicle->ap_vehicle_status_id] ?? $vehicle->ap_vehicle_status_id,
      'target'       => $targetStatusId ? ($this->statusNames[$targetStatusId] ?? $targetStatusId) : '-',
      'wh_current'   => $this->whShort($vehicle->warehouse_id),
      'wh_target'    => $this->whShort($targetWarehouseId),
      'wh_changes'   => (int) $targetWarehouseId !== (int) $vehicle->warehouse_id,
      'invoices'     => array_map(fn($i) => ['n' => $i->full_number, 'date' => substr((string) $i->fecha_de_emision, 0, 10), 'total' => $i->total, 'client' => $i->cliente_denominacion], $validInvoices),
      'exit_id'      => $lastExit->id ?? null,
      'exit_at'      => (string) ($lastExit->created_at ?? ''),
      'guide'        => $guideRef,
      'action'       => $noAction ? 'SIN ACCIÓN' : ($manual ? 'REVISAR' : 'CORREGIR'),
      'manual'       => $manual,
      'cancelled'    => $cancelledInvoices,
      'timeline'     => $timeline,
      'earlier_exits' => $flagged->slice(0, -1)->map(function ($m) {
        $ref = preg_match('/Guía(?: interna| COMPRA)?: ([A-Z0-9-]+)/u', (string) $m->observation, $g) ? $g[1] : null;
        return ['at' => Carbon::parse($m->created_at)->format('d/m H:i'), 'type' => $m->movement_type, 'guide' => $ref];
      })->all(),
    ];

    return $row + $this->narrative($row, $vehicle, $lastExit, $storySale, $saleDoc, $guide, $overlap, $creditNote);
  }

  private function narrative(array $row, object $vehicle, ?object $lastExit, ?object $saleMov, ?string $saleDoc, ?object $guide, array $overlap, ?array $nc): array
  {
    $d = fn(?string $s) => $s ? Carbon::parse($s)->format('d/m H:i') : '?';
    $day = fn(?string $s) => $s ? Carbon::parse($s)->format('d/m/Y') : '?';
    $invoices = implode(', ', array_map(fn($i) => "{$i['n']} ({$day($i['date'])})", $row['invoices']));
    $cancelled = implode(', ', array_map(fn($i) => "{$i['n']}: {$i['kind']} {$i['how']}", $row['cancelled']));
    $available = $row['current'] === 'INVENTARIO VN' ? 'disponible en inventario' : 'no vendido';
    $happened = [];
    $wrong = [];
    $prevention = [];

    switch ($row['cause']) {
      case self::CAUSE_LATE_ARRIVAL:
        $sold = $saleDoc ? "{$saleDoc}, {$d($saleMov?->created_at)}" : $d($saleMov?->created_at);
        $soldBefore = $saleMov && $guide && $saleMov->created_at < $guide->created_at;
        $happened[] = $soldBefore
          ? "Ya estaba vendido ({$sold}) cuando se creó el traslado {$row['guide']} ({$d($guide->created_at)})."
          : "Se vendió ({$sold}) con el traslado {$row['guide']} todavía abierto (creado {$d($guide?->created_at)}, fecha de traslado {$day($guide->issue_date ?? null)}).";
        $happened[] = "El {$d($lastExit->created_at)} se registró la llegada de ese traslado y el sistema lo dejó en {$row['current']} sin comprobar que ya estaba vendido.";
        if ($overlap) {
          $happened[] = 'Con ese traslado sin llegar se crearon además: ' . implode(', ', array_map(fn($o) => "{$o['doc']} ({$o['kind']}, {$d($o['at'])})", $overlap)) . '.';
          $wrong[] = 'Se permitió crear otra guía con el traslado anterior sin haber llegado.';
        }
        $prevention[] = 'Llegada de traslado: conservar el estado de venta y solo actualizar almacén (HECHO, sin commit).';
        if ($overlap) {
          $prevention[] = 'No crear una guía de traslado hasta que el anterior llegue (HECHO, sin commit).';
          if (array_filter($overlap, fn($o) => $o['kind'] === 'entrega/venta')) {
            $prevention[] = 'DECISIÓN PENDIENTE: la guía de entrega (venta) no pasa por ese candado.';
          }
        }
        break;

      case self::CAUSE_CREDIT_NOTE:
        $happened[] = $nc && $nc['annuls']
          ? "Se emitió la nota de crédito {$nc['number']} que anula {$nc['annuls']}, y el vehículo se volvió a facturar con {$invoices}."
          : "Se aplicó una reversión por nota de crédito ({$lastExit->observation}).";
        $happened[] = "El {$d($lastExit->created_at)}, al contabilizarse la nota de crédito, el sistema lo devolvió a {$row['current']} sin considerar la factura nueva.";
        $wrong[] = 'La reversión por nota de crédito no comprueba si hubo una refacturación posterior.';
        $prevention[] = 'PENDIENTE: en storeCreditNoteRevertMovement no revertir si existe otra venta vigente más reciente.';
        $prevention[] = 'PENDIENTE: revisar storeInvoiceCancellationRevertMovement (anulación), que usa la misma lógica.';
        break;

      case self::CAUSE_CANCELLATION:
        $happened[] = "El {$d($lastExit->created_at)} se anuló un comprobante" . ($cancelled ? " ({$cancelled})" : '') . " y el sistema devolvió el vehículo a {$row['current']}.";
        $happened[] = $row['invoices']
          ? "Pero el vehículo conserva otro comprobante vigente: {$invoices}."
          : 'No conserva ningún comprobante vigente.';
        if ($row['invoices']) {
          $wrong[] = 'La anulación de un comprobante (posiblemente un anticipo) revierte el vehículo sin comprobar si hay otra venta vigente.';
        }
        $prevention[] = 'PENDIENTE: en storeInvoiceCancellationRevertMovement no revertir si el vehículo tiene otra venta vigente; distinguir anticipos de la factura final.';
        break;

      case self::CAUSE_GUIDE_ON_SOLD:
        $happened[] = "El {$d($lastExit->created_at)} se registró la guía {$row['guide']} sobre un vehículo ya vendido y el sistema lo pasó a EN TRÁNSITO.";
        $wrong[] = 'Se permitió generar una guía de traslado sobre un vehículo facturado.';
        $prevention[] = 'store() ya rechaza traslados de sede sobre vehículos vendidos; falta confirmar que cubra todas las rutas (estos casos son de julio).';
        break;

      case self::CAUSE_SILENT:
        $happened[] = 'El último movimiento del historial lo dejó vendido, pero hoy el vehículo figura como ' . $row['current'] . '.';
        $happened[] = 'No hay ningún movimiento que explique ese cambio: el estado se modificó sin dejar rastro (actualización directa, migración o proceso que no registra movimiento).';
        $wrong[] = 'El estado del vehículo no coincide con su historial.';
        $prevention[] = 'Investigar qué proceso cambió el estado sin movimiento (revisar auditoría del vehículo).';
        break;

      default:
        $happened[] = 'No se identificó el movimiento que lo sacó del estado de venta.';
        $prevention[] = 'Revisar a mano el historial (línea de tiempo).';
    }

    if ($row['action'] === 'SIN ACCIÓN') {
      $wrong = ["Su venta se anuló después ({$cancelled}), por lo que el estado actual ({$row['current']}) es el correcto."];
      $happened[] = 'El daño ocurrió, pero quedó sin efecto porque la venta se canceló legítimamente.';
    } elseif ($row['invoices']) {
      $wrong[] = "Figura como {$row['current']} ({$available}) pero tiene comprobante(s) vigente(s): {$invoices}.";
    } else {
      $wrong[] = 'No tiene comprobante de venta vigente: puede que su estado actual sea correcto.';
    }
    if ($row['wh_changes']) {
      $wrong[] = "Almacén {$row['wh_current']}, pero debería estar en {$row['wh_target']}.";
    }
    foreach ($row['earlier_exits'] as $e) {
      $wrong[] = 'Antes ya lo había revertido ' . match ($e['type']) {
        VehicleMovement::INTERNAL_TRANSFER, VehicleMovement::INVENTORY => 'la llegada tardía del traslado ' . ($e['guide'] ?? '?'),
        default => "un movimiento {$e['type']}",
      } . " el {$e['at']}.";
    }

    $fix = match ($row['action']) {
      'CORREGIR' => "Registrar un movimiento de corrección y pasar {$row['current']} → {$row['target']}"
        . ($row['wh_changes'] ? ", almacén {$row['wh_current']} → {$row['wh_target']}" : ' (el almacén no cambia)') . '.',
      'SIN ACCIÓN' => 'Ninguna: el estado actual ya es el correcto.',
      default => 'No se corrige solo: ' . implode('; ', $row['manual']) . '.',
    };

    return ['happened' => $happened, 'wrong' => $wrong, 'fix' => $fix, 'prevention' => $prevention];
  }

  // ─────────────────────────────────────────────────────────────────────────
  // Utilidades
  // ─────────────────────────────────────────────────────────────────────────

  private function guideKind(object $g): string
  {
    if ($g->document_type === 'GUIA_INTERNA') {
      return 'traslado interno';
    }
    return match ((int) $g->transfer_reason_id) {
      17 => 'traslado entre sedes',
      14 => 'entrega/venta',
      15 => 'compra',
      18 => 'consignación',
      default => 'otro motivo',
    };
  }

  private function statusShort(?int $id): string
  {
    return $id ? str_replace(['VEHICULO ', ' VN'], '', $this->statusNames[$id] ?? (string) $id) : '-';
  }

  private function whShort(?int $id): string
  {
    return $id ? trim(str_replace('ALMACEN COMERCIAL', '', $this->warehouseNames[$id] ?? (string) $id)) : '-';
  }

  private function causeInfo(): array
  {
    return [
      self::CAUSE_LATE_ARRIVAL => [
        'title' => 'La llegada tardía de un traslado devolvió a inventario un vehículo ya vendido',
        'text'  => 'Un traslado tiene dos momentos: sale (EN CURSO) y llega. La llegada se registra días después (job de las 06:00 o al contabilizar en Dynamics) y siempre deja el vehículo en INVENTARIO VN, sin mirar si mientras tanto se vendió o se entregó.',
        'where' => 'VehicleMovementService::storeInterCompanyTransferCompletedVehicleMovement',
        'fix'   => 'Corregido en código (sin commit): la llegada conserva el estado de venta y solo actualiza el almacén.',
      ],
      self::CAUSE_CREDIT_NOTE => [
        'title' => 'Una nota de crédito revirtió a inventario un vehículo que ya había sido refacturado',
        'text'  => 'Se anula una factura con nota de crédito y se emite una factura nueva. Cuando Dynamics contabiliza la nota de crédito (al día siguiente), el sistema devuelve el vehículo a inventario sin ver que existe una factura nueva vigente.',
        'where' => 'VehicleMovementService::storeCreditNoteRevertMovement',
        'fix'   => 'PENDIENTE: no revertir cuando hay otra venta vigente más reciente.',
      ],
      self::CAUSE_CANCELLATION => [
        'title' => 'La anulación de un comprobante devolvió a inventario un vehículo que conserva otra venta vigente',
        'text'  => 'Se anula un comprobante (por ejemplo una factura de anticipo) y el sistema devuelve el vehículo a inventario o tránsito sin comprobar si tiene otro comprobante de venta vigente, como la factura final.',
        'where' => 'VehicleMovementService::storeInvoiceCancellationRevertMovement',
        'fix'   => 'PENDIENTE: no revertir cuando hay otra venta vigente; distinguir anticipos de la factura final.',
      ],
      self::CAUSE_SILENT => [
        'title' => 'El estado del vehículo cambió sin dejar ningún movimiento',
        'text'  => 'El último movimiento del historial lo dejó vendido, pero el vehículo figura en otro estado. Algo modificó el estado directamente, sin registrar movimiento.',
        'where' => 'Por identificar (revisar auditoría del vehículo)',
        'fix'   => 'Investigar qué proceso lo cambió.',
      ],
      self::CAUSE_GUIDE_ON_SOLD => [
        'title' => 'Se registró una guía de traslado sobre un vehículo ya facturado',
        'text'  => 'La guía pasó el vehículo a EN TRÁNSITO aunque estaba vendido. Todos los casos son de julio.',
        'where' => 'ShippingGuidesService::store / VehicleMovementService::storeShippingGuideVehicleMovement',
        'fix'   => 'Probablemente ya cubierto por la validación isSaleStatus de traslados de sede; por confirmar.',
      ],
      self::CAUSE_OTHER => [
        'title' => 'Sin clasificar',
        'text'  => 'Tiene comprobante vigente pero no se identificó qué movimiento lo sacó de venta.',
        'where' => '-',
        'fix'   => 'Revisión manual.',
      ],
    ];
  }
}
