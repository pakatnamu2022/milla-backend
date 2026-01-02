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
  <title>Detalle de Gastos del Personal</title>
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

    .subsection-title {
      background-color: #e8e8e8;
      font-weight: bold;
      padding: 5px;
      text-align: left;
      font-size: 10px;
    }

    .text-center {
      text-align: center;
    }

    .text-right {
      text-align: right;
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
  </style>
</head>
<body>

<!-- Título -->
<div class="title">DETALLE DE GASTOS DEL PERSONAL</div>

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
    <td class="label">Empresa de servicio:</td>
    <td colspan="5">{{ $request['company_service']['name'] ?? '' }}</td>
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
    <td colspan="5">S/. {{ number_format($importeOtorgado ?? 0, 2) }}</td>
  </tr>
</table>

<!-- GASTOS DEL COLABORADOR -->
@if(count($expenseCategories) > 0)
  <table>
    <tr>
      <th colspan="7" class="section-title">GASTOS DEL COLABORADOR</th>
    </tr>
    <tr>
      <th style="width: 10%;">FECHA</th>
      <th style="width: 12%;">N° COMPROBANTE</th>
      <th style="width: 20%;">RAZÓN SOCIAL</th>
      <th style="width: 23%;">DETALLE</th>
      <th style="width: 12%;">TOTAL GASTADO</th>
      <th style="width: 12%;">CUBIERTO EMPRESA</th>
      <th style="width: 11%;">CUBIERTO POR MÍ</th>
    </tr>

    @foreach($expenseCategories as $category)
      <!-- {{ $category['type_name'] }} -->
      <tr>
        <td colspan="7" class="subsection-title">{{ strtoupper($category['type_name']) }}</td>
      </tr>
      @foreach($category['expenses'] as $expense)
        <tr>
          <td
            class="text-center">{{ isset($expense['expense_date']) ? \Carbon\Carbon::parse($expense['expense_date'])->format('d/m/Y') : '-' }}</td>
          <td class="text-center">{{ $expense['receipt_number'] ?? 'SIN COMPROBANTE' }}</td>
          <td>{{ $expense['business_name'] ?? '-' }}</td>
          <td>{{ $expense['detalle'] ?? '-' }}</td>
          <td class="text-right">S/. {{ number_format($expense['receipt_amount'] ?? 0, 2) }}</td>
          <td class="text-right">S/. {{ number_format($expense['company_amount'] ?? 0, 2) }}</td>
          <td class="text-right">S/. {{ number_format($expense['employee_amount'] ?? 0, 2) }}</td>
        </tr>
      @endforeach
      <tr style="background-color: #f0f0f0;">
        <td colspan="4" class="text-right label">TOTAL {{ strtoupper($category['type_name']) }}:</td>
        <td class="text-right label">S/. {{ number_format($category['total_receipt'], 2) }}</td>
        <td class="text-right label">S/. {{ number_format($category['total_company'], 2) }}</td>
        <td class="text-right label">S/. {{ number_format($category['total_employee'], 2) }}</td>
      </tr>
    @endforeach

    <!-- Total General -->
    <tr style="background-color: #d0d0d0;">
      <td colspan="4" class="text-right label" style="font-size: 11px;">TOTAL GENERAL DE GASTOS:</td>
      <td class="text-right label" style="font-size: 11px;">S/. {{ number_format($totalGeneralReceipt, 2) }}</td>
      <td class="text-right label" style="font-size: 11px;">S/. {{ number_format($totalGeneralCompany, 2) }}</td>
      <td class="text-right label" style="font-size: 11px;">S/. {{ number_format($totalGeneralEmployee, 2) }}</td>
    </tr>
  </table>
@endif

<!-- Resumen -->
<table>
  <tr>
    <th colspan="2" class="section-title">RESUMEN</th>
  </tr>
  <tr>
    <td class="label" style="width: 70%;">Importe otorgado para viáticos:</td>
    <td class="text-right">S/. {{ number_format($importeOtorgado ?? 0, 2) }}</td>
  </tr>
  <tr>
    <td class="label">Total de gastos del personal:</td>
    <td class="text-right">S/. {{ number_format($totalGeneralCompany, 2) }}</td>
  </tr>
  <tr>
    <td class="label">Monto a devolver y/o reembolso de gastos:</td>
    <td class="text-right label">
      @if($montoDevolver > 0)
        S/. {{ number_format($montoDevolver, 2) }} (A DEVOLVER)
      @elseif($montoDevolver < 0)
        S/. {{ number_format(abs($montoDevolver), 2) }} (A REEMBOLSAR)
      @else
        S/. 0.00
      @endif
    </td>
  </tr>
</table>

<!-- Firmas -->
@php
  $boss = $request['employee']['boss'] ?? null;

  // Buscar el approval aprobado
  $approverName = null;
  $approverPosition = null;

  if (isset($request['approvals'])) {
    $approvals = $request['approvals'];

    // Convertir a array si es necesario
    if (is_object($approvals)) {
      $approvals = json_decode(json_encode($approvals), true);
    }

    if (is_array($approvals) || is_iterable($approvals)) {
      foreach ($approvals as $approval) {
        // Convertir a array si es objeto
        if (is_object($approval)) {
          $approval = json_decode(json_encode($approval), true);
        }

        $status = $approval['status'] ?? null;

        if ($status === 'approved') {
          $approverData = $approval['approver'] ?? null;

          if ($approverData) {
            // Convertir a array si es objeto
            if (is_object($approverData)) {
              $approverData = json_decode(json_encode($approverData), true);
            }

            $approverName = $approverData['full_name'] ?? null;
            $approverPosition = $approverData['position']['name'] ?? null;
            break;
          }
        }
      }
    }
  }
@endphp
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
      @if($boss)
        <div style="margin-top: 3px;">{{ $boss['full_name'] ?? '' }}</div>
        <div style="margin-top: 3px;">{{ $boss['position']['name'] ?? '' }}</div>
      @else
        <div style="margin-top: 3px;">_______________________</div>
        <div style="margin-top: 3px;">Cargo: _________________</div>
      @endif
    </td>
    <td class="signature-box" style="width: 34%;">
      <div class="dotted-line"></div>
      <div class="label" style="margin-top: 5px;">Autorizado por</div>
      @if($approverName)
        <div style="margin-top: 3px;">{{ $approverName }}</div>
        <div style="margin-top: 3px;">{{ $approverPosition ?? '' }}</div>
      @else
        <div style="margin-top: 3px;">_______________________</div>
        <div style="margin-top: 3px;">Cargo: _________________</div>
      @endif
    </td>
  </tr>
</table>

</body>
</html>
