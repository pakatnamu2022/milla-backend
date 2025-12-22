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
@endphp
<!doctype html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Liquidación de Gastos</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: Arial, sans-serif;
      font-size: 10px;
      padding: 30px;
    }

    .header {
      text-align: center;
      margin-bottom: 20px;
    }

    .company-name {
      font-size: 14px;
      font-weight: bold;
      margin-bottom: 5px;
    }

    .title {
      background-color: #e0e0e0;
      padding: 8px;
      font-size: 14px;
      font-weight: bold;
      text-align: center;
      margin-bottom: 15px;
      border: 1px solid #000;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 15px;
    }

    table, th, td {
      border: 1px solid #000;
    }

    td, th {
      padding: 5px 8px;
      vertical-align: top;
    }

    th {
      background-color: #e0e0e0;
      font-weight: bold;
      text-align: center;
    }

    .label {
      font-weight: bold;
      font-size: 9px;
    }

    .section-title {
      background-color: #d0d0d0;
      font-weight: bold;
      padding: 6px;
      text-align: center;
      font-size: 11px;
    }

    .text-center {
      text-align: center;
    }

    .text-right {
      text-align: right;
    }

    .highlight {
      background-color: #ffff00;
      padding: 3px;
    }

    .dotted-line {
      border-bottom: 1px dotted #000;
      min-height: 18px;
      display: inline-block;
      width: 100%;
    }

    .signature-section {
      margin-top: 30px;
    }

    .signature-box {
      min-height: 60px;
      text-align: center;
      vertical-align: bottom;
      padding-top: 40px;
    }

    .note {
      background-color: #ffff00;
      padding: 8px;
      margin-top: 15px;
      border: 1px solid #000;
      font-size: 9px;
      font-weight: bold;
    }
  </style>
</head>
<body>

<!-- Encabezado -->
<div class="header">
  <div class="company-name">{{ $request['company']['name'] ?? 'NOMBRE DE LA EMPRESA' }}</div>
</div>

<!-- Título -->
<div class="title">LIQUIDACIÓN DE GASTOS</div>

<!-- Datos Generales -->
<table>
  <tr>
    <th colspan="6" class="section-title">DATOS GENERALES</th>
  </tr>
  <tr>
    <td class="label" style="width: 20%;">Nombre del empleado:</td>
    <td colspan="3">{{ $request['employee']['full_name'] ?? '' }}</td>
    <td class="label" style="width: 15%;">Área:</td>
    <td>{{ $request['employee']['position']['area']['name'] ?? '' }}</td>
  </tr>
  <tr>
    <td class="label">Cargo del empleado:</td>
    <td colspan="5">{{ $request['employee']['position']['name'] ?? '' }}</td>
  </tr>
  <tr>
    <td class="label">Lugar de destino:</td>
    <td colspan="3">{{ $request['district']['name'] ?? '' }}</td>
    <td class="label">Zona:</td>
    <td>{{ ($request['district']['zone'] ?? 'Nacional') }}</td>
  </tr>
  <tr>
    <td class="label">Objetivo del viaje:</td>
    <td colspan="5">{{ $request['purpose'] ?? '' }}</td>
  </tr>
  <tr>
    <td class="label">Fecha de inicio:</td>
    <td>{{ \Carbon\Carbon::parse($request['start_date'])->format('d/m/Y') }}</td>
    <td class="label" style="width: 15%;">Fecha fin:</td>
    <td>{{ \Carbon\Carbon::parse($request['end_date'])->format('d/m/Y') }}</td>
    <td class="label">Moneda:</td>
    <td>S/. - Nuevos Soles</td>
  </tr>
  <tr>
    <td class="label">Importe otorgado:</td>
    <td colspan="5" class="highlight">S/. {{ number_format($request['total_budget'] ?? 0, 2) }}</td>
  </tr>
</table>

