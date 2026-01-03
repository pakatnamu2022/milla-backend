<?php

namespace App\Http\Services\common;

use App\Models\gp\gestionhumana\evaluacion\Evaluation;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPerson;
use App\Models\gp\gestionhumana\personal\Worker;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EvaluationNotificationService
{
  private EmailService $emailService;

  public function __construct(EmailService $emailService)
  {
    $this->emailService = $emailService;
  }

  /**
   * Envía recordatorios a líderes con evaluaciones pendientes
   */
  public function sendPendingEvaluationReminders(int $evaluationId = null): array
  {
    $results = [];

    try {
      $evaluations = $evaluationId
        ? Evaluation::where('id', $evaluationId)->get()
        : $this->getActiveEvaluations();

      foreach ($evaluations as $evaluation) {
        $leaderReminders = $this->processEvaluationReminders($evaluation);
        $results = array_merge($results, $leaderReminders);
      }

      return [
        'success' => true,
        'total_sent' => count($results),
        'results' => $results
      ];

    } catch (\Exception $e) {
      return [
        'success' => false,
        'error' => $e->getMessage(),
        'total_sent' => 0,
        'results' => []
      ];
    }
  }

  /**
   * Procesa los recordatorios para una evaluación específica
   */
  private function processEvaluationReminders(Evaluation $evaluation): array
  {
    $results = [];
    $leadersWithPending = $this->getLeadersWithPendingEvaluations($evaluation);

    foreach ($leadersWithPending as $leaderId => $pendingData) {
      $leader = Worker::find($leaderId);

      if (!$leader || !$leader->email) {
        continue;
      }

      $emailResult = $this->sendReminderToLeader($evaluation, $leader, $pendingData);
      $results[] = $emailResult;
    }

    return $results;
  }

  /**
   * Obtiene evaluaciones activas que requieren recordatorios
   */
  private function getActiveEvaluations(): Collection
  {
    $now = Carbon::now();

    return Evaluation::where('status', 1) // En progreso
    ->where('end_date', '>', $now)
      ->where('end_date', '<=', $now->copy()->addDays(7)) // Próximas a vencer en 7 días
      ->get();
  }

  /**
   * Obtiene líderes con evaluaciones pendientes para una evaluación específica
   */
  private function getLeadersWithPendingEvaluations(Evaluation $evaluation): array
  {
    $evaluationPersons = EvaluationPerson::where('evaluation_id', $evaluation->id)
      ->with(['person'])
      ->get();

    $leadersPending = [];

    foreach ($evaluationPersons as $evalPerson) {
      $leaderId = $evalPerson->chief_id;

      if (!$leaderId) {
        continue;
      }

      if (!isset($leadersPending[$leaderId])) {
        $leadersPending[$leaderId] = [
          'leader_id' => $leaderId,
          'total_evaluations' => 0,
          'pending_evaluations' => 0,
          'pending_details' => []
        ];
      }

      $leadersPending[$leaderId]['total_evaluations']++;

      // Verificar si la evaluación está pendiente
      $isCompleted = $evalPerson->wasEvaluated == 1;

      if (!$isCompleted) {
        $leadersPending[$leaderId]['pending_evaluations']++;
        $leadersPending[$leaderId]['pending_details'][] = [
          'employee_name' => $evalPerson->person->nombre_completo ?? 'N/A',
          'progress_percentage' => $this->calculateEvaluationProgress($evalPerson),
          'employee_id' => $evalPerson->person_id
        ];
      }
    }

    // Filtrar solo líderes que tienen evaluaciones pendientes
    return array_filter($leadersPending, function ($data) {
      return $data['pending_evaluations'] > 0;
    });
  }

  /**
   * Calcula el progreso de una evaluación específica
   */
  private function calculateEvaluationProgress(EvaluationPerson $evaluationPerson): float
  {
    if ($evaluationPerson->wasEvaluated == 1) {
      return 100.0;
    }

    // Si no está evaluada, verificamos si tiene algún progreso parcial
    if ($evaluationPerson->result !== null && $evaluationPerson->result > 0) {
      return 50.0; // Progreso parcial
    }

    return 0.0; // No iniciada
  }

  /**
   * Envía el recordatorio a un líder específico
   */
  private function sendReminderToLeader(Evaluation $evaluation, Worker $leader, array $pendingData): array
  {
    try {
      $emailConfig = [
        'to' => $leader->email,
        'subject' => 'Recordatorio: Evaluaciones de Desempeño Pendientes',
        'template' => 'emails.evaluation-reminder',
        'data' => [
          'title' => 'Recordatorio de Evaluación de Desempeño',
          'subtitle' => 'Evaluaciones Pendientes de Completar',
          'leader_name' => $leader->nombre_completo,
          'evaluation_name' => $evaluation->name,
          'end_date' => Carbon::parse($evaluation->end_date)->format('d/m/Y'),
          'pending_count' => $pendingData['pending_evaluations'],
          'total_count' => $pendingData['total_evaluations'],
          'pending_evaluations' => $pendingData['pending_details'],
          'evaluation_url' => config('app.frontend_url') . '/evaluations/' . $evaluation->id,
          'additional_notes' => $this->generateAdditionalNotes($evaluation),
          'send_date' => now()->format('d/m/Y H:i'),
          'company_name' => 'Grupo Pakana',
          'contact_info' => 'rrhh@grupopakatnamu.com'
        ]
      ];

      $sent = $this->emailService->queue($emailConfig); // Usando cola de trabajo

      return [
        'leader_id' => $leader->id,
        'leader_name' => $leader->nombre_completo,
        'leader_email' => $leader->email,
        'pending_count' => $pendingData['pending_evaluations'],
        'total_count' => $pendingData['total_evaluations'],
        'sent' => $sent,
        'evaluation_id' => $evaluation->id,
        'evaluation_name' => $evaluation->name
      ];

    } catch (\Exception $e) {
      // Log::error("Error sending reminder to leader {$leader->id}: " . $e->getMessage());

      return [
        'leader_id' => $leader->id,
        'leader_name' => $leader->nombre_completo,
        'leader_email' => $leader->email,
        'pending_count' => $pendingData['pending_evaluations'],
        'total_count' => $pendingData['total_evaluations'],
        'sent' => false,
        'error' => $e->getMessage(),
        'evaluation_id' => $evaluation->id,
        'evaluation_name' => $evaluation->name
      ];
    }
  }

  /**
   * Genera notas adicionales basadas en la evaluación
   */
  private function generateAdditionalNotes(Evaluation $evaluation): string
  {
    $daysUntilEnd = Carbon::now()->diffInDays(Carbon::parse($evaluation->end_date), false);

    if ($daysUntilEnd <= 3) {
      return "Esta evaluación vence en {$daysUntilEnd} días. Por favor complete las evaluaciones pendientes lo antes posible.";
    } elseif ($daysUntilEnd <= 7) {
      return "Esta evaluación vence en {$daysUntilEnd} días. Recomendamos completar las evaluaciones pronto.";
    }

    return "Recuerde completar todas las evaluaciones antes de la fecha límite para asegurar un proceso completo.";
  }

  /**
   * Envía notificación de apertura de evaluación a líderes
   */
  public function sendEvaluationOpened(int $evaluationId): array
  {
    try {
      $evaluation = Evaluation::find($evaluationId);

      if (!$evaluation) {
        return [
          'success' => false,
          'error' => 'Evaluación no encontrada',
          'total_sent' => 0,
          'results' => []
        ];
      }

      if ($evaluation->send_opened_email) {
        return [
          'success' => false,
          'error' => 'El correo de apertura ya fue enviado para esta evaluación',
          'total_sent' => 0,
          'results' => []
        ];
      }

      $leaders = $this->getLeadersForEvaluation($evaluation);
      $results = [];
      $allSuccessful = true; // Bandera para verificar si todos se enviaron

      if (empty($leaders)) {
        return [
          'success' => false,
          'error' => 'No se encontraron líderes para esta evaluación',
          'total_sent' => 0,
          'results' => []
        ];
      }

      foreach ($leaders as $leaderId => $leaderData) {
        $leader = Worker::find($leaderId);

        if (!$leader || !$leader->email) {
          continue;
        }

        $emailResult = $this->sendOpenedEmailToLeader($evaluation, $leader, $leaderData);
        $results[] = $emailResult;

        // Verificar si este correo falló
        if (!$emailResult['sent']) {
          $allSuccessful = false;
        }
      }

      // Solo actualizar si TODOS los correos se enviaron exitosamente
      if ($allSuccessful && count($results) > 0) {
        $evaluation->update(['send_opened_email' => true]);
      }

      return [
        'success' => $allSuccessful,
        'total_sent' => count(array_filter($results, fn($r) => $r['sent'])),
        'total_failed' => count(array_filter($results, fn($r) => !$r['sent'])),
        'results' => $results
      ];

    } catch (\Exception $e) {
      return [
        'success' => false,
        'error' => $e->getMessage(),
        'total_sent' => 0,
        'results' => []
      ];
    }
  }

  /**
   * Obtiene líderes con sus equipos para una evaluación
   */
  private function getLeadersForEvaluation(Evaluation $evaluation): array
  {
    $evaluationPersons = EvaluationPerson::where('evaluation_id', $evaluation->id)
      ->with(['person', 'person.position', 'person.area'])
      ->get();

    $leadersData = [];

    foreach ($evaluationPersons as $evalPerson) {
      $leaderId = $evalPerson->chief_id;

      if (!$leaderId) {
        continue;
      }

      if (!isset($leadersData[$leaderId])) {
        $leadersData[$leaderId] = [
          'leader_id' => $leaderId,
          'team_count' => 0,
          'team_members' => [],
          'person_ids' => [] // Para trackear los person_id únicos
        ];
      }

      // Verificar si ya agregamos este person_id para evitar duplicados
      if (in_array($evalPerson->person_id, $leadersData[$leaderId]['person_ids'])) {
        continue;
      }

      $leadersData[$leaderId]['person_ids'][] = $evalPerson->person_id;
      $leadersData[$leaderId]['team_count']++;
      $leadersData[$leaderId]['team_members'][] = [
        'name' => $evalPerson->person->nombre_completo ?? 'N/A',
        'position' => $evalPerson->person->position->name ?? 'N/A',
        'area' => $evalPerson->person->area->name ?? 'N/A',
        'employee_id' => $evalPerson->person_id
      ];
    }

    // Remover el array person_ids antes de retornar (no lo necesitamos en el resultado final)
    foreach ($leadersData as &$leaderData) {
      unset($leaderData['person_ids']);
    }

    return $leadersData;
  }

  /**
   * Envía correo de apertura a un líder
   */
  private function sendOpenedEmailToLeader(Evaluation $evaluation, Worker $leader, array $leaderData): array
  {
    try {
      $emailConfig = [
        'to' => $leader->email2,
        'subject' => 'Nueva Evaluación de Desempeño Habilitada',
        'template' => 'emails.evaluation-opened',
        'data' => [
          'title' => 'Evaluación de Desempeño Habilitada',
          'subtitle' => 'Su equipo está listo para ser evaluado',
          'badge' => 'Nueva Evaluación',
          'leader_name' => $leader->nombre_completo,
          'evaluation_name' => $evaluation->name,
          'start_date' => Carbon::parse($evaluation->start_date)->format('d/m/Y'),
          'end_date' => Carbon::parse($evaluation->end_date)->format('d/m/Y'),
          'team_count' => $leaderData['team_count'],
          'team_members' => $leaderData['team_members'],
          'has_objectives' => true,
          'has_competences' => true,
          'has_goals' => true,
          'evaluation_url' => config('app.frontend_url'),
          'additional_notes' => 'Recuerde que completar las evaluaciones a tiempo contribuye al desarrollo profesional de su equipo.',
          'date' => now()->format('d/m/Y H:i'),
          'company_name' => 'Grupo Pakatnamu',
          'contact_info' => 'rrhh@grupopakatnamu.com'
        ]
      ];

      $sent = $this->emailService->queue($emailConfig); // Usando cola de trabajo

      return [
        'leader_id' => $leader->id,
        'leader_name' => $leader->nombre_completo,
        'leader_email' => $leader->email,
        'team_count' => $leaderData['team_count'],
        'sent' => $sent,
        'evaluation_id' => $evaluation->id,
        'evaluation_name' => $evaluation->name
      ];

    } catch (\Exception $e) {
      return [
        'leader_id' => $leader->id,
        'leader_name' => $leader->nombre_completo,
        'leader_email' => $leader->email2,
        'team_count' => $leaderData['team_count'] ?? 0,
        'sent' => false,
        'error' => $e->getMessage(),
        'evaluation_id' => $evaluation->id,
        'evaluation_name' => $evaluation->name
      ];
    }
  }

  /**
   * Envía resumen final cuando se cierra una evaluación
   */
  public function sendEvaluationClosed(int $evaluationId): array
  {
    try {
      $evaluation = Evaluation::find($evaluationId);

      if (!$evaluation) {
        return [
          'success' => false,
          'error' => 'Evaluación no encontrada',
          'total_sent' => 0,
          'results' => []
        ];
      }

      if ($evaluation->send_closed_email) {
        return [
          'success' => false,
          'error' => 'El correo de cierre ya fue enviado para esta evaluación',
          'total_sent' => 0,
          'results' => []
        ];
      }

      $leaders = $this->getLeadersForEvaluation($evaluation);
      $results = [];
      $allSuccessful = true; // Bandera para verificar si todos se enviaron

      if (empty($leaders)) {
        return [
          'success' => false,
          'error' => 'No se encontraron líderes para esta evaluación',
          'total_sent' => 0,
          'results' => []
        ];
      }

      foreach ($leaders as $leaderId => $leaderData) {
        $leader = Worker::find($leaderId);

        if (!$leader || !$leader->email) {
          continue;
        }

        $teamSummary = $this->calculateTeamSummary($evaluation, $leaderId);
        $emailResult = $this->sendClosedEmailToLeader($evaluation, $leader, $leaderData, $teamSummary);
        $results[] = $emailResult;

        // Verificar si este correo falló
        if (!$emailResult['sent']) {
          $allSuccessful = false;
        }
      }

      // Solo actualizar si TODOS los correos se enviaron exitosamente
      if ($allSuccessful && count($results) > 0) {
        $evaluation->update(['send_closed_email' => true]);
      }

      return [
        'success' => $allSuccessful,
        'total_sent' => count(array_filter($results, fn($r) => $r['sent'])),
        'total_failed' => count(array_filter($results, fn($r) => !$r['sent'])),
        'results' => $results
      ];

    } catch (\Exception $e) {
      return [
        'success' => false,
        'error' => $e->getMessage(),
        'total_sent' => 0,
        'results' => []
      ];
    }
  }

  /**
   * Calcula el resumen de desempeño del equipo
   */
  private function calculateTeamSummary(Evaluation $evaluation, int $leaderId): array
  {
    $evaluationPersons = EvaluationPerson::where('evaluation_id', $evaluation->id)
      ->where('chief_id', $leaderId)
      ->with('person')
      ->get();

    $totalEvaluated = 0;
    $totalScore = 0;
    $teamResults = [];
    $distribution = [
      'Excelente' => ['count' => 0, 'percentage' => 0],
      'Bueno' => ['count' => 0, 'percentage' => 0],
      'Regular' => ['count' => 0, 'percentage' => 0],
      'Deficiente' => ['count' => 0, 'percentage' => 0]
    ];

    foreach ($evaluationPersons as $evalPerson) {
      if ($evalPerson->wasEvaluated == 1 && $evalPerson->result !== null) {
        $totalEvaluated++;
        $score = (float)$evalPerson->result;
        $totalScore += $score;

        $level = $this->getPerformanceLevel($score);
        $distribution[$level]['count']++;

        $teamResults[] = [
          'employee_name' => $evalPerson->person->nombre_completo ?? 'N/A',
          'score' => $score,
          'level' => $level
        ];
      }
    }

    $averageScore = $totalEvaluated > 0 ? $totalScore / $totalEvaluated : 0;

    // Calcular porcentajes
    foreach ($distribution as $level => &$data) {
      $data['percentage'] = $totalEvaluated > 0 ? ($data['count'] / $totalEvaluated) * 100 : 0;
    }

    return [
      'average_score' => $averageScore,
      'total_evaluated' => $totalEvaluated,
      'team_results' => $teamResults,
      'performance_distribution' => $distribution,
      'stats' => [
        'Mejor Calificación' => $totalEvaluated > 0 ? number_format(max(array_column($teamResults, 'score')), 1) . '%' : 'N/A',
        'Menor Calificación' => $totalEvaluated > 0 ? number_format(min(array_column($teamResults, 'score')), 1) . '%' : 'N/A',
        'Tasa de Completitud' => number_format(($totalEvaluated / count($evaluationPersons)) * 100, 1) . '%',
        'Total Evaluados' => $totalEvaluated . ' de ' . count($evaluationPersons)
      ]
    ];
  }

  /**
   * Determina el nivel de desempeño según la calificación
   */
  private function getPerformanceLevel(float $score): string
  {
    if ($score >= 90) return 'Excelente';
    if ($score >= 70) return 'Bueno';
    if ($score >= 60) return 'Regular';
    return 'Deficiente';
  }

  /**
   * Envía correo de cierre a un líder
   */
  private function sendClosedEmailToLeader(Evaluation $evaluation, Worker $leader, array $leaderData, array $teamSummary): array
  {
    try {
      $emailConfig = [
        'to' => $leader->email2,
        'subject' => 'Evaluación de Desempeño Finalizada - Resumen de Resultados',
        'template' => 'emails.evaluation-closed',
        'data' => [
          'title' => 'Evaluación de Desempeño Finalizada',
          'subtitle' => 'Resumen de resultados de su equipo',
          'badge' => 'Evaluación Cerrada',
          'leader_name' => $leader->nombre_completo,
          'evaluation_name' => $evaluation->name,
          'start_date' => Carbon::parse($evaluation->start_date)->format('d/m/Y'),
          'end_date' => Carbon::parse($evaluation->end_date)->format('d/m/Y'),
          'closed_date' => now()->format('d/m/Y'),
          'team_count' => $leaderData['team_count'],
          'total_evaluated' => $teamSummary['total_evaluated'],
          'team_summary' => $teamSummary,
          'team_results' => $teamSummary['team_results'],
          'top_competences' => [
            'Trabajo en equipo',
            'Cumplimiento de objetivos',
            'Responsabilidad'
          ],
          'areas_improvement' => [
            'Comunicación efectiva',
            'Gestión del tiempo'
          ],
          'evaluation_url' => config('app.frontend_url'),
          'additional_notes' => 'Los resultados detallados están disponibles en la plataforma para su revisión.',
          'date' => now()->format('d/m/Y H:i'),
          'company_name' => 'Grupo Pakatnamu',
          'contact_info' => 'rrhh@grupopakatnamu.com'
        ]
      ];

      $sent = $this->emailService->queue($emailConfig); // Usando cola de trabajo

      return [
        'leader_id' => $leader->id,
        'leader_name' => $leader->nombre_completo,
        'leader_email' => $leader->email2,
        'team_count' => $leaderData['team_count'],
        'average_score' => $teamSummary['average_score'],
        'sent' => $sent,
        'evaluation_id' => $evaluation->id,
        'evaluation_name' => $evaluation->name
      ];

    } catch (\Exception $e) {
      return [
        'leader_id' => $leader->id,
        'leader_name' => $leader->nombre_completo,
        'leader_email' => $leader->email2,
        'sent' => false,
        'error' => $e->getMessage(),
        'evaluation_id' => $evaluation->id,
        'evaluation_name' => $evaluation->name
      ];
    }
  }

  /**
   * Envía un resumen de evaluaciones pendientes a RRHH
   */
  public function sendSummaryToHR(int $evaluationId = null): array
  {
    try {
      $evaluations = $evaluationId
        ? Evaluation::where('id', $evaluationId)->get()
        : $this->getActiveEvaluations();

      $summaryData = [];
      $totalPendingEvaluations = 0;
      $totalLeadersWithPending = 0;

      foreach ($evaluations as $evaluation) {
        $leadersWithPending = $this->getLeadersWithPendingEvaluations($evaluation);
        $pendingCount = array_sum(array_column($leadersWithPending, 'pending_evaluations'));

        $summaryData[] = [
          'evaluation_name' => $evaluation->name,
          'end_date' => Carbon::parse($evaluation->end_date)->format('d/m/Y'),
          'leaders_with_pending' => count($leadersWithPending),
          'total_pending_evaluations' => $pendingCount,
          'completion_percentage' => $evaluation->completion_percentage
        ];

        $totalPendingEvaluations += $pendingCount;
        $totalLeadersWithPending += count($leadersWithPending);
      }

      $emailConfig = [
        'to' => 'rrhh@grupopakatnamu.com',
        'subject' => 'Resumen de Evaluaciones Pendientes - Sistema de Desempeño',
        'template' => 'emails.report',
        'data' => [
          'report_title' => 'Resumen de Evaluaciones de Desempeño Pendientes',
          'report_subtitle' => 'Estado actual de las evaluaciones en progreso',
          'generated_date' => now()->format('d/m/Y H:i:s'),
          'recipient_name' => 'Equipo de Recursos Humanos',
          'introduction' => 'Este reporte muestra el estado actual de las evaluaciones de desempeño pendientes por líder.',
          'summary_data' => [
            'Total de Evaluaciones Monitoreadas' => count($evaluations),
            'Líderes con Evaluaciones Pendientes' => $totalLeadersWithPending,
            'Total de Evaluaciones Pendientes' => $totalPendingEvaluations,
            'Generado el' => now()->format('d/m/Y H:i')
          ],
          'table_headers' => ['Evaluación', 'Fecha Límite', 'Líderes Pendientes', 'Eval. Pendientes', 'Completitud'],
          'table_data' => array_map(function ($data) {
            return [
              $data['evaluation_name'],
              $data['end_date'],
              $data['leaders_with_pending'],
              $data['total_pending_evaluations'],
              number_format($data['completion_percentage'], 1) . '%'
            ];
          }, $summaryData),
          'conclusions' => $totalPendingEvaluations > 0
            ? "Se han identificado {$totalPendingEvaluations} evaluaciones pendientes que requieren seguimiento."
            : "Todas las evaluaciones están al día.",
          'company_name' => 'Grupo Pakana',
          'contact_info' => 'sistema@grupopakatnamu.com'
        ]
      ];

      $sent = $this->emailService->queue($emailConfig); // Usando cola de trabajo

      return [
        'success' => $sent,
        'evaluations_monitored' => count($evaluations),
        'total_pending' => $totalPendingEvaluations,
        'leaders_with_pending' => $totalLeadersWithPending
      ];

    } catch (\Exception $e) {
      // Log::error('Error sending HR summary: ' . $e->getMessage());

      return [
        'success' => false,
        'error' => $e->getMessage()
      ];
    }
  }
}
