<?php

namespace App\Http\Services\common;

use App\Models\gp\gestionhumana\evaluacion\Evaluation;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPerson;
use App\Models\gp\gestionsistema\Person;
use App\Http\Services\common\EmailService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

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
      // Log::error('Error sending evaluation reminders: ' . $e->getMessage());

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
      $leader = Person::find($leaderId);

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
  private function sendReminderToLeader(Evaluation $evaluation, Person $leader, array $pendingData): array
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

      $sent = $this->emailService->send($emailConfig);

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

      $sent = $this->emailService->send($emailConfig);

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
