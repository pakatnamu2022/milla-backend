{{-- resources/views/emails/evaluation-results-available.blade.php --}}
@extends('emails.layouts.base')

@push('styles')
<style>
@media only screen and (max-width: 600px) {
  .col3 { display: block !important; width: 100% !important; padding: 12px 0 !important; }
  .col3-spacer { display: none !important; }
}
</style>
@endpush

@section('content')
  {{-- Hero --}}
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
         style="margin:-24px 0 0 0;">
    <tr>
      <td align="center" bgcolor="#01237e"
          style="padding:40px 32px 36px;background:#01237e;border-radius: 12px">
        <div style="font:700 28px/1.2 Inter,Arial,Helvetica,sans-serif;color:#ffffff;margin-bottom:10px;">
          ¡Tu resultado ya está disponible!
        </div>
        <div style="font:400 15px/1.6 Inter,Arial,Helvetica,sans-serif;color:#b3c8fe;">
          {{ $evaluation_name }}
        </div>
      </td>
    </tr>
  </table>

  {{-- Cuerpo --}}
  <div style="padding:8px 0 0 0;font:400 15px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">

    <p style="margin:24px 0 20px 0;">
      Hola <strong>{{ $person_name }}</strong>,
    </p>

    <p style="margin:0 0 20px 0;color:#374151;">
      El período de evaluación de desempeño <strong>{{ $evaluation_name }}</strong> ha concluido
      y tu calificación ya está lista. Este es el resultado del trabajo y dedicación que pusiste
      durante <strong>{{ $start_date }} al {{ $end_date }}</strong>.
    </p>

    <p style="margin:0 0 28px 0;color:#374151;">
      Ingresa a la plataforma para ver tu calificación, los detalles por competencia u objetivo,
      y los comentarios de tu evaluación.
    </p>

    {{-- CTA --}}
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
           style="margin:0 0 28px 0;">
      <tr>
        <td align="center">
          <a href="{{ $results_url }}"
             style="display:inline-block;padding:16px 36px;background:#01237e;color:#ffffff;
                    font:700 15px/1 Inter,Arial,Helvetica,sans-serif;text-decoration:none;
                    border-radius:10px;border:1px solid #0131b1;">
            Ver mi resultado de desempeño
          </a>
        </td>
      </tr>
    </table>

    {{-- Tres puntos visuales en fila --}}
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
           style="margin:0 0 24px 0;">
      <tr>
        <td class="col3" width="32%" align="center" valign="top"
            style="padding:20px 8px;background:#f2f4f8;border-radius:12px;">
          <div style="font:700 22px/1 Inter,Arial,Helvetica,sans-serif;color:#01237e;margin-bottom:6px;">
            &#128200;
          </div>
          <div style="font:600 13px/1.4 Inter,Arial,Helvetica,sans-serif;color:#111827;">
            Tu calificación
          </div>
          <div style="font:400 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#6b7280;margin-top:4px;">
            Resultado final del período
          </div>
        </td>
        <td class="col3-spacer" width="4%"></td>
        <td class="col3" width="32%" align="center" valign="top"
            style="padding:20px 8px;background:#f2f4f8;border-radius:12px;">
          <div style="font:700 22px/1 Inter,Arial,Helvetica,sans-serif;color:#01237e;margin-bottom:6px;">
            &#128172;
          </div>
          <div style="font:600 13px/1.4 Inter,Arial,Helvetica,sans-serif;color:#111827;">
            Retroalimentación
          </div>
          <div style="font:400 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#6b7280;margin-top:4px;">
            Comentarios de tu evaluador
          </div>
        </td>
        <td class="col3-spacer" width="4%"></td>
        <td class="col3" width="32%" align="center" valign="top"
            style="padding:20px 8px;background:#f2f4f8;border-radius:12px;">
          <div style="font:700 22px/1 Inter,Arial,Helvetica,sans-serif;color:#01237e;margin-bottom:6px;">
            &#127919;
          </div>
          <div style="font:600 13px/1.4 Inter,Arial,Helvetica,sans-serif;color:#111827;">
            Oportunidades
          </div>
          <div style="font:400 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#6b7280;margin-top:4px;">
            Áreas de desarrollo
          </div>
        </td>
      </tr>
    </table>

    <p style="margin:0;font:400 13px/1.6 Inter,Arial,Helvetica,sans-serif;color:#6b7280;">
      ¿Tienes dudas sobre tu resultado? Conversa con tu líder directo o escríbenos a
      <a href="mailto:{{ $contact_info }}" style="color:#01237e;text-decoration:none;">{{ $contact_info }}</a>.
    </p>
  </div>
@endsection
