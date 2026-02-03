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
      margin-top: 0px;
      padding-top: 5px;
      font-size: 9px;
      font-weight: bold;
    }

    .signature-img {
      max-width: 200px;
      max-height: 80px;
      margin-bottom: 0px;
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

    .guarantee-recall-container {
      display: table;
      width: 100%;
      margin-bottom: 5px;
    }

    .recall-box {
      display: table-cell;
      width: 50%;
      padding-right: 5px;
      vertical-align: top;
    }

    .guarantee-box {
      display: table-cell;
      width: 50%;
      padding-left: 5px;
      vertical-align: top;
    }

    .recall-info-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 8px;
      margin-top: 5px;
    }

    .recall-info-table th {
      background-color: #172e66;
      color: white;
      font-weight: bold;
      padding: 5px;
      text-align: left;
      border: 1px solid #000;
      font-size: 9px;
    }

    .recall-info-table td {
      padding: 5px;
      border: 1px solid #000;
      vertical-align: top;
    }

    .recall-info-table .label-cell {
      font-weight: bold;
      width: 30%;
      background-color: #f0f0f0;
    }

    .status-box {
      border: 2px solid #172e66;
      padding: 8px;
      background-color: #f9f9f9;
    }

    .status-box-title {
      font-weight: bold;
      font-size: 9px;
      text-align: center;
      margin-bottom: 5px;
      color: #172e66;
    }

    .status-options {
      display: table;
      width: 100%;
      border-collapse: collapse;
    }

    .status-option {
      display: table-cell;
      width: 50%;
      text-align: center;
      padding: 5px;
      border: 1.5px solid #000;
      font-size: 9px;
      font-weight: bold;
    }

    .status-option.checked {
      background-color: #d0d0d0;
    }

    .status-option.checked::after {
      content: " X";
      color: #000;
      font-size: 11px;
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

<!-- Cuadros de Estado: Recall y Garantía -->
<div class="guarantee-recall-container">
  <!-- Lado Izquierdo: Estado de Recall -->
  <div class="recall-box">
    <div class="status-box">
      <div class="status-box-title">VEHÍCULO EN RECALL</div>
      <div class="status-options">
        <div class="status-option {{ $isRecall ? 'checked' : '' }}">SI</div>
        <div class="status-option {{ !$isRecall ? 'checked' : '' }}">NO</div>
      </div>
    </div>
  </div>

  <!-- Lado Derecho: Estado de Garantía -->
  <div class="guarantee-box">
    <div class="status-box">
      <div class="status-box-title">VEHÍCULO EN GARANTÍA</div>
      <div class="status-options">
        <div class="status-option {{ $isGuarantee ? 'checked' : '' }}">SI</div>
        <div class="status-option {{ !$isGuarantee ? 'checked' : '' }}">NO</div>
      </div>
    </div>
  </div>
</div>

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
    <td>{{ $sede ? $sede->abreviatura : 'N/A' }}</td>
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
  @if($isRecall && ($typeRecall || $descriptionRecall))
    <tr>
      <td class="label-cell">Tipo de Recall:</td>
      <td colspan="3">{{ $typeRecall ?: 'N/A' }}</td>
    </tr>
    <tr>
      <td class="label-cell">Descripción de Recall:</td>
      <td colspan="3">{{ $descriptionRecall ?: 'N/A' }}</td>
    </tr>
  @endif
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
</body>
</html>
