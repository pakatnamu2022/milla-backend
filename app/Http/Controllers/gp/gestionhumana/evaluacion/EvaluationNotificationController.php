<?php

namespace App\Http\Controllers\gp\gestionhumana\evaluacion;

use App\Http\Controllers\Controller;
use App\Http\Services\common\EmailService;
use App\Http\Services\common\EvaluationNotificationService;
use App\Models\gp\gestionhumana\evaluacion\Evaluation;
use App\Models\gp\gestionhumana\evaluacion\EvaluationPerson;
use App\Models\gp\gestionhumana\personal\Worker;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EvaluationNotificationController extends Controller
{
  private EvaluationNotificationService $notificationService;

  public function __construct(EvaluationNotificationService $notificationService)
  {
    $this->notificationService = $notificationService;
  }

  /**
   * Envía recordatorios de evaluaciones pendientes a líderes
   */
  public function sendReminders(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'evaluation_id' => 'nullable|integer|exists:gh_evaluation,id',
      'include_hr_summary' => 'nullable|boolean'
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Datos de entrada inválidos',
        'errors' => $validator->errors()
      ], 422);
    }

    try {
      $evaluationId = $request->input('evaluation_id');
      $includeHrSummary = $request->input('include_hr_summary', false);

      // Enviar recordatorios a líderes
      $results = $this->notificationService->sendPendingEvaluationReminders($evaluationId);

      $response = [
        'success' => $results['success'],
        'message' => $results['success']
          ? "Se enviaron {$results['total_sent']} recordatorios correctamente"
          : "Error al enviar recordatorios: {$results['error']}",
        'data' => [
          'reminders_sent' => $results['total_sent'],
          'results' => $results['results']
        ]
      ];

      // Enviar resumen a RRHH si se solicita
      if ($includeHrSummary && $results['success']) {
        $hrResults = $this->notificationService->sendSummaryToHR($evaluationId);

        $response['data']['hr_summary'] = [
          'sent' => $hrResults['success'],
          'evaluations_monitored' => $hrResults['evaluations_monitored'] ?? 0,
          'total_pending' => $hrResults['total_pending'] ?? 0,
          'leaders_with_pending' => $hrResults['leaders_with_pending'] ?? 0
        ];

        if (!$hrResults['success']) {
          $response['data']['hr_summary']['error'] = $hrResults['error'];
        }
      }

      return response()->json($response, $results['success'] ? 200 : 500);

    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error interno del servidor',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * Envía resumen de evaluaciones pendientes a RRHH
   */
  public function sendHrSummary(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'evaluation_id' => 'nullable|integer|exists:gh_evaluation,id'
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Datos de entrada inválidos',
        'errors' => $validator->errors()
      ], 422);
    }

    try {
      $evaluationId = $request->input('evaluation_id');

      $results = $this->notificationService->sendSummaryToHR($evaluationId);

      return response()->json([
        'success' => $results['success'],
        'message' => $results['success']
          ? 'Resumen enviado a RRHH correctamente'
          : 'Error al enviar resumen a RRHH',
        'data' => [
          'evaluations_monitored' => $results['evaluations_monitored'] ?? 0,
          'total_pending' => $results['total_pending'] ?? 0,
          'leaders_with_pending' => $results['leaders_with_pending'] ?? 0
        ],
        'error' => $results['error'] ?? null
      ], $results['success'] ? 200 : 500);

    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error interno del servidor',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * Obtiene el estado de evaluaciones pendientes sin enviar correos
   */
  public function getPendingStatus(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'evaluation_id' => 'nullable|integer|exists:gh_evaluation,id'
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Datos de entrada inválidos',
        'errors' => $validator->errors()
      ], 422);
    }

    try {
      $evaluationId = $request->input('evaluation_id');

      // Usar el servicio para obtener datos sin enviar correos
      // Esto requeriría una modificación del servicio para solo obtener datos
      // Por ahora, devolvemos un mensaje indicativo

      return response()->json([
        'success' => true,
        'message' => 'Funcionalidad en desarrollo',
        'data' => [
          'note' => 'Esta funcionalidad obtendría el estado de evaluaciones pendientes sin enviar correos'
        ]
      ]);

    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error interno del servidor',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * Envía notificación de apertura de evaluación (Correo 1)
   */
  public function sendEvaluationOpened(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'evaluation_id' => 'required|integer|exists:gh_evaluation,id'
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Datos de entrada inválidos',
        'errors' => $validator->errors()
      ], 422);
    }

    try {
      $evaluationId = $request->input('evaluation_id');

      $results = $this->notificationService->sendEvaluationOpened($evaluationId);

      return response()->json([
        'success' => $results['success'],
        'message' => $results['success']
          ? "Se enviaron {$results['total_sent']} notificaciones de apertura"
          : "Error al enviar notificaciones: {$results['error']}",
        'data' => [
          'notifications_sent' => $results['total_sent'],
          'results' => $results['results']
        ]
      ], $results['success'] ? 200 : 500);

    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error interno del servidor',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * Envía resumen de cierre de evaluación (Correo 3)
   */
  public function sendEvaluationClosed(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'evaluation_id' => 'required|integer|exists:gh_evaluation,id'
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Datos de entrada inválidos',
        'errors' => $validator->errors()
      ], 422);
    }

    try {
      $evaluationId = $request->input('evaluation_id');

      $closedResults = $this->notificationService->sendEvaluationClosed($evaluationId);
      $resultsAvailable = $this->notificationService->sendResultsAvailable($evaluationId);

      return response()->json([
        'success' => $closedResults['success'],
        'message' => $closedResults['success']
          ? "Se enviaron {$closedResults['total_sent']} resúmenes de cierre"
          : "Error al enviar resúmenes: {$closedResults['error']}",
        'data' => [
          'leaders' => [
            'sent' => $closedResults['total_sent'],
            'results' => $closedResults['results'],
          ],
          'persons' => [
            'sent' => $resultsAvailable['total_sent'],
            'results' => $resultsAvailable['results'],
          ],
        ]
      ], $closedResults['success'] ? 200 : 500);

    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error interno del servidor',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * Envía recordatorio a un líder específico
   */
  public function sendReminderToLeader(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'evaluation_id' => 'required|integer|exists:gh_evaluation,id',
      'leader_id' => 'required|integer|exists:rrhh_persona,id'
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Datos de entrada inválidos',
        'errors' => $validator->errors()
      ], 422);
    }

    try {
      $evaluationId = $request->input('evaluation_id');
      $leaderId = $request->input('leader_id');

      $result = $this->notificationService->sendReminderToSingleLeader($evaluationId, $leaderId);

      return response()->json([
        'success' => $result['success'],
        'message' => $result['success']
          ? 'Recordatorio enviado correctamente al líder'
          : 'Error al enviar recordatorio: ' . ($result['error'] ?? 'Error desconocido'),
        'data' => $result['result']
      ], $result['success'] ? 200 : 400);

    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error interno del servidor',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * Prueba el servicio de correos con un correo específico
   */
  public function testReminder(Request $request): JsonResponse
  {
    $validator = Validator::make($request->all(), [
      'email' => 'required|email',
      'evaluation_id' => 'required|integer|exists:gh_evaluation,id'
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Datos de entrada inválidos',
        'errors' => $validator->errors()
      ], 422);
    }

    try {
      // Crear datos de prueba para el correo
      $testData = [
        'to' => $request->input('email'),
        'subject' => 'PRUEBA - Recordatorio de Evaluación de Desempeño',
        'template' => 'emails.evaluation-reminder',
        'data' => [
          'title' => 'Recordatorio de Evaluación de Desempeño (PRUEBA)',
          'subtitle' => 'Evaluaciones Pendientes de Completar',
          'leader_name' => 'Usuario de Prueba',
          'evaluation_name' => 'Evaluación de Prueba 2024',
          'end_date' => now()->addDays(7)->format('d/m/Y'),
          'pending_count' => 3,
          'total_count' => 5,
          'pending_evaluations' => [
            [
              'employee_name' => 'Juan Pérez',
              'progress_percentage' => 0
            ],
            [
              'employee_name' => 'María González',
              'progress_percentage' => 50
            ],
            [
              'employee_name' => 'Carlos Rodríguez',
              'progress_percentage' => 0
            ]
          ],
          'evaluation_url' => config('app.frontend_url') . '/evaluations/' . $request->input('evaluation_id'),
          'additional_notes' => 'Esta es una prueba del sistema de notificaciones de evaluaciones.',
          'send_date' => now()->format('d/m/Y H:i'),
          'company_name' => 'Grupo Pakatnamu',
          'contact_info' => 'rrhh@grupopakatnamu.com'
        ]
      ];

      $emailService = app(EmailService::class);
      $sent = $emailService->send($testData);

      return response()->json([
        'success' => $sent,
        'message' => $sent ? 'Correo de prueba enviado correctamente' : 'Error al enviar correo de prueba',
        'data' => [
          'email' => $request->input('email'),
          'evaluation_id' => $request->input('evaluation_id'),
          'sent_at' => $sent ? now()->toISOString() : null
        ]
      ], $sent ? 200 : 500);

    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'Error al enviar correo de prueba',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * Vista temporal para previsualizar el correo de resultados disponibles
   */
  public function previewResultsAvailable(Request $request, int $chiefId)
  {
    if (!app()->environment(['local', 'testing'])) {
      abort(404);
    }

    $validator = Validator::make(array_merge($request->all(), ['chief_id' => $chiefId]), [
      'chief_id' => 'required|integer|exists:rrhh_persona,id',
      'evaluation_id' => 'nullable|integer|exists:gh_evaluation,id',
    ]);

    if ($validator->fails()) {
      abort(404);
    }

    $query = EvaluationPerson::query()
      ->with(['person', 'evaluation.cycle'])
      ->where('chief_id', $chiefId);

    if ($evaluationId = $request->integer('evaluation_id')) {
      $query->where('evaluation_id', $evaluationId);
    } else {
      $query->orderByDesc('evaluation_id')->orderByDesc('id');
    }

    $evaluationPerson = $query->first();

    if (!$evaluationPerson || !$evaluationPerson->person || !$evaluationPerson->evaluation) {
      abort(404, 'No se encontró información suficiente para previsualizar el correo.');
    }

    $evaluation = $evaluationPerson->evaluation;
    $cycle = $evaluation->cycle;
    $frontendUrl = rtrim((string)config('app.frontend_url', config('app.url', url('/'))), '/');

    return response()->view('emails.evaluation-results-available', [
      'badge' => 'Resultado disponible',
      'person_name' => $evaluationPerson->person->nombre_completo,
      'evaluation_name' => $evaluation->name,
      'start_date' => Carbon::parse($cycle?->start_date ?? $evaluation->start_date ?? now())->format('d/m/Y'),
      'end_date' => Carbon::parse($cycle?->end_date ?? $evaluation->end_date ?? now())->format('d/m/Y'),
      'results_url' => $frontendUrl . '/perfil/mi-desempeno',
      'date' => now()->format('d/m/Y H:i'),
      'company_name' => 'Grupo Pakatnamu',
      'contact_info' => 'rrhh@grupopakatnamu.com',
      'logo' => 'https://namu-storage.nyc3.digitaloceanspaces.com/general/sian.svg',
    ]);
  }

  /**
   * Vista temporal para previsualizar el correo de recordatorio de evaluaciones pendientes
   */
  public function previewEvaluationReminder(Request $request, int $chiefId)
  {
    if (!app()->environment(['local', 'testing'])) {
      abort(404);
    }

    $validator = Validator::make(array_merge($request->all(), ['chief_id' => $chiefId]), [
      'chief_id' => 'required|integer|exists:rrhh_persona,id',
      'evaluation_id' => 'nullable|integer|exists:gh_evaluation,id',
    ]);

    if ($validator->fails()) {
      abort(404);
    }

    // Obtener la evaluación
    $evaluationQuery = Evaluation::query();

    if ($evaluationId = $request->integer('evaluation_id')) {
      $evaluationQuery->where('id', $evaluationId);
    } else {
      $evaluationQuery->orderByDesc('id');
    }

    $evaluation = $evaluationQuery->first();

    if (!$evaluation) {
      abort(404, 'No se encontró evaluación.');
    }

    // Obtener el líder
    $leader = Worker::find($chiefId);

    if (!$leader) {
      abort(404, 'Líder no encontrado.');
    }

    // Obtener evaluaciones pendientes del líder para esta evaluación
    $evaluationPersons = EvaluationPerson::query()
      ->with(['person'])
      ->where('evaluation_id', $evaluation->id)
      ->where('chief_id', $chiefId)
      ->get();

    if ($evaluationPersons->isEmpty()) {
      abort(404, 'El líder no tiene evaluaciones asignadas.');
    }

    // Calcular estadísticas y detalles de evaluaciones pendientes
    $totalEvaluations = 0;
    $pendingCount = 0;
    $pendingDetails = [];
    $seenPersonIds = [];

    foreach ($evaluationPersons as $evalPerson) {
      // Evitar duplicados
      if (in_array($evalPerson->person_id, $seenPersonIds)) {
        continue;
      }
      $seenPersonIds[] = $evalPerson->person_id;
      $totalEvaluations++;

      // Determinar progreso
      $isCompleted = $evalPerson->wasEvaluated == 1;
      if ($isCompleted) {
        $progressPercentage = 100.0;
      } elseif ($evalPerson->result !== null && $evalPerson->result > 0) {
        $progressPercentage = 50.0;
      } else {
        $progressPercentage = 0.0;
      }

      // Agregar a pendientes si no está completada
      if (!$isCompleted) {
        $pendingCount++;
        $pendingDetails[] = [
          'employee_name' => $evalPerson->person->nombre_completo ?? 'N/A',
          'progress_percentage' => (int)$progressPercentage,
          'employee_id' => $evalPerson->person_id
        ];
      }
    }

    // Calcular notas adicionales
    $daysUntilEnd = Carbon::now()->diffInDays(Carbon::parse($evaluation->end_date), false);
    if ($daysUntilEnd <= 3) {
      $additionalNotes = "Esta evaluación vence en {$daysUntilEnd} días. Por favor complete las evaluaciones pendientes lo antes posible.";
    } elseif ($daysUntilEnd <= 7) {
      $additionalNotes = "Esta evaluación vence en {$daysUntilEnd} días. Recomendamos completar las evaluaciones pronto.";
    } else {
      $additionalNotes = "Recuerde completar todas las evaluaciones antes de la fecha límite para asegurar un proceso completo.";
    }

    $frontendUrl = rtrim((string)config('app.frontend_url', config('app.url', url('/'))), '/');

    return response()->view('emails.evaluation-reminder', [
      'leader_name' => $leader->nombre_completo,
      'evaluation_name' => $evaluation->name,
      'start_date' => Carbon::parse($evaluation->start_date),
      'end_date' => Carbon::parse($evaluation->end_date),
      'pending_count' => $pendingCount,
      'total_count' => $totalEvaluations,
      'pending_evaluations' => $pendingDetails,
      'evaluation_url' => $frontendUrl . '/perfil/equipo',
      'additional_notes' => $additionalNotes,
      'send_date' => now()->format('d/m/Y H:i'),
      'company_name' => 'Grupo Pakatnamu',
      'contact_info' => 'rrhh@grupopakatnamu.com',
      'logo' => 'https://namu-storage.nyc3.digitaloceanspaces.com/general/sian.svg',
    ]);
  }

  /**
   * Vista temporal para previsualizar el correo de evaluación abierta (Correo: opened)
   */
  public function previewEvaluationOpened(Request $request, int $chiefId)
  {
    if (!app()->environment(['local', 'testing'])) {
      abort(404);
    }

    $validator = Validator::make(array_merge($request->all(), ['chief_id' => $chiefId]), [
      'chief_id' => 'required|integer|exists:rrhh_persona,id',
      'evaluation_id' => 'nullable|integer|exists:gh_evaluation,id',
    ]);

    if ($validator->fails()) {
      abort(404);
    }

    // Obtener la evaluación
    $evaluationQuery = Evaluation::query();

    if ($evaluationId = $request->integer('evaluation_id')) {
      $evaluationQuery->where('id', $evaluationId);
    } else {
      $evaluationQuery->orderByDesc('id');
    }

    $evaluation = $evaluationQuery->first();

    if (!$evaluation) {
      abort(404, 'No se encontró evaluación.');
    }

    // Obtener el líder
    $leader = Worker::find($chiefId);

    if (!$leader) {
      abort(404, 'Líder no encontrado.');
    }

    // Obtener miembros del equipo asignados a este líder para la evaluación
    $evaluationPersons = EvaluationPerson::query()
      ->with(['person'])
      ->where('evaluation_id', $evaluation->id)
      ->where('chief_id', $chiefId)
      ->get();

    if ($evaluationPersons->isEmpty()) {
      abort(404, 'El líder no tiene evaluaciones asignadas.');
    }

    $seen = [];
    $team_members = [];
    foreach ($evaluationPersons as $ep) {
      if (in_array($ep->person_id, $seen, true)) {
        continue;
      }
      $seen[] = $ep->person_id;

      $rawPosition = $ep->person->cargo ?? ($ep->person->position ?? null);
      $position = 'N/A';

      if (is_string($rawPosition) && trim($rawPosition) !== '') {
        $decoded = json_decode($rawPosition, true);
        if (is_array($decoded)) {
          $position = (string)($decoded['name'] ?? $decoded['cargo'] ?? $decoded['descripcion'] ?? 'N/A');
        } else {
          $position = $rawPosition;
        }
      } elseif (is_array($rawPosition)) {
        $position = (string)($rawPosition['name'] ?? $rawPosition['cargo'] ?? $rawPosition['descripcion'] ?? 'N/A');
      } elseif (is_object($rawPosition)) {
        $position = (string)($rawPosition->name ?? $rawPosition->cargo ?? $rawPosition->descripcion ?? 'N/A');
      }

      $position = trim(strip_tags($position));
      if ($position === '' || $position === '{}' || str_starts_with($position, '{"id"')) {
        $position = 'N/A';
      }

      $team_members[] = [
        'name' => $ep->person->nombre_completo ?? 'N/A',
        'position' => $position
      ];
    }

    $frontendUrl = rtrim((string)config('app.frontend_url', config('app.url', url('/'))), '/');

    return response()->view('emails.evaluation-opened', [
      'leader_name' => $leader->nombre_completo,
      'evaluation_name' => $evaluation->name,
      'start_date' => Carbon::parse($evaluation->start_date)->format('d/m/Y'),
      'end_date' => Carbon::parse($evaluation->end_date)->format('d/m/Y'),
      'team_members' => $team_members,
      'team_count' => count($team_members),
      'has_objectives' => true,
      'has_competences' => true,
      'evaluation_url' => $frontendUrl . '/perfil/equipo',
      'additional_notes' => 'Recuerda revisar el alcance y objetivos compartidos con el equipo.',
      'send_date' => now()->format('d/m/Y H:i'),
      'company_name' => 'Grupo Pakatnamu',
      'contact_info' => 'rrhh@grupopakatnamu.com',
      'logo' => 'https://namu-storage.nyc3.digitaloceanspaces.com/general/sian.svg',
    ]);
  }

  /**
   * Vista temporal para previsualizar el correo de evaluación finalizada (Correo: closed)
   */
  public function previewEvaluationClosed(Request $request, int $chiefId)
  {
    if (!app()->environment(['local', 'testing'])) {
      abort(404);
    }

    $validator = Validator::make(array_merge($request->all(), ['chief_id' => $chiefId]), [
      'chief_id' => 'required|integer|exists:rrhh_persona,id',
      'evaluation_id' => 'nullable|integer|exists:gh_evaluation,id',
    ]);

    if ($validator->fails()) {
      abort(404);
    }

    // Obtener la evaluación
    $evaluationQuery = Evaluation::query();

    if ($evaluationId = $request->integer('evaluation_id')) {
      $evaluationQuery->where('id', $evaluationId);
    } else {
      $evaluationQuery->orderByDesc('id');
    }

    $evaluation = $evaluationQuery->first();

    if (!$evaluation) {
      abort(404, 'No se encontró evaluación.');
    }

    // Obtener el líder
    $leader = Worker::find($chiefId);

    if (!$leader) {
      abort(404, 'Líder no encontrado.');
    }

    $evaluationPersons = EvaluationPerson::query()
      ->with(['person'])
      ->where('evaluation_id', $evaluation->id)
      ->where('chief_id', $chiefId)
      ->get();

    if ($evaluationPersons->isEmpty()) {
      abort(404, 'El líder no tiene evaluaciones asignadas.');
    }

    // Calcular resumen simple
    $total = 0;
    $sumScores = 0.0;
    $evaluatedCount = 0;
    $distribution = [
      'Excelente' => ['count' => 0, 'percentage' => 0],
      'Bueno' => ['count' => 0, 'percentage' => 0],
      'Regular' => ['count' => 0, 'percentage' => 0],
      'Deficiente' => ['count' => 0, 'percentage' => 0],
    ];

    foreach ($evaluationPersons as $ep) {
      if (in_array($ep->person_id, [])) {
        // placeholder to allow future dedupe if needed
      }
      $total++;
      $score = is_numeric($ep->result) ? (float)$ep->result : 0.0;
      $sumScores += $score;
      if ($score > 0) {
        $evaluatedCount++;
      }

      // Categorizar según score (suponiendo escala 0-100)
      if ($score >= 90) {
        $distribution['Excelente']['count']++;
      } elseif ($score >= 70) {
        $distribution['Bueno']['count']++;
      } elseif ($score >= 60) {
        $distribution['Regular']['count']++;
      } else {
        $distribution['Deficiente']['count']++;
      }
    }

    // Calcular porcentajes
    foreach ($distribution as $level => &$data) {
      $data['percentage'] = $total > 0 ? ($data['count'] / $total) * 100 : 0;
    }

    $avg = $total > 0 ? ($sumScores / max(1, $total)) : 0.0;

    $team_summary = [
      'average_score' => round($avg, 1),
      'stats' => [
        'Evaluados' => "$evaluatedCount",
        'Total' => "$total",
      ],
      'performance_distribution' => array_map(function ($k, $v) {
        return [$k => ['count' => $v['count'], 'percentage' => $v['percentage']]];
      }, array_keys($distribution), $distribution),
    ];

    // Normalize performance_distribution to simple array of arrays for blade
    $perf = [];
    foreach ($distribution as $level => $d) {
      $perf[$level] = ['count' => $d['count'], 'percentage' => $d['percentage']];
    }

    $frontendUrl = rtrim((string)config('app.frontend_url', config('app.url', url('/'))), '/');

    return response()->view('emails.evaluation-closed', [
      'leader_name' => $leader->nombre_completo,
      'evaluation_name' => $evaluation->name,
      'start_date' => Carbon::parse($evaluation->start_date)->format('d/m/Y'),
      'end_date' => Carbon::parse($evaluation->end_date)->format('d/m/Y'),
      'team_count' => $total,
      'total_evaluated' => $evaluatedCount,
      'team_summary' => [
        'average_score' => round($avg, 1),
        'stats' => ['Evaluados' => $evaluatedCount, 'Total' => $total],
        'performance_distribution' => $perf,
      ],
      'top_competences' => ['Colaboración', 'Comunicación', 'Resolución de problemas'],
      'areas_improvement' => ['Gestión del tiempo', 'Documentación'],
      'evaluation_url' => $frontendUrl . '/perfil/equipo',
      'additional_notes' => 'Resumen generado para previsualización.',
      'send_date' => now()->format('d/m/Y H:i'),
      'company_name' => 'Grupo Pakatnamu',
      'contact_info' => 'rrhh@grupopakatnamu.com',
      'logo' => 'https://namu-storage.nyc3.digitaloceanspaces.com/general/sian.svg',
    ]);
  }
}
