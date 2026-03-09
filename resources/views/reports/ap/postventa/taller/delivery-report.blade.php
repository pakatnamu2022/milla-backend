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
  <title>Orden de Recepción {{ $workOrder->correlative }}</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: Arial, sans-serif;
      font-size: 9px;
      padding: 15px;
    }

    .red-line {
      width: 100%;
      height: 10px;
      background-color: #ff0000;
      margin-bottom: 0;
    }

    .header {
      margin-bottom: 15px;
      margin-top: 0;
    }

    .header table {
      width: 100%;
      border: none;
      border-spacing: 0;
    }

    .header td {
      border: none;
      vertical-align: top;
      padding: 0;
    }

    .header-left {
      width: 50%;
      padding-right: 10px;
      padding-top: 7px;
    }

    .header-right {
      width: 50%;
      padding-left: 10px;
      text-align: right;
    }

    .company-info-container {
      display: table;
      width: 100%;
      margin-top: 2px;
    }

    .company-logo {
      display: table-cell;
      vertical-align: middle;
      width: 60px;
    }

    .company-logo img {
      max-width: 60px;
      max-height: 45px;
      height: auto;
      display: block;
    }

    .company-text {
      display: table-cell;
      vertical-align: middle;
    }

    .company-name {
      font-size: 14px;
      color: #ff0000;
      font-weight: bold;
      line-height: 1.2;
    }

    .company-website {
      font-size: 7px;
      color: #000;
      display: inline;
      margin-left: 5px;
    }

    .company-addresses {
      font-size: 8px;
      color: #000;
      margin-top: 5px;
      line-height: 1.4;
    }

    .company-addresses strong {
      font-weight: bold;
    }

    .work-order-title {
      background-color: #000;
      color: white;
      font-size: 20px;
      font-weight: bold;
      padding: 5px 10px;
      display: inline-block;
      margin: 0;
    }

    .logos-guarantee-container {
      margin-top: 20px;
      display: table;
      width: 100%;
      border-collapse: collapse;
    }

    .logo-section {
      display: table-cell;
      vertical-align: middle;
      padding-right: 10px;
      text-align: right;
      height: 60px;
    }

    .logo-section img {
      max-height: 50px;
      height: auto;
      display: inline-block;
      vertical-align: middle;
    }

    .guarantee-check-box {
      display: table-cell;
      border: 1px solid #000;
      vertical-align: middle;
      width: 120px;
      height: 60px;
    }

    .guarantee-check-title {
      font-size: 8px;
      font-weight: bold;
      padding: 5px;
      text-align: center;
      background-color: #f0f0f0;
      border-bottom: 1px solid #000;
    }

    .guarantee-check-options {
      display: table;
      width: 100%;
      border-collapse: collapse;
    }

    .guarantee-option {
      display: table-cell;
      width: 50%;
      font-size: 9px;
      font-weight: bold;
      text-align: center;
      padding: 5px;
      border-right: 1px solid #000;
    }

    .guarantee-option:last-child {
      border-right: none;
    }

    .section-title {
      background-color: #172e66;
      color: white;
      font-weight: bold;
      font-size: 10px;
      padding: 5px;
      text-align: left;
      border: 1px solid #000;
      margin-top: 10px;
      margin-bottom: 5px;
    }

    table.items-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 10px;
      font-size: 8px;
    }

    table.items-table th {
      background-color: #172e66;
      color: white;
      font-weight: bold;
      padding: 5px;
      text-align: center;
      border: 1px solid #000;
    }

    table.items-table td {
      padding: 4px;
      border: 1px solid #000;
      vertical-align: middle;
    }

    .vehicle-inspection-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 8px;
      font-size: 8px;
      border: 1px solid #000;
      page-break-inside: avoid;
    }

    .vehicle-inspection-table td {
      padding: 5px;
      border-right: 1px solid #000;
      vertical-align: top;
    }

    .vehicle-inspection-table td:last-child {
      border-right: none;
    }

    .inspection-cell-vehicle {
      width: 28%;
      text-align: center;
    }

    .inspection-cell-inventory {
      width: 47%;
    }

    .inspection-cell-boxes {
      width: 25%;
    }

    .inventory-item {
      margin-bottom: 2px;
      line-height: 1.2;
      font-size: 7.5px;
    }

    .inventory-extra-fields {
      margin-top: 6px;
      padding-top: 6px;
      border-top: 1px solid #ccc;
    }

    .inventory-extra-item {
      margin-bottom: 3px;
      font-size: 7.5px;
      line-height: 1.2;
    }

    .inventory-extra-item strong {
      font-weight: bold;
      margin-right: 3px;
    }

    .inventory-box {
      border: 1px solid #172e66;
      margin-bottom: 3px;
      background-color: #f8f9fc;
      page-break-inside: avoid;
    }

    .inventory-box-title {
      font-weight: bold;
      font-size: 7px;
      text-align: center;
      color: white;
      background-color: #172e66;
      padding: 2px;
      border-bottom: 1px solid #172e66;
    }

    .inventory-box-content {
      font-size: 6.5px;
      line-height: 1.2;
      padding: 3px;
    }

    .inventory-box-item {
      margin-bottom: 1px;
    }

    .mini-checkbox {
      display: inline-block;
      width: 7px;
      height: 7px;
      border: 1px solid #000;
      margin-right: 3px;
      margin-top: 1px;
      background-color: white;
      vertical-align: middle;
    }

    .mini-checkbox.checked::before {
      content: "✓";
      font-size: 6px;
      line-height: 7px;
      display: block;
      text-align: center;
      font-weight: bold;
    }

    .odometer-value {
      font-size: 9px;
      font-weight: bold;
      text-align: center;
      padding: 4px;
      background-color: white;
      border: 1px solid #172e66;
      margin-top: 2px;
      color: #172e66;
    }

    .checkbox {
      display: inline-block;
      width: 10px;
      height: 10px;
      border: 1.5px solid #000;
      margin-right: 5px;
      text-align: center;
      line-height: 8px;
      font-size: 10px;
      font-weight: bold;
      vertical-align: middle;
      background-color: white;
    }

    .checkbox.checked::before {
      content: "X";
      color: #000;
    }

    .vehicle-state-container {
      width: 100%;
      max-width: 180px;
      margin: 0 auto;
      padding: 5px;
      background-color: #f9f9f9;
      page-break-inside: avoid;
    }

    .vehicle-image-wrapper {
      position: relative;
      width: 100%;
      height: 240px;
      display: block;
    }

    .vehicle-state-container img {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: contain;
    }

    .damage-marker {
      position: absolute;
      width: 14px;
      height: 14px;
      background-color: #ff0000;
      border: 2px solid #000;
      border-radius: 50%;
      font-size: 8px;
      color: white;
      text-align: center;
      line-height: 10px;
      font-weight: bold;
      transform: translate(-50%, -50%);
      z-index: 999;
    }

    .no-damages-text {
      text-align: center;
      color: #666;
      font-size: 9px;
      padding: 8px;
      font-style: italic;
      position: relative;
    }

    .damages-list {
      font-size: 8px;
      margin-top: 10px;
    }

    .damages-list table {
      width: 100%;
      border-collapse: collapse;
    }

    .damages-list td {
      padding: 3px;
      border: 1px solid #000;
    }

    .important-section {
      border: 1px solid #000;
      padding: 8px;
      margin-top: 10px;
      margin-bottom: 10px;
      min-height: 80px;
    }

    .important-title {
      font-weight: bold;
      font-size: 10px;
      margin-bottom: 5px;
      text-decoration: underline;
    }

    .signatures {
      margin-top: 20px;
    }

    .signatures table {
      width: 100%;
      border-collapse: collapse;
    }

    .signatures td {
      width: 50%;
      text-align: center;
      vertical-align: bottom;
      padding: 10px;
    }

    .signature-box {
      border-top: 2px solid #000;
      margin-top: 0;
      padding-top: 5px;
      font-size: 9px;
      font-weight: bold;
    }

    .signature-img {
      max-width: 200px;
      max-height: 80px;
      margin-bottom: 0;
      display: block;
      margin-left: auto;
      margin-right: auto;
    }

    .text-center {
      text-align: center;
    }

    .text-right {
      text-align: right;
    }


    .damage-evidence-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
      margin-bottom: 10px;
    }

    .damage-evidence-table td {
      width: 33.33%;
      padding: 8px;
      border: 1px solid #000;
      text-align: center;
      vertical-align: top;
    }

    .damage-evidence-img {
      max-width: 100%;
      max-height: 180px;
      width: auto;
      height: auto;
      display: block;
      margin: 0 auto 5px auto;
      border: 1px solid #ddd;
    }

    .damage-evidence-label {
      font-size: 8px;
      font-weight: bold;
      margin-bottom: 5px;
      color: #172e66;
    }

    .damage-evidence-description {
      font-size: 7px;
      margin-top: 5px;
      color: #333;
      text-align: center;
    }

    .schedule-info-container {
      display: table;
      width: 100%;
      margin-top: 10px;
      margin-bottom: 10px;
      border-collapse: collapse;
    }

    .schedule-left {
      display: table-cell;
      width: 50%;
      padding-right: 5px;
      vertical-align: top;
    }

    .schedule-right {
      display: table-cell;
      width: 50%;
      padding-left: 5px;
      vertical-align: top;
    }

    .schedule-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 8px;
    }

    .schedule-table th {
      background-color: #172e66;
      color: white;
      font-weight: bold;
      padding: 5px;
      text-align: center;
      border: 1px solid #000;
      font-size: 9px;
    }

    .schedule-table td {
      padding: 5px;
      border: 1px solid #000;
      text-align: center;
      vertical-align: middle;
    }

    .schedule-table .label-col {
      background-color: #f0f0f0;
      font-weight: bold;
      text-align: left;
      padding-left: 8px;
    }

    .appointment-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 8px;
    }

    .appointment-table th {
      background-color: #172e66;
      color: white;
      font-weight: bold;
      padding: 5px;
      text-align: center;
      border: 1px solid #000;
      font-size: 9px;
    }

    .appointment-table td {
      padding: 5px;
      border: 1px solid #000;
      text-align: left;
      vertical-align: middle;
    }

    .appointment-table .label-col {
      background-color: #f0f0f0;
      font-weight: bold;
      width: 35%;
    }

    .appointment-table .info-col {
      width: 45%;
    }

    .appointment-table .responsible-col {
      background-color: #e8e8e8;
      font-weight: bold;
      text-align: center;
      vertical-align: middle;
      width: 20%;
    }

    .client-vehicle-container {
      display: table;
      width: 100%;
      margin-top: 10px;
      margin-bottom: 10px;
      border-collapse: collapse;
    }

    .client-info-side {
      display: table-cell;
      width: 50%;
      padding-right: 5px;
      vertical-align: top;
    }

    .vehicle-info-side {
      display: table-cell;
      width: 50%;
      padding-left: 5px;
      vertical-align: top;
    }

    .info-detail-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 8px;
    }

    .info-detail-table td {
      padding: 5px;
      border: 1px solid #000;
      vertical-align: middle;
    }

    .info-detail-table .header-row {
      background-color: #172e66;
      color: white;
      font-weight: bold;
      text-align: left;
      padding: 5px 8px;
      font-size: 9px;
    }

    .info-detail-table .label-row {
      background-color: #f0f0f0;
      font-weight: bold;
      text-align: left;
      padding: 5px 8px;
    }

    .info-detail-table .value-row {
      background-color: white;
      text-align: left;
      padding: 5px 8px;
    }

    .info-detail-table .checkbox-option {
      display: inline-block;
      margin-right: 15px;
      vertical-align: middle;
      min-width: 140px;
    }

    .info-detail-table .mini-checkbox {
      display: inline-block;
      width: 12px;
      height: 12px;
      border: 1.5px solid #000;
      margin-left: 5px;
      vertical-align: middle;
      position: relative;
      top: 1px;
      float: right;
    }

    .info-detail-table .two-col-row td {
      width: 50%;
    }

    .info-detail-table .activities-content {
      min-height: 40px;
      line-height: 1.4;
    }
  </style>
