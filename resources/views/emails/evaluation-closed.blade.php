{{-- resources/views/emails/evaluation-closed.blade.php --}}
@extends('emails.layouts.base')

@section('content')
  <div style="font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
    <p style="margin:0 0 12px 0;">Estimado/a <strong style="font-weight:600;">{{ $leader_name }}</strong>,</p>

    <div class="card card-muted">
      Le informamos que el período de evaluación <strong>{{ $evaluation_name }}</strong> ha finalizado. A continuación encontrará un resumen general del desempeño de su equipo.
    </div>

    <div class="callout">
      <div class="callout-title">Información de la evaluación</div>
      <div><strong>Evaluación:</strong> {{ $evaluation_name }}</div>
      <div><strong>Período:</strong> {{ $start_date }} - {{ $end_date }}</div>
      <div><strong>Fecha de cierre:</strong> {{ $closed_date }}</div>
      <div><strong>Total evaluados:</strong> {{ $total_evaluated }} de {{ $team_count }}</div>
    </div>

    @isset($team_summary)
      <div class="card">
        <div style="font:600 16px/1.4 Inter,Arial,Helvetica,sans-serif;margin-bottom:12px;color:#01237e;">
          📊 Resumen General del Equipo
        </div>

        <div style="margin-bottom:16px;">
          <div style="font:600 14px/1.4 Inter,Arial,Helvetica,sans-serif;margin-bottom:8px;">
            Promedio General: <span style="font-size:20px;color:#01237e;">{{ number_format($team_summary['average_score'] ?? 0, 2) }}%</span>
          </div>

          {{-- Barra de progreso del promedio --}}
          @php
            $avg = (float)($team_summary['average_score'] ?? 0);
            $barColor = $avg >= 90 ? '#10b981' : ($avg >= 70 ? '#01237e' : ($avg >= 60 ? '#f59e0b' : '#ef4444'));
          @endphp
          <div class="progress" style="height:14px;">
            <b style="height:14px;width:{{ $avg }}%;background:{{ $barColor }};"></b>
          </div>
        </div>

        {{-- Distribución de rendimiento --}}
        @if(!empty($team_summary['performance_distribution']))
          <div style="margin-top:20px;">
            <div style="font:600 14px/1.4 Inter,Arial,Helvetica,sans-serif;margin-bottom:10px;">
              Distribución de Desempeño
            </div>

            @foreach($team_summary['performance_distribution'] as $level => $data)
              @php
                $percentage = $data['percentage'] ?? 0;
                $count = $data['count'] ?? 0;
                $levelColor = match($level) {
                  'Excelente' => '#10b981',
                  'Bueno' => '#01237e',
                  'Regular' => '#f59e0b',
                  'Deficiente' => '#ef4444',
                  default => '#6b7280'
                };
              @endphp

              <div style="margin-bottom:12px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                  <span style="font:600 13px/1 Inter,Arial,Helvetica,sans-serif;color:{{ $levelColor }};">
                    {{ $level }}
                  </span>
                  <span style="font:700 13px/1 Inter,Arial,Helvetica,sans-serif;color:{{ $levelColor }};">
                    {{ $count }} ({{ number_format($percentage, 1) }}%)
                  </span>
                </div>
                <div class="progress" style="height:10px;background:#e5e7eb;">
                  <b style="height:10px;width:{{ $percentage }}%;background:{{ $levelColor }};"></b>
                </div>
              </div>
            @endforeach
          </div>
        @endif

        {{-- Estadísticas adicionales --}}
        @if(!empty($team_summary['stats']))
          <div style="margin-top:20px;padding-top:16px;border-top:1px solid #e6e8ee;">
            <div style="font:600 14px/1.4 Inter,Arial,Helvetica,sans-serif;margin-bottom:10px;">
              Estadísticas Detalladas
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
              @foreach($team_summary['stats'] as $label => $value)
                <div style="padding:10px;background:#f9fafc;border-radius:8px;">
                  <div style="font:400 12px/1.4 Inter,Arial,Helvetica,sans-serif;color:#6b7280;">{{ $label }}</div>
                  <div style="font:700 16px/1.2 Inter,Arial,Helvetica,sans-serif;color:#01237e;">{{ $value }}</div>
                </div>
              @endforeach
            </div>
          </div>
        @endif
      </div>
    @endisset

    @if(!empty($team_results))
      <div class="card">
        <div style="font:600 14px/1.4 Inter,Arial,Helvetica,sans-serif;margin-bottom:8px;">Resultados Individuales</div>
        <table class="table" role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
          <thead>
          <tr>
            <th align="left">Colaborador</th>
            <th align="center">Calificación</th>
            <th align="left">Nivel</th>
          </tr>
          </thead>
          <tbody>
          @foreach($team_results as $result)
            @php
              $score = (float)($result['score'] ?? 0);
              $level = $result['level'] ?? 'N/A';
              $levelColor = match($level) {
                'Excelente' => '#10b981',
                'Bueno' => '#01237e',
                'Regular' => '#f59e0b',
                'Deficiente' => '#ef4444',
                default => '#6b7280'
              };
            @endphp
            <tr>
              <td>{{ $result['employee_name'] }}</td>
              <td align="center" style="font-weight:700;font-size:15px;">{{ number_format($score, 1) }}%</td>
              <td style="font-weight:600;color:{{ $levelColor }};">{{ $level }}</td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
    @endif

    @isset($top_competences)
      <div class="callout" style="background:#f0fdf4;border-left-color:#10b981;">
        <div class="callout-title" style="color:#059669;">✓ Fortalezas del Equipo</div>
        <ul style="margin:6px 0 0 0;padding-left:20px;">
          @foreach($top_competences as $competence)
            <li>{{ $competence }}</li>
          @endforeach
        </ul>
      </div>
    @endisset

    @isset($areas_improvement)
      <div class="callout" style="background:#fff7ed;border-left-color:#f59e0b;">
        <div class="callout-title" style="color:#d97706;">⚠ Áreas de Oportunidad</div>
        <ul style="margin:6px 0 0 0;padding-left:20px;">
          @foreach($areas_improvement as $area)
            <li>{{ $area }}</li>
          @endforeach
        </ul>
      </div>
    @endisset

    <div class="callout">
      <div class="callout-title">Próximos Pasos</div>
      <div>
        • Revisar los resultados individuales con cada miembro del equipo<br>
        • Establecer planes de desarrollo personalizados<br>
        • Programar reuniones de retroalimentación<br>
        • Dar seguimiento a las áreas de mejora identificadas
      </div>
    </div>

    @isset($evaluation_url)
      <div style="text-align:center;margin:20px 0 6px;">
        <a href="{{ $evaluation_url }}" class="btn btn-primary">Ver Resultados Completos</a>
      </div>
    @endisset

    @isset($additional_notes)
      <div class="card" style="margin-top:16px;">
        <div style="font:600 13px/1.5 Inter,Arial,Helvetica,sans-serif;color:#01237e;margin-bottom:6px;">Nota</div>
        <div>{{ $additional_notes }}</div>
      </div>
    @endisset

    <p style="margin:20px 0 0 0;font-size:13px;color:#6b7280;">
      Gracias por su participación en el proceso de evaluación de desempeño. Su compromiso es fundamental para el desarrollo de su equipo.
    </p>
  </div>
@endsection