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

    .header {
      margin-bottom: 15px;
    }

    .header table {
      width: 100%;
      border: none;
    }

    .header td {
      border: none;
      vertical-align: middle;
    }

    .logo {
      text-align: center;
    }

    .logo img {
      max-width: 80px;
      height: auto;
    }

    .center-title {
      text-align: center;
      font-size: 14px;
      font-weight: bold;
      padding: 5px;
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
      width: 50%;
      vertical-align: top;
      padding-right: 10px;
    }

    .vehicle-image-column {
      display: table-cell;
      width: 50%;
      vertical-align: top;
      padding-left: 10px;
    }

    .inventory-list {
      border: 1px solid #000;
      padding: 8px;
      font-size: 8px;
    }

    .inventory-item {
      margin-bottom: 4px;
      line-height: 1.3;
    }

    .checkbox {
      display: inline-block;
      width: 10px;
      height: 10px;
      border: 1px solid #000;
      margin-right: 5px;
      text-align: center;
      line-height: 10px;
      font-size: 8px;
      vertical-align: middle;
    }

    .checkbox.checked::before {
      content: "✓";
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
      margin-top: 60px;
      padding-top: 5px;
      font-size: 9px;
      font-weight: bold;
    }

    .signature-img {
      max-width: 150px;
      max-height: 50px;
      margin-bottom: 5px;
    }

    .text-center {
      text-align: center;
    }

    .text-right {
      text-align: right;
    }
  </style>
</head>
<body>

<!-- Encabezado -->
<div class="header">
  <table>
    <tr>
      <td class="logo" style="width: 25%;">
        <img src="{{ getBase64Image('images/ap/derco.jpg') }}" alt="Derco Logo">
      </td>
      <td class="center-title" style="width: 50%;">
        ORDEN DE RECEPCIÓN<br>
        AUTOMOTORES PAKATNAMU S.A.C.
      </td>
      <td class="logo" style="width: 25%;">
        <img src="{{ getBase64Image('images/ap/logo-ap.png') }}" alt="Automotores Logo">
      </td>
    </tr>
  </table>
</div>

<!-- Sección: Información de la Orden de Trabajo -->
<div class="section-title">INFORMACIÓN DE LA ORDEN DE TRABAJO</div>
<table class="data-table">
  <tr>
    <td class="label-cell">Número OT:</td>
    <td>{{ $workOrder->correlative }}</td>
    <td class="label-cell">Fecha OT:</td>
    <td>{{ $workOrder->opening_date ? $workOrder->opening_date->format('d/m/Y') : 'N/A' }}</td>
  </tr>
  <tr>
    <td class="label-cell">Tipo OT:</td>
    <td>{{ $status ? $status->description : 'N/A' }}</td>
    <td class="label-cell">Sucursal:</td>
    <td>{{ $sede ? $sede->description : 'N/A' }}</td>
  </tr>
  <tr>
    <td class="label-cell">Asesor:</td>
    <td>{{ $advisor ? $advisor->nombre_completo : 'N/A' }}</td>
    <td class="label-cell">Telf. Asesor:</td>
    <td>{{ $advisorPhone }}</td>
  </tr>
  <tr>
    <td class="label-cell">Fecha de Recepción:</td>
    <td>{{ $inspection->inspection_date ? $inspection->inspection_date->format('d/m/Y') : 'N/A' }}</td>
    <td class="label-cell">Hora de Recepción:</td>
    <td>{{ $inspection->inspection_date ? $inspection->inspection_date->format('H:i') : 'N/A' }}</td>
  </tr>
  <tr>
    <td class="label-cell">Fecha de Entrega Estimada:</td>
    <td>{{ $workOrder->estimated_delivery_date ? $workOrder->estimated_delivery_date->format('d/m/Y') : 'N/A' }}</td>
    <td class="label-cell">Hora de Compromiso:</td>
    <td>{{ $workOrder->estimated_delivery_date ? $workOrder->estimated_delivery_date->format('H:i') : 'N/A' }}</td>
  </tr>
  <tr>
    <td class="label-cell">Número de Cita:</td>
    <td colspan="3">{{ $appointmentNumber }}</td>
  </tr>
</table>

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
    <td colspan="3">{{ $customer ? $customer->business_name : 'N/A' }}</td>
  </tr>
  <tr>
    <td class="label-cell">DNI/RUC:</td>
    <td>{{ $customer ? $customer->document_number : 'N/A' }}</td>
    <td class="label-cell">Teléfono:</td>
    <td>{{ $customer ? $customer->phone : 'N/A' }}</td>
  </tr>
  <tr>
    <td class="label-cell">Dirección:</td>
    <td colspan="3">{{ $customer ? $customer->address : 'N/A' }}</td>
  </tr>
  <tr>
    <td class="label-cell">E-mail:</td>
    <td>{{ $customer ? $customer->email : 'N/A' }}</td>
    <td class="label-cell">Celular:</td>
    <td>{{ $customer ? $customer->cellphone : 'N/A' }}</td>
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
      @foreach($inventoryChecks as $key => $label)
        <div class="inventory-item">
          <span class="checkbox {{ $inspection->{$key} ? 'checked' : '' }}"></span>
          {{ $label }}
        </div>
      @endforeach
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

<!-- Sección: Información Importante -->
<div class="section-title">INFORMACIÓN IMPORTANTE</div>
<div class="important-section">
  <div class="important-title">CONSIDERACIONES IMPORTANTES:</div>
  <div style="font-size: 8px; line-height: 1.4;">
    <ol style="margin-left: 15px;">
      <li>EL CLIENTE DEBE VERIFICAR QUE TODOS LOS ITEMS DE TRABAJO ESTÉN CORRECTAMENTE REGISTRADOS.</li>
      <li>LA EMPRESA NO SE HACE RESPONSABLE POR OBJETOS DE VALOR DEJADOS EN EL VEHÍCULO.</li>
      <li>EL HORARIO DE ATENCIÓN ES DE LUNES A VIERNES DE 8:00 AM A 6:00 PM Y SÁBADOS DE 8:00 AM A 1:00 PM.</li>
      <li>EL CLIENTE DEBE RECOGER EL VEHÍCULO EN LA FECHA Y HORA PROGRAMADA.</li>
      <li>CUALQUIER TRABAJO ADICIONAL DEBE SER AUTORIZADO POR EL CLIENTE.</li>
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
          {{ $customer ? $customer->business_name : 'N/A' }}
        </div>
      </td>
    </tr>
  </table>
</div>

</body>
</html>