</head>
<body>

<!-- Línea Roja Superior -->
<div class="red-line"></div>

<!-- Encabezado -->
<div class="header">
  <table>
    <tr>
      <!-- Columna Izquierda: Información de la Empresa -->
      <td class="header-left">
        <div class="company-info-container">
          <div class="company-logo">
            <img src="{{ getBase64Image('images/ap/ap.png') }}" alt="Logo AP">
          </div>
          <div class="company-text">
            <div class="company-name">
              AUTOMOTORES<br>
              PAKATNAMU SAC
              <span class="company-website">www.automotorespakatnamu.com</span>
            </div>
          </div>
        </div>
        <div class="company-addresses">
          <strong>CHICLAYO</strong>: CARRETERA PANAMERICANA NORTE N°1006<br>
          <strong>PIURA</strong>: AV. SANCHEZ CERRO MZA 248 LOTE 02 ZONA INDUSTRIAL<br>
          <strong>CAJAMARCA</strong>: MZA. B LOTE 19 OTR. EL BOSQUE III ETAPA (MAYOPATA FRENTE VIA EVITAMIENTO NORTE
          S/N)<br>
          <strong>JAEN</strong>: AV.PAKAMUROS N° 2485 (REF. CAMPO FERIAL - LINDEROS CARRETERA A SAN IGNACIO)
        </div>
      </td>

      <!-- Columna Derecha: Orden de Trabajo y Garantía -->
      <td class="header-right">
        <div class="work-order-title">ORDEN DE TRABAJO</div>
        <div class="logos-guarantee-container">
          <div class="logo-section">
            <img src="{{ getBase64Image('images/ap/derco-center.png') }}" alt="Derco Center">
          </div>
          <div class="logo-section">
            <img src="{{ getBase64Image('images/ap/logo-garantia-derco.jpg') }}" alt="Garantía Derco">
          </div>
          <div class="guarantee-check-box">
            <div class="guarantee-check-title">VEHÍCULO EN GARANTÍA</div>
            <div class="guarantee-check-options">
              <div class="guarantee-option">SI</div>
              <div class="guarantee-option">NO</div>
            </div>
          </div>
        </div>
      </td>
    </tr>
  </table>
