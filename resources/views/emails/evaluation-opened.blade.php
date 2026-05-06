{{-- resources/views/emails/evaluation-opened.blade.php --}}
@extends('emails.layouts.evaluation')

@section('email_subject') Nueva Evaluación de Desempeño Habilitada @endsection
@section('title') Nueva evaluación abierta @endsection
@section('subtitle') Se ha habilitado una evaluación de desempeño para tu equipo. @endsection

@section('content')
@php
  $shown = array_slice($team_members ?? [], 0, 4);
  $extra = max(0, count($team_members ?? []) - 4);
@endphp

{{-- Greeting --}}
<p style="margin:0 0 20px 0;
          font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;
          font-size:15px;line-height:1.7;color:#3a3a3c;">
  Hola, <strong style="color:#1d1d1f;">{{ $leader_name }}</strong>.
  Es momento de evaluar el desempeño de los miembros de tu equipo durante el período indicado.
</p>

{{-- Evaluation info card --}}
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
       style="background:#f9f9fb;border-radius:14px;margin-bottom:24px;">
  <tr>
    <td style="padding:24px;">

      {{-- Evaluation name --}}
      <p style="margin:0 0 3px 0;
                font-family:system-ui,-apple-system,sans-serif;
                font-size:11px;font-weight:600;color:#aeaeb2;
                text-transform:uppercase;letter-spacing:0.8px;">
        Evaluación
      </p>
      <p style="margin:0 0 20px 0;
                font-family:system-ui,-apple-system,sans-serif;
                font-size:16px;font-weight:600;color:#1d1d1f;">
        {{ $evaluation_name }}
      </p>

      {{-- Period + team count --}}
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
             class="ev-stats">
        <tr>
          <td width="50%" valign="top" style="padding-right:12px;">
            <p style="margin:0 0 3px 0;
                      font-family:system-ui,-apple-system,sans-serif;
                      font-size:11px;font-weight:600;color:#aeaeb2;
                      text-transform:uppercase;letter-spacing:0.6px;">
              Período
            </p>
            <p style="margin:0;
                      font-family:system-ui,-apple-system,sans-serif;
                      font-size:14px;font-weight:500;color:#1d1d1f;">
              {{ $start_date }} — {{ $end_date }}
            </p>
          </td>
          <td width="50%" align="right" valign="top">
            <p style="margin:0;
                      font-family:system-ui,-apple-system,sans-serif;
                      font-size:26px;font-weight:700;line-height:1;color:#01237e;">
              {{ $team_count }}
            </p>
            <p style="margin:4px 0 0 0;
                      font-family:system-ui,-apple-system,sans-serif;
                      font-size:11px;color:#aeaeb2;
                      text-transform:uppercase;letter-spacing:0.5px;">
              {{ $team_count == 1 ? 'Colaborador' : 'Colaboradores' }}
            </p>
          </td>
        </tr>
      </table>

    </td>
  </tr>
</table>

{{-- Team list --}}
@if(!empty($shown))
  <p style="margin:0 0 12px 0;
            font-family:system-ui,-apple-system,sans-serif;
            font-size:11px;font-weight:600;color:#aeaeb2;
            text-transform:uppercase;letter-spacing:0.8px;">
    Equipo asignado
  </p>

  @foreach($shown as $index => $member)
    @php
      $words    = preg_split('/\s+/', trim($member['name']));
      $initials = strtoupper(substr($words[0] ?? '', 0, 1) . substr($words[1] ?? '', 0, 1));
      $isLast   = ($index === count($shown) - 1) && $extra === 0;
    @endphp

    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
      <tr>
        <td width="44" valign="middle" style="padding:11px 0;">
          <span style="display:inline-block;width:36px;height:36px;line-height:36px;
                       border-radius:50%;background:#01237e;color:#ffffff;
                       font-family:system-ui,-apple-system,sans-serif;
                       font-size:12px;font-weight:600;text-align:center;">
            {{ $initials }}
          </span>
        </td>
        <td valign="middle" style="padding:11px 0;">
          <p style="margin:0;
                    font-family:system-ui,-apple-system,sans-serif;
                    font-size:14px;font-weight:500;color:#1d1d1f;">
            {{ $member['name'] }}
          </p>
          @if(!empty($member['position']) && $member['position'] !== 'N/A')
            <p style="margin:2px 0 0 0;
                      font-family:system-ui,-apple-system,sans-serif;
                      font-size:12px;color:#aeaeb2;">
              {{ $member['position'] }}
            </p>
          @endif
        </td>
        <td align="right" valign="middle" style="padding:11px 0;white-space:nowrap;">
          <span style="display:inline-block;padding:4px 10px;
                       background:#eff6ff;border-radius:999px;
                       font-family:system-ui,-apple-system,sans-serif;
                       font-size:11px;font-weight:600;color:#1e40af;">
            Por evaluar
          </span>
        </td>
      </tr>
      @if(!$isLast)
      <tr>
        <td colspan="3" style="padding:0;font-size:0;line-height:0;">
          <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
            <tr><td height="1" bgcolor="#f5f5f7" style="font-size:0;line-height:0;">&nbsp;</td></tr>
          </table>
        </td>
      </tr>
      @endif
    </table>
  @endforeach

  @if($extra > 0)
    <p style="margin:12px 0 0 0;
              font-family:system-ui,-apple-system,sans-serif;
              font-size:13px;color:#aeaeb2;text-align:center;">
      +{{ $extra }} {{ $extra === 1 ? 'colaborador más' : 'colaboradores más' }}
    </p>
  @endif
@endif

{{-- What will be evaluated --}}
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
       style="background:#f9f9fb;border-radius:14px;margin-top:24px;">
  <tr>
    <td style="padding:20px 24px;">
      <p style="margin:0 0 10px 0;
                font-family:system-ui,-apple-system,sans-serif;
                font-size:13px;font-weight:600;color:#1d1d1f;">
        Aspectos a evaluar
      </p>
      @if($has_objectives ?? true)
        <p style="margin:0 0 6px 0;
                  font-family:system-ui,-apple-system,sans-serif;
                  font-size:13px;line-height:1.5;color:#3a3a3c;">
          · Objetivos y metas del período
        </p>
      @endif
      <p style="margin:0 0 6px 0;
                font-family:system-ui,-apple-system,sans-serif;
                font-size:13px;line-height:1.5;color:#3a3a3c;">
        · Cumplimiento de indicadores
      </p>
      @if($has_competences ?? true)
        <p style="margin:0;
                  font-family:system-ui,-apple-system,sans-serif;
                  font-size:13px;line-height:1.5;color:#3a3a3c;">
          · Competencias técnicas y blandas
        </p>
      @endif
    </td>
  </tr>
</table>

{{-- CTA --}}
@isset($evaluation_url)
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
         style="margin-top:28px;">
    <tr>
      <td align="center">
        <a href="{{ $evaluation_url }}"
           style="display:inline-block;padding:15px 40px;
                  background:#01237e;color:#ffffff;
                  font-family:system-ui,-apple-system,sans-serif;
                  font-size:15px;font-weight:600;line-height:1;
                  text-decoration:none;border-radius:12px;
                  border:1px solid #0131b1;">
          Iniciar evaluaciones
        </a>
      </td>
    </tr>
  </table>
@endisset

{{-- Additional notes --}}
@isset($additional_notes)
  <p style="margin:20px 0 0 0;
            font-family:system-ui,-apple-system,sans-serif;
            font-size:13px;line-height:1.6;color:#aeaeb2;text-align:center;">
    {{ $additional_notes }}
  </p>
@endisset

<div style="height:8px;font-size:0;"></div>
@endsection
