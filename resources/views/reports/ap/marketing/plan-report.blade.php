@php
  use App\Models\ap\marketing\MktSupport;
  use App\Models\ap\marketing\MktPurchaseOrder;
  use App\Models\ap\marketing\MktBudget;
  use App\Models\ap\marketing\MktActivity;

  function base64ImgPlan($path) {
    $fullPath = public_path($path);
    if (!file_exists($fullPath)) return '';
    return 'data:' . mime_content_type($fullPath) . ';base64,' . base64_encode(file_get_contents($fullPath));
  }

  $plan = $plan ?? null;
  $activities = $activities ?? collect();
  $planTotals = $planTotals ?? [];

  $planCurrencySymbol = optional($plan->budgets->first())->currency->symbol ?? 'S/';
@endphp
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Reporte de Plan de Marketing</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: Arial, sans-serif;
      font-size: 12px;
      color: #22293a;
      background: #fff;
    }

    .page-header { padding: 8px 22px; margin-bottom: 8px; }
    .header-inner { display: table; width: 100%; }
    .h-logo { display: table-cell; width: 190px; vertical-align: middle; padding-right: 14px; }
    .h-logo img { max-width: 180px; height: auto; display: block; }
    .h-right { display: table-cell; vertical-align: middle; }
    .h-box { display: table; width: 100%; border: 1.5px solid #e0e0e0; border-radius: 5px; overflow: hidden; }
    .h-box-title { display: table-cell; vertical-align: middle; padding: 6px 14px; background: #fff; }
    .h-box-title-main { font-size: 13px; font-weight: bold; color: #22293a; letter-spacing: 0.3px; }
    .h-box-title-sub { font-size: 9px; color: #777777; margin-top: 2px; }
    .h-box-num { display: table-cell; vertical-align: middle; width: 145px; background: #e0e0e0; color: #22293a; text-align: center; padding: 6px 14px; }
    .h-box-num-lbl { font-size: 8px; font-weight: bold; letter-spacing: 0.6px; }
    .h-box-num-val { font-size: 16px; font-weight: bold; white-space: nowrap; margin-top: 4px; }

    .content { padding: 0 22px; }

    .card { border: 1px solid #d2d2d2; border-radius: 7px; overflow: hidden; margin-bottom: 11px; page-break-inside: avoid; }
    .card-title { background-color: #e0e0e0; color: #22293a; font-weight: bold; font-size: 10.5px; padding: 4px 12px; letter-spacing: 0.3px; }

    table.dt { width: 100%; border-collapse: collapse; }
    table.dt td { padding: 5px 10px; border-bottom: 1px solid #ebebeb; font-size: 11px; vertical-align: top; }
    table.dt tr:last-child td { border-bottom: none; }
    .lbl { font-weight: bold; color: #22293a; background: #f5f5f5; white-space: nowrap; width: 15%; }

    table.cl { width: 100%; border-collapse: collapse; }
    table.cl th {
      background-color: #f5f5f5; color: #22293a; font-size: 9.5px; font-weight: bold;
      padding: 5px 6px; text-align: center; border-right: 1px solid #d2d2d2; border-bottom: 1px solid #d2d2d2;
    }
    table.cl th:last-child { border-right: none; }
    table.cl td { padding: 4px 6px; border-bottom: 1px solid #ebebeb; border-right: 1px solid #ebebeb; font-size: 9.5px; vertical-align: middle; }
    table.cl td:last-child { border-right: none; }
    table.cl tr:last-child td { border-bottom: none; }
    table.cl tr:nth-child(even) td { background: #f9f9f9; }
    table.cl tr:nth-child(odd) td { background: #fff; }

    .col-num { width: 20px; text-align: center; }
    .col-amount { width: 72px; text-align: right; }
    .col-date { width: 58px; text-align: center; }

    .activity-block { border: 1px solid #d2d2d2; border-radius: 7px; margin-bottom: 13px; overflow: hidden; page-break-inside: avoid; }
    .activity-head { background: #22293a; color: #fff; padding: 6px 12px; }
    .activity-head-name { font-size: 11.5px; font-weight: bold; }
    .activity-head-sub { font-size: 9px; color: #d5d8e0; margin-top: 1px; }
    .activity-body { padding: 8px 10px; }

    .sub-title { font-size: 9.5px; font-weight: bold; color: #22293a; margin: 6px 0 4px; text-transform: uppercase; letter-spacing: 0.3px; }

    .compare-box { display: table; width: 100%; margin-top: 6px; border: 1px solid #e0e0e0; border-radius: 5px; overflow: hidden; }
    .compare-cell { display: table-cell; width: 33.33%; padding: 6px 10px; text-align: center; border-right: 1px solid #e0e0e0; }
    .compare-cell:last-child { border-right: none; }
    .compare-lbl { font-size: 8px; color: #777777; text-transform: uppercase; letter-spacing: 0.3px; }
    .compare-val { font-size: 12px; font-weight: bold; color: #22293a; margin-top: 2px; }
    .compare-val.negative { color: #b3261e; }
    .compare-val.positive { color: #1e7a34; }

    .empty { border: 1px solid #d2d2d2; border-radius: 6px; padding: 8px 10px; font-style: italic; color: #aaaaaa; font-size: 10.5px; margin-bottom: 8px; }

    .totals { margin-top: 8px; text-align: right; }
    .totals .row { font-size: 12px; font-weight: bold; padding: 2px 0; }

    .footer { margin-top: 16px; text-align: center; font-size: 8px; color: #aaaaaa; border-top: 1px solid #d2d2d2; padding-top: 5px; }
  </style>
</head>
<body>

<div class="page-header">
  <div class="header-inner">
    <div class="h-logo">
      <img src="{{ base64ImgPlan('images/ap/logo-ap.png') }}" alt="AP Logo">
    </div>
    <div class="h-right">
      <div class="h-box">
        <div class="h-box-title">
          <div class="h-box-title-main">REPORTE COMPLETO DE PLAN DE MARKETING</div>
          <div class="h-box-title-sub">Actividades, órdenes de compra y sustentos</div>
        </div>
        <div class="h-box-num">
          <div class="h-box-num-lbl">N° PLAN</div>
          <div class="h-box-num-val">PLN-{{ str_pad($plan->id, 6, '0', STR_PAD_LEFT) }}</div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="content">

  <div class="card">
    <div class="card-title">DATOS DEL PLAN</div>
    <table class="dt">
      <tr>
        <td class="lbl" style="width:16%;">Nombre</td>
        <td colspan="3">{{ $plan->name }}</td>
      </tr>
      <tr>
        <td class="lbl">Marca</td>
        <td>{{ optional($plan->brand)->name ?? 'N/A' }}</td>
        <td class="lbl" style="width:14%;">Año</td>
        <td>{{ $plan->year ?? 'N/A' }}</td>
      </tr>
      <tr>
        <td class="lbl">Concepto</td>
        <td>{{ $plan->concept ?: 'N/A' }}</td>
        <td class="lbl">Estado</td>
        <td>{{ \App\Models\ap\marketing\MktPlan::STATUS_LABELS[$plan->status] ?? $plan->status }}</td>
      </tr>
    </table>
  </div>

  @if($plan->budgets->count() > 0)
    <div class="card">
      <div class="card-title">PRESUPUESTOS ({{ $plan->budgets->count() }})</div>
      <table class="cl">
        <thead>
        <tr>
          <th style="text-align:left;">Tipo</th>
          <th>Período</th>
          <th style="text-align:left;">Moneda</th>
          <th class="col-amount">Estimado</th>
          <th class="col-amount">Ejecutado</th>
          <th>Estado</th>
        </tr>
        </thead>
        <tbody>
        @foreach($plan->budgets as $budget)
          <tr>
            <td>{{ MktBudget::TYPE_LABELS[$budget->type] ?? $budget->type }}</td>
            <td style="text-align:center;">{{ $budget->period_month ?? '—' }}</td>
            <td>{{ $budget->currency->code ?? ($budget->currency->symbol ?? 'N/A') }}</td>
            <td class="col-amount">{{ $budget->currency->symbol ?? 'S/' }} {{ number_format($budget->amount_estimated ?? 0, 2) }}</td>
            <td class="col-amount">{{ $budget->currency->symbol ?? 'S/' }} {{ number_format($budget->amount_executed ?? 0, 2) }}</td>
            <td>{{ MktBudget::STATUS_LABELS[$budget->status] ?? $budget->status }}</td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
  @endif

  <div class="card">
    <div class="card-title">RESUMEN GENERAL DEL PLAN</div>
    <div class="compare-box" style="border:none;">
      <div class="compare-cell">
        <div class="compare-lbl">Presupuesto Estimado</div>
        <div class="compare-val">{{ $planCurrencySymbol }} {{ number_format($planTotals['estimated'] ?? 0, 2) }}</div>
      </div>
      <div class="compare-cell">
        <div class="compare-lbl">Total en Órdenes de Compra</div>
        <div class="compare-val">{{ $planCurrencySymbol }} {{ number_format($planTotals['orders'] ?? 0, 2) }}</div>
      </div>
      <div class="compare-cell">
        <div class="compare-lbl">Total Sustentado</div>
        <div class="compare-val">{{ $planCurrencySymbol }} {{ number_format($planTotals['supports'] ?? 0, 2) }}</div>
      </div>
    </div>
  </div>

  @if($activities->count() > 0)
    @foreach($activities as $i => $activity)
      @php
        $ordersTotal = $activity->purchaseOrders->sum(fn($o) => (float) $o->amount);
        $supportsTotal = $activity->supports->sum(fn($s) => (float) $s->amount);
        $diff = $ordersTotal - $supportsTotal;
      @endphp
      <div class="activity-block">
        <div class="activity-head">
          <div class="activity-head-name">{{ $i + 1 }}. {{ $activity->name }}</div>
          <div class="activity-head-sub">
            {{ $activity->activity_type ?: 'N/A' }} ·
            {{ MktActivity::STATUS_LABELS[$activity->status] ?? $activity->status }} ·
            Responsable: {{ $activity->responsible ?: 'N/A' }}
          </div>
        </div>
        <div class="activity-body">

          @if($activity->purchaseOrders->count() > 0)
            <div class="sub-title">Órdenes de Compra ({{ $activity->purchaseOrders->count() }})</div>
            <table class="cl">
              <thead>
              <tr>
                <th class="col-num">#</th>
                <th style="text-align:left;">Número</th>
                <th style="text-align:left;">Proveedor</th>
                <th class="col-date">Fecha</th>
                <th class="col-amount">Monto</th>
                <th>Estado</th>
              </tr>
              </thead>
              <tbody>
              @foreach($activity->purchaseOrders->values() as $j => $order)
                <tr>
                  <td class="col-num">{{ $j + 1 }}</td>
                  <td>{{ $order->number ?: '—' }}</td>
                  <td>{{ optional($order->supplier)->full_name ?? '—' }}</td>
                  <td class="col-date">{{ $order->issue_date ? $order->issue_date->format('d/m/Y') : '—' }}</td>
                  <td class="col-amount">{{ $order->currency->symbol ?? 'S/' }} {{ number_format($order->amount ?? 0, 2) }}</td>
                  <td>{{ MktPurchaseOrder::STATUS_LABELS[$order->status] ?? $order->status }}</td>
                </tr>
              @endforeach
              </tbody>
            </table>
          @else
            <div class="empty">Sin órdenes de compra registradas.</div>
          @endif

          @if($activity->supports->count() > 0)
            <div class="sub-title">Sustentos ({{ $activity->supports->count() }})</div>
            <table class="cl">
              <thead>
              <tr>
                <th class="col-num">#</th>
                <th style="text-align:left;">Tipo</th>
                <th style="text-align:left;">Documento</th>
                <th style="text-align:left;">Proveedor</th>
                <th class="col-date">Fecha</th>
                <th class="col-amount">Monto</th>
              </tr>
              </thead>
              <tbody>
              @foreach($activity->supports->values() as $j => $support)
                <tr>
                  <td class="col-num">{{ $j + 1 }}</td>
                  <td>{{ MktSupport::TYPE_LABELS[$support->type] ?? $support->type }}</td>
                  <td>{{ trim(($support->document_series ?? '') . '-' . ($support->document_number ?? ''), '-') ?: '—' }}</td>
                  <td>{{ optional($support->supplier)->full_name ?? '—' }}</td>
                  <td class="col-date">{{ $support->issue_date ? $support->issue_date->format('d/m/Y') : '—' }}</td>
                  <td class="col-amount">{{ $support->currency->symbol ?? 'S/' }} {{ number_format($support->amount ?? 0, 2) }}</td>
                </tr>
              @endforeach
              </tbody>
            </table>
          @else
            <div class="empty">Sin sustentos registrados.</div>
          @endif

          <div class="compare-box">
            <div class="compare-cell">
              <div class="compare-lbl">Total OC</div>
              <div class="compare-val">{{ $activity->currency->symbol ?? 'S/' }} {{ number_format($ordersTotal, 2) }}</div>
            </div>
            <div class="compare-cell">
              <div class="compare-lbl">Total Sustentado</div>
              <div class="compare-val">{{ $activity->currency->symbol ?? 'S/' }} {{ number_format($supportsTotal, 2) }}</div>
            </div>
            <div class="compare-cell">
              <div class="compare-lbl">Diferencia (OC - Sustentado)</div>
              <div class="compare-val {{ $diff > 0.01 ? 'positive' : ($diff < -0.01 ? 'negative' : '') }}">
                {{ $activity->currency->symbol ?? 'S/' }} {{ number_format($diff, 2) }}
              </div>
            </div>
          </div>

        </div>
      </div>
    @endforeach
  @else
    <div class="empty">Este plan aún no tiene actividades registradas.</div>
  @endif

</div>

<div class="footer">
  Automotores Pakatnamu S.A.C. &nbsp;·&nbsp; Reporte completo de plan de marketing &nbsp;·&nbsp;
  PLN-{{ str_pad($plan->id, 6, '0', STR_PAD_LEFT) }} &nbsp;·&nbsp;
  {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}
</div>

</body>
</html>