</div>

<div class="schedule-info-container">
  <!-- Tabla Izquierda: Recepción y Entrega -->
  <div class="schedule-left">
    <table class="schedule-table">
      <thead>
      <tr>
        <th style="width: 40%;"></th>
        <th style="width: 30%;">FECHA</th>
        <th style="width: 30%;">HORA</th>
      </tr>
      </thead>
      <tbody>
      <tr>
        <td class="label-col">Recepción Programada</td>
        <td>15/03/2026</td>
        <td>09:00 AM</td>
      </tr>
      <tr>
        <td class="label-col">Recepción Real</td>
        <td>15/03/2026</td>
        <td>09:15 AM</td>
      </tr>
      <tr>
        <td class="label-col">Entrega Programada</td>
        <td>18/03/2026</td>
        <td>05:00 PM</td>
      </tr>
      <tr>
        <td class="label-col">Entrega Real</td>
        <td>18/03/2026</td>
        <td>04:45 PM</td>
      </tr>
      </tbody>
    </table>
  </div>

  <!-- Tabla Derecha: Citas y Repuestos -->
  <div class="schedule-right">
    <table class="appointment-table">
      <thead>
      <tr>
        <th colspan="3">CITAS Y REPUESTOS</th>
      </tr>
      </thead>
      <tbody>
      <tr>
        <td class="label-col">Confirmación de Cita</td>
        <td class="info-col">14/03/2026 - 03:30 PM</td>
        <td class="responsible-col" rowspan="3">
          <div style="writing-mode: vertical-rl; white-space: nowrap;">
            RESPONSABLE CITAS<br><br>
            María González Pérez
          </div>
        </td>
      </tr>
      <tr>
        <td class="label-col">Repuestos Pedido</td>
        <td class="info-col">10/03/2026 - 10:00 AM</td>
      </tr>
      <tr>
        <td class="label-col">Repuestos Llegada</td>
        <td class="info-col">14/03/2026 - 02:00 PM</td>
      </tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Sección: Información del Cliente y Vehículo -->
