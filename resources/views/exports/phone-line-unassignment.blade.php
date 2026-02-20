<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Acta de Desasignación de Línea Telefónica</title>
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
    .info-table .label { font-weight: bold; width: 160px; color: #555; }
    .observaciones { background-color: #f8f9fa; padding: 10px; border-left: 3px solid #00227d; margin-bottom: 20px; }
    .firmas { margin-top: 60px; width: 100%; }
    .firmas td { width: 50%; text-align: center; vertical-align: bottom; padding-top: 40px; }
    .firma-linea { border-top: 1px solid #333; display: inline-block; width: 200px; margin-bottom: 4px; }
    .firma-nombre { font-weight: bold; font-size: 11px; }
    .firma-cargo { font-size: 9px; color: #666; }
    .declaracion { font-size: 10px; line-height: 1.6; text-align: justify; background-color: #f8f9fa; border: 1px solid #dee2e6; border-left: 4px solid #00227d; padding: 12px 14px; margin-bottom: 10px; color: #333; }
  </style>
</head>
<body>
  <div class="header">
    <h1>ACTA DE DESASIGNACIÓN DE LÍNEA TELEFÓNICA</h1>
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
        <td>{{ $assignment->assigned_at ? \Carbon\Carbon::parse($assignment->assigned_at)->format('d/m/Y') : '-' }}</td>
      </tr>
      <tr>
        <td class="label">Fecha de Desasignación:</td>
        <td>{{ $assignment->unassigned_at ? \Carbon\Carbon::parse($assignment->unassigned_at)->format('d/m/Y') : now()->format('d/m/Y') }}</td>
      </tr>
    </table>
  </div>

  <div class="section">
    <div class="section-title">Datos de la Línea Telefónica</div>
    <table class="info-table">
      <tr>
        <td class="label">Número de Línea:</td>
        <td>{{ $assignment->phoneLine?->line_number ?? '-' }}</td>
      </tr>
      <tr>
        <td class="label">Cuenta:</td>
        <td>{{ $assignment->phoneLine?->telephoneAccount?->account_number ?? '-' }}</td>
      </tr>
      <tr>
        <td class="label">Operador:</td>
        <td>{{ $assignment->phoneLine?->telephoneAccount?->operator ?? '-' }}</td>
      </tr>
      <tr>
        <td class="label">Plan:</td>
        <td>{{ $assignment->phoneLine?->telephonePlan?->name ?? '-' }}</td>
      </tr>
    </table>
  </div>

  <div class="section">
    <div class="section-title">Declaración de Desasignación</div>
    <div class="declaracion">
      Certifico que la línea telefónica corporativa detallada en el presente documento ha sido desasignada a mi persona en la fecha indicada. Me comprometo a cesar inmediatamente cualquier uso de dicha línea a partir de esta fecha y a no retener ningún dispositivo o SIM asociado a la misma. Declaro haber entregado al área de TICs todos los elementos vinculados a esta línea en las condiciones acordadas, quedando exento de toda responsabilidad sobre el uso o costos generados por la línea referida a partir de la fecha de desasignación registrada en este documento. En caso de detectarse consumos posteriores atribuibles a mi persona, o de no haberse realizado la devolución íntegra de los dispositivos asociados, reconozco mi responsabilidad y autorizo se descuente el valor correspondiente de cualquier liquidación, prestaciones sociales, bonificaciones y demás haberes que me correspondan.
    </div>
  </div>

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