<!-- Liquidación de Gastos - Viáticos con Comprobantes -->
<table>
  <tr>
    <th colspan="3" class="section-title">LIQUIDACIÓN DE GASTOS - VIÁTICOS CON COMPROBANTES</th>
  </tr>
  <tr>
    <th style="width: 50%;">Concepto de Gastos</th>
    <th style="width: 20%;">TOTAL</th>
    <th style="width: 30%;">Observaciones</th>
  </tr>

  @php
    $conceptGroups = $expensesWithReceipts->groupBy('expense_type.name');
  @endphp

  @if($conceptGroups->count() > 0)
    @foreach($conceptGroups as $conceptName => $expenses)
      <tr>
        <td>{{ $conceptName }}</td>
        <td class="text-right">S/. {{ number_format($expenses->sum('company_amount'), 2) }}</td>
        <td>{{ $expenses->pluck('notes')->filter()->implode(', ') }}</td>
      </tr>
    @endforeach
  @else
    <tr>
      <td>Alojamiento</td>
      <td class="text-right">S/. 0.00</td>
      <td></td>
    </tr>
    <tr>
      <td>Alimentación</td>
      <td class="text-right">S/. 0.00</td>
      <td></td>
    </tr>
    <tr>
      <td>Boletos de viaje</td>
      <td class="text-right">S/. 0.00</td>
      <td></td>
    </tr>
    <tr>
      <td>Transporte personal</td>
      <td class="text-right">S/. 0.00</td>
      <td></td>
    </tr>
    <tr>
      <td>Otros gastos</td>
      <td class="text-right">S/. 0.00</td>
      <td></td>
    </tr>
  @endif

  <tr>
    <td class="label text-right">SUB-TOTAL CON COMPROBANTES:</td>
    <td class="text-right label">S/. {{ number_format($totalWithReceipts, 2) }}</td>
    <td></td>
  </tr>
</table>

<!-- Gastos Sin Comprobantes -->
<table>
  <tr>
    <th colspan="3" class="section-title">GASTOS SIN COMPROBANTES</th>
  </tr>
  <tr>
    <th style="width: 50%;">Concepto de Gastos</th>
    <th style="width: 20%;">TOTAL</th>
    <th style="width: 30%;">Observaciones</th>
  </tr>

  @php
    $conceptGroupsNoReceipt = $expensesWithoutReceipts->groupBy('expense_type.name');
  @endphp

  @if($conceptGroupsNoReceipt->count() > 0)
    @foreach($conceptGroupsNoReceipt as $conceptName => $expenses)
      <tr>
        <td>{{ $conceptName }}</td>
        <td class="text-right">S/. {{ number_format($expenses->sum('company_amount'), 2) }}</td>
        <td>{{ $expenses->pluck('notes')->filter()->implode(', ') }}</td>
      </tr>
    @endforeach
  @else
    <tr>
      <td>Peaje</td>
      <td class="text-right">S/. 0.00</td>
      <td></td>
    </tr>
    <tr>
      <td>Gasolina</td>
      <td class="text-right">S/. 0.00</td>
      <td></td>
    </tr>
    <tr>
      <td>Planilla movilidad</td>
      <td class="text-right">S/. 0.00</td>
      <td></td>
    </tr>
  @endif

  <tr>
    <td class="label text-right">SUB-TOTAL SIN COMPROBANTES:</td>
    <td class="text-right label">S/. {{ number_format($totalWithoutReceipts, 2) }}</td>
    <td></td>
  </tr>
</table>

<!-- Resumen de la Liquidación -->
<table>
  <tr>
    <th colspan="2" class="section-title">RESUMEN DE LA LIQUIDACIÓN</th>
  </tr>
  <tr>
    <td class="label" style="width: 70%;">Importe otorgado para viáticos:</td>
    <td class="text-right highlight">S/. {{ number_format($request['total_budget'] ?? 0, 2) }}</td>
  </tr>
  <tr>
    <td class="label">Total general de gastos:</td>
    <td class="text-right">S/. {{ number_format($totalGeneral, 2) }}</td>
  </tr>
  <tr>
    <td class="label">Saldo {{ $saldo >= 0 ? '(a favor de la empresa)' : '(a favor del empleado)' }}:</td>
    <td class="text-right label">S/. {{ number_format(abs($saldo), 2) }}</td>
  </tr>
</table>

<!-- Nota Importante -->
<div class="note">
  Importes referenciales, los documentos originales deben ser enviados adjuntando la transferencia realizada por Tesorería
</div>

<!-- Firmas -->
<table class="signature-section">
  <tr>
    <td class="signature-box" style="width: 33%;">
      <div class="dotted-line"></div>
      <div class="label" style="margin-top: 5px;">Firma del Empleado</div>
      <div style="margin-top: 3px;">{{ $request['employee']['full_name'] ?? '' }}</div>
      <div style="margin-top: 3px;">{{ $request['employee']['position']['name'] ?? '' }}</div>
    </td>
    <td class="signature-box" style="width: 33%;">
      <div class="dotted-line"></div>
      <div class="label" style="margin-top: 5px;">Responsable</div>
      <div style="margin-top: 3px;">_______________________</div>
      <div style="margin-top: 3px;">Cargo: _________________</div>
    </td>
    <td class="signature-box" style="width: 34%;">
      <div class="dotted-line"></div>
      <div class="label" style="margin-top: 5px;">Autorizado por</div>
      <div style="margin-top: 3px;">_______________________</div>
      <div style="margin-top: 3px;">Cargo: _________________</div>
    </td>
  </tr>
</table>

</body>
</html>