<div class="client-vehicle-container">
  <!-- Tabla Izquierda: Información del Cliente -->
  <div class="client-info-side">
    <table class="info-detail-table">
      <tr>
        <td class="header-row">INFORMACIÓN DE CLIENTE</td>
      </tr>
      <tr>
        <td class="label-row">Nombre del cliente</td>
      </tr>
      <tr>
        <td class="value-row">{{ $customer ? $customer->full_name : 'Juan Carlos Rodríguez Méndez' }}</td>
      </tr>
      <tr>
        <td class="label-row">Dirección (Domicilio / Trabajo):</td>
      </tr>
      <tr>
        <td
          class="value-row">{{ $customer ? $customer->direction : 'Av. Los Pinos 458, Urb. Santa Victoria, Chiclayo' }}</td>
      </tr>
      <tr>
        <td class="label-row">Correo electrónico</td>
      </tr>
      <tr>
        <td class="value-row">{{ $customer ? $customer->email : 'juan.rodriguez@email.com' }}</td>
      </tr>
      <tr>
        <td class="label-row">Teléfono de contacto</td>
      </tr>
      <tr>
        <td class="value-row">{{ $customer ? $customer->phone : '-' }}</td>
      </tr>
      <tr>
        <td class="label-row">
          Vehículo conducido por: (Dueño / Familiar / Otro)
        </td>
      </tr>
      <tr>
        <td class="value-row">Dueño</td>
      </tr>
      <tr>
        <td class="label-row">Cita hora y fecha</td>
      </tr>
      <tr>
        <td class="value-row">14/03/2026 - 03:30 PM</td>
      </tr>
    </table>

    <!-- Tabla: Solicitud de Cliente -->
    <table class="info-detail-table" style="margin-top: 10px;">
      <tr>
        <td class="header-row" colspan="2">SOLICITUD DE CLIENTE</td>
      </tr>
      <tr>
        <td class="value-row" style="width: 50%; border-right: 1px solid #fff; border-bottom: 1px solid #fff;">
          <span style="display: inline-block; width: 110px;">Cliente con cita</span><span class="mini-checkbox"
                                                                                          style="float: none; margin-left: 5px;"></span>
        </td>
        <td class="value-row" style="width: 50%; border-bottom: 1px solid #fff;">
          <span style="display: inline-block; width: 110px;">Cliente sin cita</span><span class="mini-checkbox"
                                                                                          style="float: none; margin-left: 5px;"></span>
        </td>
      </tr>
      <tr>
        <td class="value-row" style="border-right: 1px solid #fff; border-bottom: 1px solid #fff;">
          <span style="display: inline-block;">Mtto Preventivo ( ) km</span>
          <span style="display: inline-block; width: 30px; border-bottom: 1px solid #000; margin: 0 5px;"></span>
        </td>
        <td class="value-row" style="border-bottom: 1px solid #fff;">
          <span style="display: inline-block; width: 110px;">Mtto. correlativo</span><span class="mini-checkbox"
                                                                                           style="float: none; margin-left: 5px;"></span>
        </td>
      </tr>
      <tr>
        <td class="value-row" style="border-right: 1px solid #fff; border-bottom: 1px solid #fff;">
          <span style="display: inline-block; width: 110px;">Servicio interno</span><span class="mini-checkbox"
                                                                                          style="float: none; margin-left: 5px;"></span>
        </td>
        <td class="value-row" style="border-bottom: 1px solid #fff;">
          <span style="display: inline-block; width: 110px;">Garantía / Recall</span><span class="mini-checkbox"
                                                                                           style="float: none; margin-left: 5px;"></span>
        </td>
      </tr>
      <tr>
        <td class="value-row" style="border-right: 1px solid #fff;">
          <span style="display: inline-block; width: 110px;">Cliente espera</span><span class="mini-checkbox"
                                                                                        style="float: none; margin-left: 5px;"></span>
        </td>
        <td class="value-row">
          <span style="display: inline-block; width: 110px;">Reparación repetida</span><span class="mini-checkbox"
                                                                                             style="float: none; margin-left: 5px;"></span>
        </td>
      </tr>
    </table>
  </div>

  <!-- Tabla Derecha: Información del Vehículo -->
  <div class="vehicle-info-side">
    <table class="info-detail-table">
      <tr>
        <td colspan="2" class="header-row">INFORMACIÓN DE VEHÍCULO</td>
      </tr>
      <tr class="two-col-row">
        <td class="label-row">Placa: <span style="font-weight: normal;">{{ $vehicle->plate ?? 'ABC-123' }}</span></td>
        <td class="label-row">Marca: <span
            style="font-weight: normal;">{{ $vehicle->model->family->brand->name ?? 'TOYOTA' }}</span></td>
      </tr>
      <tr class="two-col-row">
        <td class="label-row">Modelo: <span
            style="font-weight: normal;">{{ $vehicle->model->family->description ?? 'HILUX' }}</span></td>
        <td class="label-row">Año de fabricación: <span
            style="font-weight: normal;">{{ $vehicle->year ?? '2024' }}</span>
        </td>
      </tr>
      <tr>
        <td colspan="2" class="label-row">VIN / N° Chasis: <span
            style="font-weight: normal;">{{ $vehicle->vin ?? 'MHFXXX7A9J1234567' }}</span></td>
      </tr>
      <tr class="two-col-row">
        <td class="label-row">Hora inicio trabajo: <span style="font-weight: normal;">09:15 AM</span></td>
        <td class="label-row">Hora fin trabajo: <span style="font-weight: normal;">04:30 PM</span></td>
      </tr>
      <tr>
        <td colspan="2" class="label-row">Técnico: <span
            style="font-weight: normal;">Carlos Alberto Sánchez Torres</span></td>
      </tr>
      <tr>
        <td colspan="2" class="header-row">RESULTADOS DE TRABAJO / OBSERVACIONES</td>
      </tr>
      <tr>
        <td colspan="2" class="value-row activities-content">
          Se realizó el mantenimiento preventivo de 10,000 km según especificaciones del fabricante. Se cambió aceite de
          motor, filtro de aceite, filtro de aire y se realizó inspección general de frenos y suspensión. Todo en
          perfecto estado.
        </td>
      </tr>
      <tr>
        <td colspan="2" class="label-row" style="font-size: 7px; padding: 3px 8px;">
          RECALL: SI<span class="mini-checkbox"
                          style="width: 10px; height: 10px; display: inline-block; margin-left: 3px; margin-right: 10px; vertical-align: middle; float: none;"></span>NO<span
            class="mini-checkbox"
            style="width: 10px; height: 10px; display: inline-block; margin-left: 3px; margin-right: 15px; vertical-align: middle; float: none;"></span>NOMBRE
          RECALL: {{ $typeRecall ?? 'N/A' }}
        </td>
      </tr>
      <tr>
        <td colspan="2" class="label-row">TIPO RECALL: <span
            style="font-weight: normal;">{{ $descriptionRecall ?? 'Sistema de Airbag' }}</span></td>
      </tr>
      <tr>
        <td colspan="2" class="header-row">ACTIVIDADES REALIZADAS</td>
      </tr>
      <tr>
        <td colspan="2" class="value-row activities-content">
          • Cambio de aceite de motor sintético 5W-30<br>
          • Reemplazo de filtro de aceite original<br>
          • Reemplazo de filtro de aire del motor<br>
          • Inspección y limpieza de frenos delanteros y traseros<br>
          • Revisión de niveles de líquidos (refrigerante, frenos, dirección)<br>
          • Inspección visual de suspensión y dirección<br>
          • Rotación de neumáticos y verificación de presión<br>
          • Escaneo computarizado del sistema electrónico
        </td>
      </tr>
    </table>
  </div>
