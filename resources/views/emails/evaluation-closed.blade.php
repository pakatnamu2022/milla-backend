{{-- resources/views/emails/evaluation-closed.blade.php --}}
@extends('emails.layouts.evaluation')

@section('email_subject') Evaluación de Desempeño Finalizada — Resumen @endsection
@section('title') Evaluación finalizada @endsection
@section('subtitle') El período de evaluación ha concluido. Aquí tienes el resumen de tu equipo. @endsection

@section('content')

{{-- Greeting --}}
<p style="margin:0 0 20px 0;
          font-family:system-ui,-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif;
          font-size:15px;line-height:1.7;color:#3a3a3c;">
  Hola, <strong style="color:#1d1d1f;">{{ $leader_name }}</strong>.
  El período <strong style="color:#1d1d1f;">{{ $evaluation_name }}</strong> ha finalizado.
  A continuación encontrarás el resumen de desempeño de tu equipo.
</p>

{{-- Evaluation meta --}}
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
       style="background:#f9f9fb;border-radius:14px;margin-bottom:24px;">
  <tr>
    <td style="padding:24px;">

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
                      font-size:13px;font-weight:500;color:#1d1d1f;">
              {{ $start_date }} — {{ $end_date }}
            </p>
          </td>
          <td width="50%" align="right" valign="top">
            <p style="margin:0 0 3px 0;
                      font-family:system-ui,-apple-system,sans-serif;
                      font-size:11px;font-weight:600;color:#aeaeb2;
                      text-transform:uppercase;letter-spacing:0.6px;">
              Evaluados
            </p>
            <p style="margin:0;
                      font-family:system-ui,-apple-system,sans-serif;
                      font-size:13px;font-weight:500;color:#1d1d1f;">
              {{ $total_evaluated }} de {{ $team_count }}
            </p>
          </td>
        </tr>
      </table>

    </td>
  </tr>
</table>

