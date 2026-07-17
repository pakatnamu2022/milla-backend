{{-- resources/views/emails/evaluation-results-available.blade.php --}}
@extends('emails.layouts.main')

@push('styles')
  <style>
    @media only screen and (max-width: 600px) {
      .ev-feat {
        display: block !important;
        width: 100% !important;
        padding: 14px 0 !important;
      }

      .ev-feat-spacer {
        display: none !important;
      }
    }
  </style>
@endpush

@section('email_subject')
  Tu resultado de desempeño ya está disponible
@endsection
@section('title')
  Tu resultado está disponible
@endsection
@section('subtitle')
  {{ $evaluation_name }}
@endsection

@section('content')
<tr>
<td>

  {{-- Greeting --}}
  <p style="margin:0 0 8px 0;
          font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;
          font-size:15px;line-height:1.7;color:#3a3a3c;">
    Hola, <strong style="color:#1d1d1f;">{{ $person_name }}</strong>.
  </p>
  <p style="margin:0 0 24px 0;
          font-family:system-ui,-apple-system,sans-serif;
          font-size:15px;line-height:1.7;color:#3a3a3c;">
    El período de evaluación <strong style="color:#1d1d1f;">{{ $start_date }} al {{ $end_date }}</strong>
    ha concluido y tu calificación ya está lista. Ingresa a la plataforma para ver tu resultado,
    los detalles por competencia y los comentarios de tu evaluador.
  </p>

  {{-- Feature blocks --}}
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
         style="margin-bottom:28px;">
    <tr>
      <td class="ev-feat" width="31%" align="center" valign="top"
          style="background:#f9f9fb;border-radius:12px;padding:22px 12px;text-align:center;">
        <p style="margin:0 0 8px 0;
                font-family:system-ui,-apple-system,sans-serif;
                font-size:24px;line-height:1;">
          &#128200;
        </p>
        <p style="margin:0 0 5px 0;
                font-family:system-ui,-apple-system,sans-serif;
                font-size:13px;font-weight:600;color:#1d1d1f;">
          Tu calificación
        </p>
        <p style="margin:0;
                font-family:system-ui,-apple-system,sans-serif;
                font-size:12px;color:#aeaeb2;line-height:1.4;">
          Resultado final del período
        </p>
      </td>
      <td class="ev-feat-spacer" width="3.5%"></td>
      <td class="ev-feat" width="31%" align="center" valign="top"
          style="background:#f9f9fb;border-radius:12px;padding:22px 12px;text-align:center;">
        <p style="margin:0 0 8px 0;
                font-family:system-ui,-apple-system,sans-serif;
                font-size:24px;line-height:1;">
          &#128172;
        </p>
        <p style="margin:0 0 5px 0;
                font-family:system-ui,-apple-system,sans-serif;
                font-size:13px;font-weight:600;color:#1d1d1f;">
          Retroalimentación
        </p>
        <p style="margin:0;
                font-family:system-ui,-apple-system,sans-serif;
                font-size:12px;color:#aeaeb2;line-height:1.4;">
          Comentarios de tu evaluador
        </p>
      </td>
      <td class="ev-feat-spacer" width="3.5%"></td>
      <td class="ev-feat" width="31%" align="center" valign="top"
          style="background:#f9f9fb;border-radius:12px;padding:22px 12px;text-align:center;">
        <p style="margin:0 0 8px 0;
                font-family:system-ui,-apple-system,sans-serif;
                font-size:24px;line-height:1;">
          &#127919;
        </p>
        <p style="margin:0 0 5px 0;
                font-family:system-ui,-apple-system,sans-serif;
                font-size:13px;font-weight:600;color:#1d1d1f;">
          Oportunidades
        </p>
        <p style="margin:0;
                font-family:system-ui,-apple-system,sans-serif;
                font-size:12px;color:#aeaeb2;line-height:1.4;">
          Áreas de desarrollo
        </p>
      </td>
    </tr>
  </table>

  {{-- CTA --}}
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
         style="margin-bottom:20px;">
    <tr>
      <td align="center">
        <a href="{{ $results_url }}"
           style="display:inline-block;padding:16px 48px;
                background:#01237e;color:#ffffff;
                font-family:system-ui,-apple-system,sans-serif;
                font-size:15px;font-weight:600;line-height:1;
                text-decoration:none;border-radius:14px;">
          Ver mi resultado de desempeño
        </a>
      </td>
    </tr>
  </table>

  {{-- Contact --}}
  <p style="margin:0;
          font-family:system-ui,-apple-system,sans-serif;
          font-size:13px;line-height:1.6;color:#aeaeb2;text-align:center;">
    ¿Tienes dudas sobre tu resultado? Escríbenos a
    <a href="mailto:{{ $contact_info }}"
       style="color:#01237e;text-decoration:none;">{{ $contact_info }}</a>.
  </p>

  <div style="height:8px;font-size:0;"></div>
</td>
</tr>
@endsection