</div>

<!-- Sección: Detalle de Trabajo, Requerimientos y Seguimiento -->
<table class="data-table">
  <tr>
    <td colspan="3" style="padding: 0;">
      <!-- Tabla interna de 3 columnas -->
      <table style="width: 100%; border-collapse: collapse;">
        <tr>
          <!-- Columna 1: Detalle de Trabajo -->
          <td style="width: 40%; vertical-align: top; padding: 8px; border-right: 0.5px solid #ccc;">
            <div
              style="background-color: #172e66; color: white; font-weight: bold; text-align: center; padding: 5px 8px; font-size: 9px; margin-bottom: 8px;">
              DETALLE DE TRABAJO
            </div>
            <div style="margin-bottom: 5px;">
              <span class="mini-checkbox"
                    style="display: inline-block; width: 10px; height: 10px; border: 1.5px solid #000; margin-right: 5px; vertical-align: middle;"></span>
              <span style="font-size: 8px;">Cambio de Aceite y Filtro</span>
              <span style="display: inline-block; margin-left: 10px;">
                <span class="mini-checkbox"
                      style="display: inline-block; width: 10px; height: 10px; border: 1.5px solid #000; margin-right: 5px; vertical-align: middle;"></span>
                <span style="font-size: 8px;">Alineamiento y balanceo</span>
              </span>
            </div>
            <div style="margin-bottom: 5px;">
              <span class="mini-checkbox"
                    style="display: inline-block; width: 10px; height: 10px; border: 1.5px solid #000; margin-right: 5px; vertical-align: middle;"></span>
              <span style="font-size: 8px;">Revisión de niveles y luces</span>
            </div>
            <div style="margin-bottom: 5px;">
              <span class="mini-checkbox"
                    style="display: inline-block; width: 10px; height: 10px; border: 1.5px solid #000; margin-right: 5px; vertical-align: middle;"></span>
              <span style="font-size: 8px;">Cambio de pastillas de freno y rectificación de disco</span>
            </div>
            <div style="margin-bottom: 5px;">
              <span class="mini-checkbox"
                    style="display: inline-block; width: 10px; height: 10px; border: 1.5px solid #000; margin-right: 5px; vertical-align: middle;"></span>
              <span style="font-size: 8px;">Engrase general</span>
              <span style="display: inline-block; margin-left: 10px;">
                <span class="mini-checkbox"
                      style="display: inline-block; width: 10px; height: 10px; border: 1.5px solid #000; margin-right: 5px; vertical-align: middle;"></span>
                <span style="font-size: 8px;">Otros: __________</span>
              </span>
            </div>
            <div style="margin-bottom: 5px;">
              <span class="mini-checkbox"
                    style="display: inline-block; width: 10px; height: 10px; border: 1.5px solid #000; margin-right: 5px; vertical-align: middle;"></span>
              <span style="font-size: 8px;">Rotación de llantas, revisión y limpieza de frenos</span>
            </div>
            <div style="margin-bottom: 5px;">
              <span class="mini-checkbox"
                    style="display: inline-block; width: 10px; height: 10px; border: 1.5px solid #000; margin-right: 5px; vertical-align: middle;"></span>
              <span style="font-size: 8px;">Inspección de filtro de aire, batería, neumáticos, suspensión y freno de mano</span>
            </div>
            <div>
              <span class="mini-checkbox"
                    style="display: inline-block; width: 10px; height: 10px; border: 1.5px solid #000; margin-right: 5px; vertical-align: middle;"></span>
              <span style="font-size: 8px;">Revisión de presión e inflado de llantas</span>
            </div>
          </td>

          <!-- Columna 2: Requerimientos de Cliente -->
          <td style="width: 30%; vertical-align: top; padding: 8px; border-right: 0.5px solid #ccc;">
            <div
              style="background-color: #172e66; color: white; font-weight: bold; text-align: center; padding: 5px 8px; font-size: 9px; margin-bottom: 8px;">
              REQUERIMIENTOS DE CLIENTE
            </div>
            <div style="font-size: 8px; line-height: 1.5; min-height: 120px;">
              <!-- Espacio para descripción de requerimientos del cliente -->
            </div>
          </td>

          <!-- Columna 3: Seguimiento Post Servicio -->
          <td style="width: 30%; vertical-align: top; padding: 8px;">
            <div
              style="background-color: #172e66; color: white; font-weight: bold; text-align: center; padding: 5px 8px; font-size: 9px; margin-bottom: 8px;">
              SEGUIMIENTO POST SERVICIO
            </div>
            <div style="font-size: 8px; margin-bottom: 15px;">
              <div style="margin-bottom: 10px;">
                <strong>Día:</strong> <span style="margin-left: 3px;">L</span> <span class="mini-checkbox"
                                                                                     style="display: inline-block; width: 10px; height: 10px; border: 1.5px solid #000; margin: 0 3px; vertical-align: middle;"></span>
                <span>M</span> <span class="mini-checkbox"
                                     style="display: inline-block; width: 10px; height: 10px; border: 1.5px solid #000; margin: 0 3px; vertical-align: middle;"></span>
                <span>M</span> <span class="mini-checkbox"
                                     style="display: inline-block; width: 10px; height: 10px; border: 1.5px solid #000; margin: 0 3px; vertical-align: middle;"></span>
                <span>J</span> <span class="mini-checkbox"
                                     style="display: inline-block; width: 10px; height: 10px; border: 1.5px solid #000; margin: 0 3px; vertical-align: middle;"></span>
                <span>V</span> <span class="mini-checkbox"
                                     style="display: inline-block; width: 10px; height: 10px; border: 1.5px solid #000; margin: 0 3px; vertical-align: middle;"></span>
                <span>S</span> <span class="mini-checkbox"
                                     style="display: inline-block; width: 10px; height: 10px; border: 1.5px solid #000; margin: 0 3px; vertical-align: middle;"></span>
                <span>D</span> <span class="mini-checkbox"
                                     style="display: inline-block; width: 10px; height: 10px; border: 1.5px solid #000; margin: 0 3px; vertical-align: middle;"></span>
              </div>
              <div>
                <strong>Hora:</strong>
                <span style="margin-left: 5px;">
                  De:
                  <span
                    style="display: inline-block; width: 18px; border-bottom: 1px solid #000; margin: 0 2px;"></span>
                  :
                  <span
                    style="display: inline-block; width: 18px; border-bottom: 1px solid #000; margin: 0 2px;"></span>
                </span>
                <span style="margin-left: 10px;">
                  A:
                  <span
                    style="display: inline-block; width: 18px; border-bottom: 1px solid #000; margin: 0 2px;"></span>
                  :
                  <span
                    style="display: inline-block; width: 18px; border-bottom: 1px solid #000; margin: 0 2px;"></span>
                </span>
              </div>
            </div>
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <!-- Pie de tabla -->
  <tr>
    <td colspan="3" style="padding: 0;">
      <table style="width: 100%; border-collapse: collapse;">
        <tr>
          <td
            style="padding: 8px; background-color: #f5f5f5; font-size: 9px; border-right: 1px solid #000000; width: 25%;">
            <strong>Reajuste hora de entrega:</strong> 14:30
          </td>
          <td
            style="padding: 8px; background-color: #f5f5f5; font-size: 9px; border-right: 1px solid #000000; width: 25%;">
            <strong>Precio estimado:</strong> S/ 450.00
          </td>
          <td
            style="padding: 8px; background-color: #f5f5f5; font-size: 9px; border-right: 1px solid #000000; width: 25%;">
            <strong>Precio estimado final:</strong> S/ 480.00
          </td>
          <td style="padding: 8px; background-color: #f5f5f5; font-size: 9px; width: 25%;">
            <strong>Asesor de servicio:</strong> Juan Pérez
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>


