@extends('emails.layouts.base')

@section('content')

  {{-- Saludo --}}
  <p style="margin:0 0 20px 0;font:400 15px/1.7 Inter,Arial,Helvetica,sans-serif;color:#374151;">
    Se informa que el vehículo con <strong style="color:#111827;">VIN: {{ $vehicle_vin }}</strong>
    ha sido recepcionado correctamente.
  </p>

  {{-- Información del Vehículo --}}
  <div class="card card-muted" style="margin-bottom:16px;">
    <p style="margin:0 0 10px 0;font:600 13px/1.4 Inter,Arial,Helvetica,sans-serif;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;">
      Vehículo
    </p>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
      <tr>
        <td style="padding:4px 0;width:38%;font:400 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#6b7280;">VIN</td>
        <td style="padding:4px 0;font:600 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#111827;">{{ $vehicle_vin }}</td>
      </tr>
      <tr>
        <td style="padding:4px 0;font:400 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#6b7280;">Marca</td>
        <td style="padding:4px 0;font:400 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#111827;">{{ $vehicle_brand }}</td>
      </tr>
      <tr>
        <td style="padding:4px 0;font:400 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#6b7280;">Modelo</td>
        <td style="padding:4px 0;font:400 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#111827;">{{ $vehicle_model }}</td>
      </tr>
      <tr>
        <td style="padding:4px 0;font:400 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#6b7280;">Año</td>
        <td style="padding:4px 0;font:400 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#111827;">{{ $vehicle_year }}</td>
      </tr>
      <tr>
        <td style="padding:4px 0;font:400 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#6b7280;">Color</td>
        <td style="padding:4px 0;font:400 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#111827;">{{ $vehicle_color }}</td>
      </tr>
    </table>
  </div>

  {{-- Detalles de Recepción --}}
  <div class="card card-muted" style="margin-bottom:16px;">
    <p style="margin:0 0 10px 0;font:600 13px/1.4 Inter,Arial,Helvetica,sans-serif;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;">
      Recepción
    </p>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
      <tr>
        <td style="padding:4px 0;width:38%;font:400 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#6b7280;">Origen</td>
        <td style="padding:4px 0;font:400 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#111827;">{{ $origin }}</td>
      </tr>
      <tr>
        <td style="padding:4px 0;font:400 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#6b7280;">Destino</td>
        <td style="padding:4px 0;font:400 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#111827;">{{ $destination }}</td>
      </tr>
      <tr>
        <td style="padding:4px 0;font:400 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#6b7280;">Fecha</td>
        <td style="padding:4px 0;font:600 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#059669;">{{ $received_date }}</td>
      </tr>
      <tr>
        <td style="padding:4px 0;font:400 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#6b7280;">Recepcionado por</td>
        <td style="padding:4px 0;font:400 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#111827;">{{ $received_by }}</td>
      </tr>
    </table>
  </div>

  {{-- Checklist recepcionado --}}
  @if(count($received_items) > 0)
    <div class="card" style="margin-bottom:16px;">
      <p style="margin:0 0 10px 0;font:600 13px/1.4 Inter,Arial,Helvetica,sans-serif;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;">
        Checklist recepcionado
      </p>
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
        @foreach($received_items as $item)
          <tr>
            <td style="padding:5px 0;border-bottom:1px solid #f3f4f6;font:400 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#374151;">
              {{ $item['name'] }}
            </td>
            <td style="padding:5px 0;border-bottom:1px solid #f3f4f6;text-align:right;font:600 14px/1.5 Inter,Arial,Helvetica,sans-serif;color:#111827;white-space:nowrap;">
              {{ $item['quantity'] }} <span style="font-weight:400;color:#9ca3af;">und.</span>
            </td>
          </tr>
        @endforeach
      </table>
    </div>
  @endif

  {{-- Observaciones --}}
  @if(!empty($note))
    <div class="callout" style="margin-bottom:16px;">
      <div class="callout-title">Observaciones</div>
      <div style="font:400 14px/1.6 Inter,Arial,Helvetica,sans-serif;color:#374151;">{{ $note }}</div>
    </div>
  @endif

  {{-- Fotos adjuntas --}}
  @if(!empty($has_photos))
    <div style="margin-bottom:16px;padding:12px 14px;border-radius:8px;background:#f0fdf4;border:1px solid #bbf7d0;">
      <p style="margin:0;font:400 13px/1.6 Inter,Arial,Helvetica,sans-serif;color:#166534;">
        Se adjuntan fotografías del vehículo al presente correo.
      </p>
    </div>
  @endif

  {{-- Acción --}}
  <p style="margin:0;font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#6b7280;">
    Por favor, coordinar fecha de entrega y lavado del vehículo.
  </p>

@endsection
