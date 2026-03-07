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

    table.data-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 10px;
      font-size: 8px;
    }

    table.data-table td {
      padding: 4px;
      border: 1px solid #000;
      vertical-align: top;
    }

    .label-cell {
      font-weight: bold;
      width: 20%;
      background-color: #f0f0f0;
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

    .vehicle-inspection-container {
      width: 100%;
      margin-bottom: 10px;
      display: table;
    }

    .inventory-column {
      display: table-cell;
      width: 60%;
      vertical-align: top;
      padding-right: 10px;
    }

    .vehicle-image-column {
      display: table-cell;
      width: 40%;
      vertical-align: top;
      padding-left: 10px;
    }

    .inventory-list {
      border: 1px solid #000;
      padding: 8px;
      font-size: 8px;
      min-height: 270px;
    }

    .inventory-list table {
      width: 100%;
      border-collapse: collapse;
    }

    .inventory-list td {
      width: 50%;
      padding: 2px;
      border: none;
      vertical-align: top;
    }

    .inventory-item {
      margin-bottom: 2px;
      line-height: 1.3;
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
      border: 1px solid #000;
      padding: 10px;
      background-color: #f9f9f9;
      page-break-inside: avoid;
    }

    .vehicle-image-wrapper {
      position: relative;
      width: 100%;
      height: 270px;
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
      font-size: 10px;
      padding: 15px;
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
        <td colspan="2" class="label-row" style="white-space: nowrap; font-size: 7px; padding: 3px 8px;">
          RECALL: <span class="checkbox-option" style="min-width: 50px; margin-right: 8px;">SI<span
              class="mini-checkbox" style="width: 10px; height: 10px;"></span></span> <span
            class="checkbox-option" style="min-width: 50px; margin-right: 8px;">NO<span class="mini-checkbox"
                                                                                        style="width: 10px; height: 10px;"></span></span>
          NOMBRE RECALL: <span
            style="font-weight: normal;">{{ $typeRecall ?? 'N/A' }}</span>
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


<!-- Sección: Información del Vehículo -->
<div class="section-title">INFORMACIÓN DEL VEHÍCULO</div>
<table class="data-table">
  <tr>
    <td class="label-cell">Marca:</td>
    <td>{{ $vehicle->model->family->brand->name ?? 'N/A' }}</td>
    <td class="label-cell">Modelo:</td>
    <td>{{ $vehicle->model->family->description ?? 'N/A' }} {{ $vehicle->model->version ?? '' }}</td>
  </tr>
  <tr>
    <td class="label-cell">Placa:</td>
    <td>{{ $vehicle->plate ?? 'N/A' }}</td>
    <td class="label-cell">Color:</td>
    <td>{{ $vehicle->color->description ?? 'N/A' }}</td>
  </tr>
  <tr>
    <td class="label-cell">VIN:</td>
    <td>{{ $vehicle->vin ?? 'N/A' }}</td>
    <td class="label-cell">N° Motor:</td>
    <td>{{ $vehicle->engine_number ?? 'N/A' }}</td>
  </tr>
  <tr>
    <td class="label-cell">Año:</td>
    <td>{{ $vehicle->year ?? 'N/A' }}</td>
    <td class="label-cell">Km:</td>
    <td>{{ $inspection->mileage ?? 'N/A' }}</td>
  </tr>
  <tr>
    <td class="label-cell">Nivel Combustible:</td>
    <td>{{ $inspection->fuel_level ?? 'N/A' }}</td>
    <td class="label-cell">Nivel de Aceite:</td>
    <td>{{ $inspection->oil_level ?? 'N/A' }}</td>
  </tr>
</table>

<!-- Sección: Datos del Cliente -->
<div class="section-title">DATOS DEL CLIENTE</div>
<table class="data-table">
  <tr>
    <td class="label-cell">Cliente:</td>
    <td colspan="3">{{ $customer ? $customer->full_name : 'N/A' }}</td>
  </tr>
  <tr>
    <td class="label-cell">DNI/RUC:</td>
    <td>{{ $customer ? $customer->num_doc : 'N/A' }}</td>
    <td class="label-cell">Teléfono:</td>
    <td>{{ $customer ? $customer->phone : 'N/A' }}</td>
  </tr>
  <tr>
    <td class="label-cell">Dirección:</td>
    <td colspan="3">{{ $customer ? $customer->direction : 'N/A' }}</td>
  </tr>
  <tr>
    <td class="label-cell">E-mail:</td>
    <td>{{ $customer ? $customer->email : 'N/A' }}</td>
    <td class="label-cell">Celular:</td>
    <td>{{ $customer ? $customer->phone : 'N/A' }}</td>
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
<div class="section-title">INSPECCIÓN DEL VEHÍCULO</div>
<div class="vehicle-inspection-container">
  <!-- Columna Izquierda: Inventario -->
  <div class="inventory-column">
    <div style="font-weight: bold; margin-bottom: 5px; font-size: 9px;">INVENTARIO:</div>
    <div class="inventory-list">
      <table>
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
            <td>
              @if(isset($leftColumn[$i]))
                <div class="inventory-item">
                  <span class="checkbox {{ $leftColumn[$i]['checked'] ? 'checked' : '' }}"></span>
                  {{ $leftColumn[$i]['label'] }}
                </div>
              @endif
            </td>
            <td>
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
    </div>
  </div>

  <!-- Columna Derecha: Estado del Vehículo -->
  <div class="vehicle-image-column">
    <div style="font-weight: bold; margin-bottom: 5px; font-size: 9px; text-align: center;">ESTADO DEL VEHÍCULO:</div>
    <div class="vehicle-state-container">
      <div class="vehicle-image-wrapper">
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
          ✓ VEHÍCULO SIN DAÑOS REPORTADOS
        </div>
      @endif
    </div>
  </div>
</div>

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