<!-- Sección: Items de Trabajo -->
<div class="section-title">ITEMS DE TRABAJO</div>
<table class="items-table">
  <thead>
  <tr>
    <th style="width: 10%;">N°</th>
    <th style="width: 20%;">Tipo</th>
    <th style="width: 70%;">Descripción</th>
  </tr>
  </thead>
  <tbody>
  @forelse($items as $index => $item)
    <tr>
      <td class="text-center">{{ $index + 1 }}</td>
      <td>{{ $item->typePlanning ? $item->typePlanning->description : 'N/A' }}</td>
      <td>{{ $item->description }}</td>
    </tr>
  @empty
    <tr>
      <td colspan="3" class="text-center">No hay items de trabajo registrados</td>
    </tr>
  @endforelse
  </tbody>
</table>

<!-- Sección: Inspección del Vehículo (Inventario + Estado) -->
<div class="section-title" style="page-break-before: avoid;">INSPECCIÓN DEL VEHÍCULO</div>
<table class="vehicle-inspection-table">
  <tr>
    <!-- Columna 1: Estado del Vehículo -->
    <td style="width: 33%; vertical-align: top; padding: 5px;">
      <div style="font-weight: bold; margin-bottom: 4px; font-size: 8px; text-align: center;">ESTADO DEL VEHÍCULO</div>
      <div class="vehicle-state-container" style="max-width: 100%; margin: 0;">
        <div class="vehicle-image-wrapper" style="height: 240px;">
          <img src="{{ getBase64Image('images/ap/body_car.png') }}" alt="Estado del Vehículo">
          @if($damages->count() > 0)
            @foreach($damages as $index => $damage)
              <div class="damage-marker" style="left: {{ $damage->x_coordinate }}%; top: {{ $damage->y_coordinate }}%;">
                {{ $index + 1 }}
              </div>
            @endforeach
          @endif
        </div>
        @if($damages->count() == 0)
          <div class="no-damages-text">
            ✓ SIN DAÑOS
          </div>
        @endif
      </div>
    </td>

    <!-- Columna 2: Lista de Inventario -->
    <td style="width: 33%; vertical-align: top; padding: 5px;">
      <div style="font-weight: bold; margin-bottom: 4px; font-size: 8px;">INVENTARIO</div>
      <div style="font-size: 7.5px;">
        <table style="width: 100%; border-collapse: collapse;">
          @php
            $inventoryItems = [];
            foreach($inventoryChecks as $key => $label) {
              $inventoryItems[] = [
                'key' => $key,
                'label' => $label,
                'checked' => $inspection->{$key}
              ];
            }
            $halfCount = ceil(count($inventoryItems) / 2);
            $leftColumn = array_slice($inventoryItems, 0, $halfCount);
            $rightColumn = array_slice($inventoryItems, $halfCount);
            $maxRows = max(count($leftColumn), count($rightColumn));
          @endphp
          @for($i = 0; $i < $maxRows; $i++)
            <tr>
              <td style="border: none; padding: 1px 2px; width: 50%;">
                @if(isset($leftColumn[$i]))
                  <div class="inventory-item">
                    <span class="checkbox {{ $leftColumn[$i]['checked'] ? 'checked' : '' }}"></span>
                    {{ $leftColumn[$i]['label'] }}
                  </div>
                @endif
              </td>
              <td style="border: none; padding: 1px 2px; width: 50%;">
                @if(isset($rightColumn[$i]))
                  <div class="inventory-item">
                    <span class="checkbox {{ $rightColumn[$i]['checked'] ? 'checked' : '' }}"></span>
                    {{ $rightColumn[$i]['label'] }}
                  </div>
                @endif
              </td>
            </tr>
          @endfor
        </table>

        <!-- Campos adicionales: Aceite y Combustible -->
        <div class="inventory-extra-fields">
          <div class="inventory-extra-item">
            <strong>Nivel de Aceite:</strong>
            <span>{{ $inspection->oil_level ?? 'N/A' }}</span>
          </div>
          <div class="inventory-extra-item">
            <strong>Nivel de Combustible:</strong>
            <span>{{ $inspection->fuel_level ?? 'N/A' }}</span>
          </div>
        </div>
      </div>
    </td>

    <!-- Columna 3: 4 Cuadros -->
    <td style="width: 34%; vertical-align: top; padding: 5px;">
      <!-- Cuadro 1: Explicación de Resultados -->
      <table class="inventory-box" style="width: 100%; border-collapse: collapse; margin-bottom: 3px;">
        <tr>
          <td class="inventory-box-title">EXPLICACIÓN DE RESULTADOS</td>
        </tr>
        <tr>
          <td class="inventory-box-content">
            <div class="inventory-box-item">
              <span class="mini-checkbox checked"></span>
              <span>Explicación de trabajos realizados</span>
            </div>
            <div class="inventory-box-item">
              <span class="mini-checkbox checked"></span>
              <span>Explicación de precios</span>
            </div>
            <div class="inventory-box-item">
              <span class="mini-checkbox checked"></span>
              <span>Confirmación de realización de trabajos adicionales</span>
            </div>
            <div class="inventory-box-item">
              <span class="mini-checkbox checked"></span>
              <span>Aclaración de inquietudes del cliente</span>
            </div>
          </td>
        </tr>
      </table>

      <!-- Cuadro 2: Entrega del Vehículo -->
      <table class="inventory-box" style="width: 100%; border-collapse: collapse; margin-bottom: 3px;">
        <tr>
          <td class="inventory-box-title">ENTREGA DEL VEHÍCULO</td>
        </tr>
        <tr>
          <td class="inventory-box-content">
            <div class="inventory-box-item">
              <span class="mini-checkbox checked"></span>
              <span>Limpieza exterior</span>
            </div>
            <div class="inventory-box-item">
              <span class="mini-checkbox checked"></span>
              <span>Limpieza interior</span>
            </div>
            <div class="inventory-box-item">
              <span class="mini-checkbox checked"></span>
              <span>Se queda con repuestos</span>
            </div>
            <div class="inventory-box-item">
              <span class="mini-checkbox checked"></span>
              <span>Objetos de valor</span>
            </div>
          </td>
        </tr>
      </table>

      <!-- Cuadro 3: Items de Cortesía -->
      <table class="inventory-box" style="width: 100%; border-collapse: collapse; margin-bottom: 3px;">
        <tr>
          <td class="inventory-box-title">ITEMS DE CORTESÍA</td>
        </tr>
        <tr>
          <td class="inventory-box-content">
            <div class="inventory-box-item">
              <span class="mini-checkbox checked"></span>
              <span>Cobertor de asiento</span>
            </div>
            <div class="inventory-box-item">
              <span class="mini-checkbox checked"></span>
              <span>Piso de papel</span>
            </div>
          </td>
        </tr>
      </table>

      <!-- Cuadro 4: Odómetro de Ingreso -->
      <table class="inventory-box" style="width: 100%; border-collapse: collapse; margin-bottom: 3px;">
        <tr>
          <td class="inventory-box-title">ODÓMETRO DE INGRESO</td>
        </tr>
        <tr>
          <td class="inventory-box-content">
            <div class="odometer-value">
              {{ $inspection->mileage ?? 'N/A' }} KM
            </div>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>