{{-- Team summary --}}
@isset($team_summary)
  @php
    $avg      = (float)($team_summary['average_score'] ?? 0);
    $avgInt   = (int) round($avg);
    $barColor = $avg >= 90 ? '#10b981' : ($avg >= 70 ? '#01237e' : ($avg >= 60 ? '#f59e0b' : '#ef4444'));
  @endphp

  {{-- Average score block --}}
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
         style="background:#f9f9fb;border-radius:14px;margin-bottom:20px;">
    <tr>
      <td style="padding:24px;">

        <p style="margin:0 0 3px 0;
                  font-family:system-ui,-apple-system,sans-serif;
                  font-size:11px;font-weight:600;color:#aeaeb2;
                  text-transform:uppercase;letter-spacing:0.8px;">
          Promedio del equipo
        </p>
        <p style="margin:0 0 10px 0;
                  font-family:system-ui,-apple-system,sans-serif;
                  font-size:38px;font-weight:700;line-height:1;
                  color:{{ $barColor }};letter-spacing:-1px;">
          {{ number_format($avg, 1) }}%
        </p>

        {{-- Progress bar --}}
        <div style="background:#e8e8ed;border-radius:3px;height:6px;margin-bottom:20px;
                    line-height:6px;font-size:1px;">
          <div style="background:{{ $barColor }};border-radius:3px;height:6px;
                      width:{{ $avgInt }}%;font-size:1px;"></div>
        </div>

        {{-- Stats grid --}}
        @if(!empty($team_summary['stats']))
          <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
            @php $statsArr = array_chunk(array_keys($team_summary['stats']), 2, true); @endphp
            @foreach(array_chunk(array_keys($team_summary['stats']), 2) as $chunk)
              <tr>
                @foreach($chunk as $label)
                  <td width="50%" valign="top" style="padding-bottom:14px;padding-right:8px;">
                    <p style="margin:0 0 2px 0;
                              font-family:system-ui,-apple-system,sans-serif;
                              font-size:11px;color:#aeaeb2;
                              text-transform:uppercase;letter-spacing:0.5px;">
                      {{ $label }}
                    </p>
                    <p style="margin:0;
                              font-family:system-ui,-apple-system,sans-serif;
                              font-size:15px;font-weight:600;color:#1d1d1f;">
                      {{ $team_summary['stats'][$label] }}
                    </p>
                  </td>
                @endforeach
              </tr>
            @endforeach
          </table>
        @endif

      </td>
    </tr>
  </table>

  {{-- Performance distribution --}}
  @if(!empty($team_summary['performance_distribution']))
    <p style="margin:0 0 12px 0;
              font-family:system-ui,-apple-system,sans-serif;
              font-size:11px;font-weight:600;color:#aeaeb2;
              text-transform:uppercase;letter-spacing:0.8px;">
      Distribución de desempeño
    </p>

    @foreach($team_summary['performance_distribution'] as $level => $data)
      @php
        $pct = (float)($data['percentage'] ?? 0);
        $cnt = (int)($data['count'] ?? 0);
        $pctInt = (int) round($pct);
        $levelColor = match($level) {
          'Excelente' => '#10b981',
          'Bueno'     => '#01237e',
          'Regular'   => '#f59e0b',
          'Deficiente'=> '#ef4444',
          default     => '#6b7280'
        };
      @endphp
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
             style="margin-bottom:14px;">
        <tr>
          <td valign="middle" width="80">
            <p style="margin:0;
                      font-family:system-ui,-apple-system,sans-serif;
                      font-size:13px;font-weight:600;color:{{ $levelColor }};">
              {{ $level }}
            </p>
          </td>
          <td valign="middle" style="padding:0 12px;">
            <div style="background:#e8e8ed;border-radius:3px;height:6px;
                        line-height:6px;font-size:1px;">
              <div style="background:{{ $levelColor }};border-radius:3px;height:6px;
                          width:{{ $pctInt }}%;font-size:1px;"></div>
            </div>
          </td>
          <td valign="middle" align="right" width="48" style="white-space:nowrap;">
            <p style="margin:0;
                      font-family:system-ui,-apple-system,sans-serif;
                      font-size:12px;font-weight:600;color:{{ $levelColor }};">
              {{ $cnt }} ({{ number_format($pct, 0) }}%)
            </p>
          </td>
        </tr>
      </table>
    @endforeach
  @endif
@endisset

{{-- Strengths --}}
@isset($top_competences)
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
         style="background:#f0fdf4;border-radius:14px;margin-top:20px;">
    <tr>
      <td style="padding:20px 24px;">
        <p style="margin:0 0 10px 0;
                  font-family:system-ui,-apple-system,sans-serif;
                  font-size:13px;font-weight:600;color:#166534;">
          Fortalezas del equipo
        </p>
        @foreach($top_competences as $competence)
          <p style="margin:0 0 5px 0;
                    font-family:system-ui,-apple-system,sans-serif;
                    font-size:13px;line-height:1.5;color:#3a3a3c;">
            · {{ $competence }}
          </p>
        @endforeach
      </td>
    </tr>
  </table>
@endisset

{{-- Areas of improvement --}}
@isset($areas_improvement)
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%"
         style="background:#fffbeb;border-radius:14px;margin-top:12px;">
    <tr>
      <td style="padding:20px 24px;">
        <p style="margin:0 0 10px 0;
                  font-family:system-ui,-apple-system,sans-serif;
                  font-size:13px;font-weight:600;color:#92400e;">
          Oportunidades de mejora
        </p>
        @foreach($areas_improvement as $area)
          <p style="margin:0 0 5px 0;
                    font-family:system-ui,-apple-system,sans-serif;
                    font-size:13px;line-height:1.5;color:#3a3a3c;">
            · {{ $area }}
          </p>
        @endforeach
      </td>
    </tr>
  </table>
@endisset

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
          Ver resultados completos
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
