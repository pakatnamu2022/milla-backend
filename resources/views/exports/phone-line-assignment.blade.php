<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Acta de Asignación de Línea Telefónica</title>
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
    <h1>ACTA DE ASIGNACIÓN DE LÍNEA TELEFÓNICA</h1>
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
        <td>{{ $assignment->assigned_at ? \Carbon\Carbon::parse($assignment->assigned_at)->format('d/m/Y') : now()->format('d/m/Y') }}</td>
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
    <div class="section-title">Declaración y Compromiso</div>
    <div class="declaracion">
      Certifico que la línea telefónica corporativa detallada en el presente documento me ha sido asignada para uso exclusivo en el desempeño de mis funciones laborales. Me comprometo a utilizarla únicamente para fines relacionados con mi cargo, a no compartir el número ni permitir su uso por terceros ajenos a la empresa, y a notificar de forma inmediata al área de TICs ante cualquier pérdida, robo, mal funcionamiento o uso no autorizado. Declaro conocer y cumplir las políticas internas de uso de comunicaciones corporativas de la empresa. Todo consumo excedente generado por uso no autorizado o ajeno a las funciones del cargo, así como los costos derivados de pérdida, robo o daño del dispositivo vinculado a esta línea, son de mi única y exclusiva responsabilidad, por lo cual autorizo se descuente el valor correspondiente del pago de planilla; en caso de finalizar mi contrato laboral me comprometo a la devolución inmediata del dispositivo y/o SIM asociados, y autorizo el descuento de salarios, prestaciones sociales, vacaciones, indemnizaciones, bonificaciones y demás derechos que me correspondan del valor correspondiente.
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