@if($damages->count() > 0)
  <div class="damages-list">
    <strong>Detalle de Daños:</strong>
    <table>
      <thead>
      <tr>
        <th style="width: 10%;">N°</th>
        <th style="width: 20%;">Tipo</th>
        <th style="width: 70%;">Descripción</th>
      </tr>
      </thead>
      <tbody>
      @foreach($damages as $index => $damage)
        <tr>
          <td class="text-center">{{ $index + 1 }}</td>
          <td>{{ $damage->damage_type }}</td>
          <td>{{ $damage->description }}</td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
@endif

@if($inspection->general_observations)
  <div style="margin-top: 10px; font-size: 8px;">
    <strong>Observaciones Generales:</strong><br>
    {{ $inspection->general_observations }}
  </div>
@endif

@if($damages->count() > 0)
  @php
    $damagesWithPhotos = $damages->filter(function($damage) {
      return !empty($damage->photo_url);
    });
  @endphp

  @if($damagesWithPhotos->count() > 0)
    <!-- Sección: Evidencias de Daños -->
    <div class="section-title">EVIDENCIAS DE DAÑOS</div>
    <table class="damage-evidence-table">
      @foreach($damagesWithPhotos->chunk(3) as $damageRow)
        <tr>
          @foreach($damageRow as $index => $damage)
            <td>
              <div class="damage-evidence-label">
                DAÑO N° {{ $damages->search($damage) + 1 }} - {{ $damage->damage_type }}
              </div>
              @if(isset($damage->photo_base64) && $damage->photo_base64)
                <img src="{{ $damage->photo_base64 }}" alt="Evidencia Daño" class="damage-evidence-img">
              @endif
              @if($damage->description)
                <div class="damage-evidence-description">
                  {{ $damage->description }}
                </div>
              @endif
            </td>
          @endforeach
          @if($damageRow->count() < 3)
            @for($i = 0; $i < (3 - $damageRow->count()); $i++)
              <td></td>
            @endfor
          @endif
        </tr>
      @endforeach
    </table>
  @endif
