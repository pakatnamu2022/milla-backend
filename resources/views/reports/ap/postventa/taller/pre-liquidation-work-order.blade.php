@php
  function getBase64Image($path) {
    $fullPath = public_path($path);
    if (!file_exists($fullPath)) {
      return '';
    }
    $imageData = base64_encode(file_get_contents($fullPath));
    $mimeType = mime_content_type($fullPath);
    return "data:{$mimeType};base64,{$imageData}";
  }

  function formatNumber($amount) {
    return number_format($amount, 2);
  }
@endphp
  <!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Preliquidación {{ $workOrder->correlative }}</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: Arial, sans-serif;
      font-size: 10px;
      padding: 15px;
    }

    .page-border {
      border: 2px solid #000;
      padding: 10px;
    }

    .header-title {
      text-align: center;
      font-size: 14px;
      font-weight: bold;
      background-color: #172e66;
      color: white;
      padding: 8px;
      margin-bottom: 10px;
    }

    .section-box {
      border: 1px solid #000;
      margin-bottom: 5px;
      padding: 5px;
    }

    .section-row {
      display: table;
      width: 100%;
      margin-bottom: 3px;
    }

    .section-label {
      display: table-cell;
      font-weight: bold;
      width: 120px;
      vertical-align: top;
    }

    .section-value {
      display: table-cell;
      vertical-align: top;
    }

    .checkbox-group {
      margin-top: 5px;
    }

    .checkbox-item {
      display: inline-block;
      margin-right: 15px;
      margin-bottom: 3px;
    }

    .checkbox {
      display: inline-block;
      width: 12px;
      height: 12px;
      border: 1px solid #000;
      vertical-align: middle;
      margin-right: 3px;
    }

    .two-column {
      display: table;
      width: 100%;
    }

    .column-left {
      display: table-cell;
      width: 50%;
      padding-right: 5px;
      vertical-align: top;
    }

    .column-right {
      display: table-cell;
      width: 50%;
      padding-left: 5px;
      vertical-align: top;
    }

    .info-table {
      width: 100%;
      font-size: 9px;
      margin-bottom: 5px;
    }

    .info-table td {
      padding: 2px;
      vertical-align: top;
    }

    .info-label {
      font-weight: bold;
      width: 60px;
    }

    .detail-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 9px;
      margin-top: 5px;
    }

    .detail-table th {
      border: 1px solid #000;
      padding: 3px 2px;
      text-align: center;
      font-weight: bold;
      background-color: #e8eef7;
    }

    .detail-table td {
      border: 1px solid #000;
      padding: 3px 2px;
    }

    .text-center {
      text-align: center;
    }

    .text-right {
      text-align: right;
    }

    .text-left {
      text-align: left;
    }

    .bold {
      font-weight: bold;
    }

    .section-title {
      font-weight: bold;
      font-size: 10px;
      margin-bottom: 5px;
      padding: 3px;
      background-color: #f0f0f0;
    }

    .footer-note {
      margin-top: 10px;
      font-size: 9px;
      font-style: italic;
    }

    .page-number {
      text-align: right;
      font-size: 9px;
      margin-top: 5px;
    }
  </style>
</head>
<body>

