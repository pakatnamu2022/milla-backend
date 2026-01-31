<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Acta de Asignación de Equipos</title>
  <style>
    @page { margin: 15mm; }
    body { font-family: Arial, sans-serif; font-size: 11px; color: #333; }
    .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #00227d; padding-bottom: 10px; }
    .header h1 { margin: 0; font-size: 18px; color: #00227d; }
    .header p { margin: 4px 0 0; font-size: 10px; color: #666; }
    .section { margin-bottom: 15px; }
    .section-title { font-size: 12px; font-weight: bold; color: #00227d; border-bottom: 1px solid #ccc; padding-bottom: 4px; margin-bottom: 8px; }
    .info-table { width: 100%; margin-bottom: 10px; }
    .info-table td { padding: 3px 8px; vertical-align: top; }
    .info-table .label { font-weight: bold; width: 140px; color: #555; }
    table.items { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
    table.items th { background-color: #f1f5f9; color: #64748B; padding: 6px 8px; text-align: left; font-size: 10px; border-bottom: 1px solid #cbd5e1; }
    table.items td { padding: 5px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10px; }
    table.items tr:nth-child(even) { background-color: #f8fafc; }
    .observaciones { background-color: #f8f9fa; padding: 10px; border-left: 3px solid #00227d; margin-bottom: 20px; }
    .firmas { margin-top: 60px; width: 100%; }
    .firmas td { width: 50%; text-align: center; vertical-align: bottom; padding-top: 40px; }
    .firma-linea { border-top: 1px solid #333; display: inline-block; width: 200px; margin-bottom: 4px; }
    .firma-nombre { font-weight: bold; font-size: 11px; }
    .firma-cargo { font-size: 9px; color: #666; }
  </style>
</head>
<body>
  <div class="header">
    <h1>ACTA DE ASIGNACIÓN DE EQUIPOS</h1>
    <p>Documento generado el {{ now()->format('d/m/Y H:i:s') }}</p>
  </div>

  <div class="section">
    <div class="section-title">Datos del Colaborador</div>
    <table class="info-table">
      <tr>
        <td class="label">Nombre:</td>
        <td>{{ $assignment->worker?->nombre_completo ?? '-' }}</td>
      </tr>
      <tr>
        <td class="label">Cargo:</td>
        <td>{{ $assignment->worker?->position?->nombre ?? '-' }}</td>
      </tr>
      <tr>
        <td class="label">Área:</td>
        <td>{{ $assignment->worker?->area?->nombre ?? '-' }}</td>
      </tr>
      <tr>
        <td class="label">Fecha de Asignación:</td>
        <td>{{ $assignment->fecha }}</td>
      </tr>
    </table>
  </div>

  <div class="section">
    <div class="section-title">Equipos Asignados</div>
    <table class="items">
      <thead>
        <tr>
          <th>#</th>
          <th>Equipo</th>
          <th>Tipo</th>
          <th>Marca / Modelo</th>
          <th>Serie</th>
          <th>Observación</th>
        </tr>
      </thead>
      <tbody>
        @foreach($assignment->items as $index => $item)
          <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $item->equipment?->equipo ?? '-' }}</td>
            <td>{{ $item->equipment?->equipmentType?->nombre ?? '-' }}</td>
            <td>{{ $item->equipment?->marca_modelo ?? '-' }}</td>
            <td>{{ $item->equipment?->serie ?? '-' }}</td>
            <td>{{ $item->observacion ?? '-' }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  @if($assignment->observacion)
    <div class="section">
      <div class="section-title">Observaciones</div>
      <div class="observaciones">{{ $assignment->observacion }}</div>
    </div>
  @endif

  <table class="firmas">
    <tr>
      <td>
        <div class="firma-linea"></div><br>
        <div class="firma-nombre">{{ $assignment->worker?->nombre_completo ?? '' }}</div>
        <div class="firma-cargo">Colaborador</div>
      </td>
      <td>
        <div class="firma-linea"></div><br>
        <div class="firma-nombre">Responsable TICs</div>
        <div class="firma-cargo">Área de Tecnología</div>
      </td>
    </tr>
  </table>
</body>
</html>