@endif

<!-- Sección: Información Importante -->
<div class="section-title">INFORMACIÓN IMPORTANTE</div>
<div class="important-section">
  <div class="important-title">ESTIMADO CLIENTE:</div>
  <div style="font-size: 8px; line-height: 1.4;">
    <ol style="margin-left: 15px;">
      <li>SÍRVASE CONSERVAR ESTE COMPROBANTE Y PRESENTARLO AL RETIRAR SU VEHÍCULO, EL CUAL SOLO SERÁ ENTREGADO A LA
        PRESENTACIÓN DE ESTE DOCUMENTO.
      </li>
      <li>PERMANENCIA DEL VEHÍCULO: CUANDO EL CLIENTE NO CUMPLA LA OBLIGACIÓN ASUMIDA POR LOS TRABAJOS REALIZADOS, EL
        VEHÍCULO PERMANECERÁ EN EL CENTRO DE SERVICIO HASTA QUE REALICE EL PAGO CORRESPONDIENTE.
      </li>
      <li>CRÉDITO: EN CASO LA FACTURACIÓN SEA A CRÉDITO, SE DEBERÁ PRESENTAR LA DOCUMENTACIÓN SOLICITADA POR EL
        DEPARTAMENTO DE CRÉDITOS Y COBRANZAS PARA PODER RETIRAR LA UNIDAD.
      </li>
      <li>PAGOS: TODA REPARACIÓN SE CANCELARÁ EN CAJA ANTES DE LA ENTREGA DEL VEHÍCULO. EN EL CASO DE NO RETIRARLO EN
        LOS DOS (2) DÍAS ÚTILES SIGUIENTES, CONTADOS A PARTIR DE LA FECHA DE HABER RECIBIDO EL AVISO DE RECOJO O DEL
        PRESUPUESTO SIN AUTORIZACIÓN DEL TRABAJO, SE COBRARÁ (S/. 15.00) NUEVOS SOLES DIARIOS POR CONCEPTO DE GUARDERÍA.
      </li>
      <li>SEGURIDAD: TODA UNIDAD QUE SE ENCUENTRE EN EL CENTRO DE SERVICIO ESTÁ ASEGURADA ANTE CUALQUIER INCIDENCIA QUE
        PUEDA OCURRIR DENTRO DE LAS INSTALACIONES DE LA EMPRESA O FUERA DE ELLA DURANTE LA PRUEBA EN RUTA.
      </li>
      <li>EN CASO EL VEHÍCULO CUENTE CON LÁMINAS POLARIZADAS, NO SE PODRÁ REALIZAR LA PRUEBA EN RUTA, SALVO QUE LA
        PERSONA AUTORIZADA EN EL PERMISO ESTÉ PRESENTE DENTRO DE LA PRUEBA. SE LE INFORMA QUE, A LA FIRMA DEL
        INVENTARIO, USTED AUTORIZA LA PRUEBA DE MANEJO DEL VEHÍCULO SI FUERA NECESARIO, COMO PARTE DEL CONTROL DE
        CALIDAD DEL SERVICIO.
      </li>
    </ol>
  </div>
</div>

<!-- Sección: Firmas -->
<div class="signatures">
  <table>
    <tr>
      <td>
        @if($advisorSignature)
          <img src="{{ $advisorSignature }}" alt="Firma Asesor" class="signature-img">
        @endif
        <div class="signature-box">
          FIRMA DEL ASESOR<br>
          {{ $advisor ? $advisor->nombre_completo : 'N/A' }}
        </div>
      </td>
      <td>
        @if($customerSignature)
          <img src="{{ $customerSignature }}" alt="Firma Cliente" class="signature-img">
        @endif
        <div class="signature-box">
          FIRMA DEL CLIENTE<br>
          {{ $customer ? $customer->full_name : 'N/A' }}
        </div>
      </td>
    </tr>
  </table>
</div>

</body>
</html>
