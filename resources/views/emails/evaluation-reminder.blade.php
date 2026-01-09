{{-- resources/views/emails/evaluations/reminder.blade.php --}}
@extends('emails.layouts.base')

@section('content')
  <div style="font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
    <p style="margin:0 0 12px 0;">Estimado/a <strong style="font-weight:600;">{{ $leader_name }}</strong>,</p>

    <div class="card card-muted">
      Le recordamos que tiene evaluaciones de desempeño pendientes de completar para los miembros de su equipo. Es
      importante finalizarlas antes de la fecha límite para asegurar un proceso completo y justo.
    </div>

    @php
      $pending = (int)($pending_count ?? 0);
      $total   = max(1,(int)($total_count ?? 1));
      $globalProgress = round((($total - $pending) / $total) * 100);
    @endphp

    <div class="card">
      <div style="font:600 14px/1.4 Inter,Arial,Helvetica,sans-serif;margin-bottom:10px;color:#111827;">Resumen de la evaluación</div>
      <div><strong>Nombre:</strong> {{ $evaluation_name }}</div>
      <div><strong>Fecha límite:</strong> {{ $end_date }}</div>
      <div><strong>Pendientes:</strong> {{ $pending_count }} de {{ $total_count }}</div>

      <div style="margin-top:10px;">
        <div class="progress"><b style="width:{{ $globalProgress }}%"></b></div>
        <div style="font:600 12px/1 Inter,Arial,Helvetica,sans-serif;color:#111827;margin-top:6px;">Avance
          general: {{ $globalProgress }}%
        </div>
      </div>
    </div>

    @if(!empty($pending_evaluations))
      <div class="card">
        <div style="font:600 14px/1.4 Inter,Arial,Helvetica,sans-serif;margin-bottom:8px;">Personal a evaluar</div>
        <table class="table" role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
          <thead>
          <tr>
            <th align="left">Colaborador</th>
            <th align="left">Estado</th>
            <th align="left">Progreso</th>
          </tr>
          </thead>
          <tbody>
          @foreach($pending_evaluations as $evaluation)
            @php
              $p = (int)($evaluation['progress_percentage'] ?? 0);
              $stateLabel = $p === 0 ? 'No iniciada' : ($p < 100 ? 'En progreso' : 'Completada');
              $stateColor = $p === 0 ? '#F60404' : ($p < 100 ? '#01237e' : '#10b981');
            @endphp
            <tr>
              <td>{{ $evaluation['employee_name'] }}</td>
              <td style="font-weight:600;color:{{ $stateColor }};">{{ $stateLabel }}</td>
              <td>
                <div class="progress" style="height:8px;"><b style="height:8px;width:{{ $p }}%"></b></div>
                <span
                  style="font:700 12px/1 Inter,Arial,Helvetica,sans-serif;color:#01237e;display:inline-block;margin-top:6px;">{{ number_format($p,0) }}%</span>
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
    @endif

    <div class="card card-muted">
      <div style="font:600 14px/1.4 Inter,Arial,Helvetica,sans-serif;margin-bottom:8px;color:#111827;">Acción requerida</div>
      Por favor ingrese al sistema para completar las evaluaciones pendientes. Su participación es fundamental para el
      desarrollo profesional de su equipo.
    </div>

    @isset($evaluation_url)
      <div style="text-align:center;margin:20px 0 6px;">
        <a href="{{ $evaluation_url }}" class="btn btn-primary">Acceder a las Evaluaciones</a>
      </div>
    @endisset

    @isset($additional_notes)
      <div class="card" style="margin-top:16px;">
        <div style="font:600 14px/1.4 Inter,Arial,Helvetica,sans-serif;margin-bottom:6px;color:#111827;">Nota importante</div>
        <div>{{ $additional_notes }}</div>
      </div>
    @endisset
  </div>
@endsection