<div class="page-border">
  <!-- TÍTULO PRINCIPAL -->
  <div class="header-title">PRELIQUIDACIÓN</div>

  <!-- PRIMERA SECCIÓN: CLIENTE E INFORMACIÓN -->
  <div class="two-column">
    <div class="column-left">
      <div class="section-box" style="padding: 10px; min-height: 150px; box-sizing: border-box;">
        <div class="section-row">
          <span class="section-label">CLIENTE</span>
          <span class="section-value">: {{ strtoupper($client->full_name ?? 'N/A') }}</span>
        </div>
        <div class="section-row">
          <span class="section-label">Fec. Recepción</span>
          <span
            class="section-value">: {{ $workOrder->opening_date ? \Carbon\Carbon::parse($workOrder->opening_date)->format('d/m/Y H:i') : 'N/A' }}</span>
        </div>
        <div class="section-row">
          <span class="section-label">Fec. Entrega</span>
          <span class="section-value">:</span>
        </div>
        <div class="section-row">
          <span class="section-label">Fec. Final</span>
          <span class="section-value"></span>
        </div>

        <div class="checkbox-group">
          <div class="checkbox-item">
            <span class="checkbox"></span> Espera Trabajo
          </div>
          <div class="checkbox-item">
            <span class="checkbox"></span> Requiere Repuestos
          </div>
          <div class="checkbox-item">
            <span class="checkbox"></span> Pide Repuestos
          </div>
          <div class="checkbox-item">
            <span class="checkbox"></span> Vehículo con Campaña NRO:
          </div>
        </div>

        <div class="section-row" style="margin-top: 10px;">
          <span class="section-label">AUTORIZACIÓN ADICIONAL :</span>
          <span class="section-value" style="margin-left: 100px;">$</span>
        </div>
      </div>
    </div>

    <div class="column-right">
      <div class="section-box" style="text-align: center; padding: 10px; min-height: 150px; box-sizing: border-box;">
        <div style="font-size: 10px; font-weight: bold; margin-bottom: 5px;">
          {{ $workOrder->appointment_planning_id !== null ? 'CON CITA' : 'SIN CITA' }}
        </div>
        <div style="font-size: 10px; font-weight: bold; margin-bottom: 5px;">ORDEN DE TRABAJO</div>
        <div style="font-size: 9px; margin-bottom: 8px;">AUTOMOTORES PAKATNAMU S.A.C. |
          {{ $workOrder->sede->abreviatura ?? 'N/A' }}</div>
        <div style="margin-bottom: 5px;">
          <span style="font-weight: bold;">NÚMERO</span>
          <span style="margin-left: 10px;">: {{ $workOrder->correlative }}</span>
        </div>
        <div style="margin-bottom: 5px;">
          <span style="font-weight: bold;">PLACA</span>
          <span style="margin-left: 10px;">: {{ $vehicle->plate ?? 'N/A' }}</span>
        </div>
        <div style="margin-bottom: 5px; font-size: 9px;">
          <span style="font-weight: bold;">TIPO SERVICIO</span>
          <span>: {{ $workOrder->items->first()->typePlanning->description ?? 'N/A' }}</span>
        </div>
        <div style="margin-bottom: 5px; font-size: 9px;">
          <span style="font-weight: bold;">ASISTENTE</span>
          <span class="checkbox" style="margin-left: 10px;"></span>
        </div>
        <div style="font-size: 9px;">
          <span style="font-weight: bold;">FEC. ASISTENCIA</span>
        </div>
      </div>
    </div>
  </div>

  <!-- INFORMACIÓN DEL VEHÍCULO Y CLIENTE -->
  <div class="two-column">
    <div class="column-left">
      <div class="section-box">
        <table class="info-table">
          <tr>
            <td class="info-label">RUC</td>
            <td>: {{ $client->num_doc ?? 'N/A' }}</td>
          </tr>
          <tr>
            <td class="info-label">CLIENTE</td>
            <td>: {{ strtoupper($client->full_name ?? 'N/A') }}</td>
          </tr>
          <tr>
            <td class="info-label">DIRECCIÓN</td>
            <td>: {{ strtoupper($client->direction ?? 'N/A') }}</td>
          </tr>
          <tr>
            <td class="info-label">TELÉFONO</td>
            <td>: {{ $client->phone ?? 'N/A' }}</td>
          </tr>
          <tr>
            <td class="info-label">E_MAIL</td>
            <td>: {{ strtolower($client->email ?? 'N/A') }}</td>
          </tr>
        </table>
      </div>
    </div>

    <div class="column-right">
      <div class="section-box">
        <table class="info-table">
          <tr>
            <td class="info-label">PLACA</td>
            <td>: {{ $vehicle->plate ?? 'N/A' }}</td>
            <td class="info-label">KM</td>
            <td>: {{ $workOrder->vehicleInspection->mileage ?? $workOrder->apVehicleInspection->mileage ?? 'N/A' }}</td>
          </tr>
          <tr>
            <td class="info-label">VEHÍCULO</td>
            <td colspan="3">: {{ $vehicle->model->name ?? 'N/A' }}</td>
          </tr>
          <tr>
            <td class="info-label">AÑO</td>
            <td>: {{ $vehicle->year ?? 'N/A' }}</td>
            <td class="info-label">VIN</td>
            <td>: {{ $vehicle->vin ?? 'N/A' }}</td>
          </tr>
          <tr>
            <td class="info-label">CHASIS</td>
            <td colspan="3">: {{ $vehicle->vin ?? 'N/A' }}</td>
          </tr>
          <tr>
            <td class="info-label">FEC. VENTA</td>
            <td>: {{ $vehicle->created_at ? \Carbon\Carbon::parse($vehicle->created_at)->format('d/m/Y') : 'N/A' }}</td>
          </tr>
        </table>
      </div>
    </div>
  </div>

  <!-- NRO PRESUPUESTO Y RECEPCIONISTA -->
  <div class="section-box">
    <table class="info-table">
      <tr>
        <td class="info-label" style="width: 110px;">NRO PRESUPUESTO</td>
        <td style="width: 200px;">:</td>
        <td class="info-label" style="width: 110px;">TEL. RECEPCIONISTA</td>
        <td>: {{ strtoupper($workOrder->advisor->tel_referencia_3 ?? 'N/A') }}</td>
      </tr>
      <tr>
        <td class="info-label">EMAIL RECEPCIONISTA</td>
        <td>: {{ strtolower($workOrder->advisor->email2 ?? 'N/A') }}</td>
        <td class="info-label">RECEPCIONISTA</td>
        <td>: {{ strtoupper($workOrder->advisor->nombre_completo ?? 'N/A') }}</td>
      </tr>
      <tr>
        <td class="info-label">SINIESTRO</td>
        <td>:</td>
        <td class="info-label">LIQ.</td>
        <td>:</td>
      </tr>
    </table>
  </div>

  <!-- REQUERIMIENTO CLIENTE -->
  <div class="section-box">
    <div style="display: table; width: 100%;">
      <div style="display: table-row;">
        <div
          style="display: table-cell; width: 50%; vertical-align: top; padding-right: 10px; border-right: 1px solid #000;">
          <div style="font-weight: bold; margin-bottom: 5px;">REQUERIMIENTO CLIENTE</div>
          <div style="margin-left: 20px; font-size: 9px; min-height: 40px;">
            {{ $workOrder->items->pluck('description')->implode(', ') ?: 'N/A' }}
          </div>
        </div>
        <div style="display: table-cell; width: 50%; vertical-align: top; padding-left: 10px;">
          <div style="font-weight: bold; margin-bottom: 5px; text-align: center;">RESULTADO REQUERIMIENTO</div>
        </div>
      </div>
    </div>
    <div style="margin-top: 10px; border-top: 1px solid #000; padding-top: 5px;">
      <table style="width: 100%;">
        <tr>
          <td style="width: 25%; vertical-align: top;">
            <div class="bold">CUANDO</div>
            <div class="bold">FRECUENCIA</div>
          </td>
          <td style="width: 25%; vertical-align: top;">
            <div class="bold">C.CALIDAD</div>
          </td>
          <td style="width: 25%; vertical-align: top;">
            <div class="bold">DONDE</div>
          </td>
          <td style="width: 25%; vertical-align: top;">
            <div class="bold">LUZ ADVERTENCIA</div>
          </td>
        </tr>
      </table>
    </div>
  </div>

  <!-- TABLA DE DETALLES -->
  <table class="detail-table">
    <thead>
    <tr>
      <th style="width: 40%;">DESCRIPCIÓN</th>
      <th style="width: 10%;">MC</th>
      <th style="width: 10%;">CANTIDAD / HRS</th>
      <th style="width: 10%;">DESCTO</th>
      <th style="width: 15%;">P.UNITARIO</th>
      <th style="width: 15%;">TOTAL</th>
    </tr>
    </thead>
    <tbody>
    <!-- RECAMBIOS (REPUESTOS) -->
    @if($parts->count() > 0)
      <tr>
        <td colspan="6" style="font-weight: bold; padding: 5px 3px;">RECAMBIOS</td>
      </tr>
      @foreach($parts as $part)
        <tr>
          <td class="text-left">{{ strtoupper($part->product->name ?? 'N/A') }}</td>
          <td class="text-center"></td>
          <td class="text-center">{{ formatNumber($part->quantity_used) }}</td>
          <td class="text-right">{{ formatNumber($part->discount_percentage ?? 0) }}</td>
          <td class="text-right">{{ formatNumber($part->unit_price) }}</td>
          <td class="text-right">{{ formatNumber($part->total_amount) }}</td>
        </tr>
      @endforeach
    @endif

    <!-- SERVICIOS (MANO DE OBRA) -->
    @if($labours->count() > 0)
      <tr>
        <td colspan="6" style="font-weight: bold; padding: 5px 3px;">SERVICIOS</td>
      </tr>
      @foreach($labours as $labour)
        <tr>
          <td class="text-left">{{ strtoupper($labour->description) }}</td>
          <td class="text-center"></td>
          <td class="text-center">{{ $labour->time_spent }}</td>
          <td class="text-right">0.00</td>
          <td class="text-right">{{ formatNumber($labour->hourly_rate) }}</td>
          <td class="text-right">{{ formatNumber($labour->total_cost) }}</td>
        </tr>
      @endforeach
    @endif

    @if($parts->count() == 0 && $labours->count() == 0)
      <tr>
        <td colspan="6" class="text-center" style="padding: 20px; font-style: italic; color: #999;">
          No hay detalles registrados
        </td>
      </tr>
    @endif
    </tbody>
  </table>

  <!-- NOTA INICIAL -->
  <div class="footer-note">
    Esta Orden Inicial da un valor estimado de la reparación antes de desarmar el vehículo
  </div>

  <!-- RESULTADO ENTREGA -->
  <div class="section-box" style="margin-top: 10px;">
    <div style="display: table; width: 100%;">
      <div
        style="display: table-cell; width: 65%; vertical-align: top; padding-right: 10px;">
        <div class="bold" style="margin-bottom: 5px;">RESULTADO ENTREGA</div>
        <table style="width: 100%; border-collapse: collapse; margin-top: 5px;">
          <tr>
            <td style="width: 50%; padding: 3px 10px 3px 0; vertical-align: middle;">
              <span class="checkbox"></span> Chequeó Anomalías
            </td>
            <td style="width: 50%; padding: 3px 0; vertical-align: middle;">
              <span class="checkbox"></span> Verificó el Precio al Cliente
            </td>
          </tr>
          <tr>
            <td style="padding: 3px 10px 3px 0; vertical-align: middle;">
              <span class="checkbox"></span> Explicaciones de la Orden de Trabajo
            </td>
            <td style="padding: 3px 0; vertical-align: middle;">
              <span class="checkbox"></span> Entregó Libro de Garantía Firmado
            </td>
          </tr>
          <tr>
            <td style="padding: 3px 10px 3px 0; vertical-align: middle;">
              <span class="checkbox"></span> Informaron su Próxima Revisión
            </td>
            <td style="padding: 3px 0; vertical-align: middle;">
              <span class="checkbox"></span> Recorrió el Vehículo con el Cliente
            </td>
          </tr>
          <tr>
            <td style="padding: 3px 10px 3px 0; vertical-align: middle;">
              <span class="checkbox"></span> Informaron Contacto Telefónico
            </td>
            <td style="padding: 3px 0; vertical-align: middle;">
              <span class="checkbox"></span> Confirmó el resultado con el Cliente
            </td>
          </tr>
        </table>
        <div style="text-align: center; margin-top: 10px;">
          {{ strtoupper($workOrder->advisor->nombre_completo ?? 'N/A') }}
        </div>
      </div>
      <div style="display: table-cell; width: 35%; vertical-align: top; padding-left: 10px;">
        <table style="width: 100%; border-collapse: collapse; font-size: 9px;">
          <tr>
            <td colspan="2"
                style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #172e66; color: white; text-align: center;">
              RESUMEN
            </td>
          </tr>
          <tr>
            <td style="border: 1px solid #000; padding: 3px 6px; font-weight: bold; background-color: #f5f5f5;">Total
            </td>
            <td
              style="border: 1px solid #000; padding: 3px 6px; text-align: right;">{{ formatNumber($totals['subtotal']) }}</td>
          </tr>
          <tr>
            <td style="border: 1px solid #000; padding: 3px 6px; font-weight: bold; background-color: #f5f5f5;">
              Descuento
            </td>
            <td
              style="border: 1px solid #000; padding: 3px 6px; text-align: right;">{{ formatNumber($totals['discount_amount']) }}</td>
          </tr>
          <tr>
            <td style="border: 1px solid #000; padding: 3px 6px; font-weight: bold; background-color: #f5f5f5;">Total
              Neto
            </td>
            <td
              style="border: 1px solid #000; padding: 3px 6px; text-align: right;">{{ formatNumber($totals['subtotal'] - $totals['discount_amount']) }}</td>
          </tr>
          <tr>
            <td style="border: 1px solid #000; padding: 3px 6px; font-weight: bold; background-color: #f5f5f5;">Total
              IGV
            </td>
            <td
              style="border: 1px solid #000; padding: 3px 6px; text-align: right;">{{ formatNumber($totals['tax_amount']) }}</td>
          </tr>
          <tr>
            <td style="border: 1px solid #000; padding: 4px 6px; font-weight: bold; background-color: #e8eef7;">Total
            </td>
            <td
              style="border: 1px solid #000; padding: 4px 6px; text-align: right; font-weight: bold; background-color: #e8eef7;">{{ formatNumber($totals['total_amount']) }}</td>
          </tr>
        </table>
      </div>
    </div>
  </div>

  <!-- SEGUIMIENTO POST-SERVICIO -->
  <div class="section-box" style="margin-top: 5px; padding-top: 20px; padding-bottom: 20px;">
    <div style="display: table; width: 100%;">
      <div
        style="display: table-cell; width: 65%; vertical-align: top; padding-right: 10px;">
        <div class="bold" style="margin-bottom: 5px;">SEGUIMIENTO POST-SERVICIO</div>
        <div style="font-size: 9px;">
          <span class="bold">LLAMAR</span>
        </div>
        <div style="font-size: 9px;">
          <span class="bold">FECHA HORA :</span>
        </div>
      </div>
      <div style="display: table-cell; width: 35%; vertical-align: bottom; padding-left: 10px; padding-bottom: 5px;">
        <div style="border-bottom: 1px solid #000; width: 100%; margin-bottom: 4px;"></div>
        <div style="font-size: 9px; text-align: center;">{{ strtoupper($client->full_name ?? 'N/A') }}</div>
      </div>
    </div>
  </div>

  <!-- NÚMERO DE PÁGINA -->
  <div class="page-number">
    Fecha/Hora Impresión: {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }} | Pág. 1 de 2
  </div>
</div>

</body>
</html>

