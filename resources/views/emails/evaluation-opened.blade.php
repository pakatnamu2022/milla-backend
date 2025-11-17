{{-- resources/views/emails/evaluation-opened.blade.php --}}
@extends('emails.layouts.base')

@section('content')
  <div style="font:400 14px/1.7 Inter,Arial,Helvetica,sans-serif;color:#111827;">
    <p style="margin:0 0 12px 0;">Estimado/a <strong style="font-weight:600;">{{ $leader_name }}</strong>,</p>

    <div class="card card-muted">
      Le informamos que se ha habilitado una nueva evaluación de desempeño en la plataforma. Es momento de evaluar el desempeño de los miembros de su equipo durante el período indicado.
    </div>

    <div class="callout">
      <div class="callout-title">Información de la evaluación</div>
      <div><strong>Nombre:</strong> {{ $evaluation_name }}</div>
      <div><strong>Fecha de inicio:</strong> {{ $start_date }}</div>
      <div><strong>Fecha límite:</strong> {{ $end_date }}</div>
      <div><strong>Personal a evaluar:</strong> {{ $team_count }} {{ $team_count == 1 ? 'colaborador' : 'colaboradores' }}</div>
    </div>

    @if(!empty($team_members))
      <div class="card">
        <div style="font:600 14px/1.4 Inter,Arial,Helvetica,sans-serif;margin-bottom:8px;">Equipo asignado para evaluación</div>
        <table class="table" role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
          <thead>
          <tr>
            <th align="left">Colaborador</th>
            <th align="left">Posición</th>
            <th align="left">Área</th>
          </tr>
          </thead>
          <tbody>
          @foreach($team_members as $member)
            <tr>
              <td>{{ $member['name'] }}</td>
              <td>{{ $member['position'] ?? 'N/A' }}</td>
              <td>{{ $member['area'] ?? 'N/A' }}</td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
    @endif

    <div class="callout">
      <div class="callout-title">Aspectos a evaluar</div>
      La evaluación de desempeño contempla los siguientes componentes:
      <ul style="margin:8px 0 0 0;padding-left:20px;">
        @if($has_objectives ?? true)
          <li>Objetivos y metas alcanzadas</li>
        @endif
        @if($has_competences ?? true)
          <li>Competencias técnicas y blandas</li>
        @endif
        @if($has_goals ?? true)
          <li>Cumplimiento de indicadores</li>
        @endif
      </ul>
    </div>

    <div class="callout" style="background:#e6edff;border-left-color:#01237e;">
      <div class="callout-title">Importante</div>
      <div>
        • Complete todas las evaluaciones antes de la fecha límite<br>
        • Sea objetivo y constructivo en sus comentarios<br>
        • Los resultados serán utilizados para el desarrollo profesional del equipo
      </div>
    </div>

    @isset($evaluation_url)
      <div style="text-align:center;margin:20px 0 6px;">
        <a href="{{ $evaluation_url }}" class="btn btn-primary">Iniciar Evaluaciones</a>
      </div>
    @endisset

    @isset($additional_notes)
      <div class="card" style="margin-top:16px;">
        <div style="font:600 13px/1.5 Inter,Arial,Helvetica,sans-serif;color:#01237e;margin-bottom:6px;">Nota</div>
        <div>{{ $additional_notes }}</div>
      </div>
    @endisset

    <p style="margin:20px 0 0 0;font-size:13px;color:#6b7280;">
      Si tiene dudas sobre el proceso de evaluación, por favor contacte al área de Recursos Humanos.
    </p>
  </div>
@endsection