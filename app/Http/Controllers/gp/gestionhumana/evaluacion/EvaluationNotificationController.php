<?php

namespace App\Http\Controllers\gp\gestionhumana\evaluacion;

use App\Http\Controllers\Controller;
use App\Http\Services\common\EvaluationNotificationService;
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

      $results = $this->notificationService->sendEvaluationClosed($evaluationId);

      return response()->json([
        'success' => $results['success'],
        'message' => $results['success']
          ? "Se enviaron {$results['total_sent']} resúmenes de cierre"
          : "Error al enviar resúmenes: {$results['error']}",
        'data' => [
          'summaries_sent' => $results['total_sent'],
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
          'company_name' => 'Grupo Pakana',
          'contact_info' => 'rrhh@grupopakatnamu.com'
        ]
      ];

      $emailService = app(\App\Http\Services\common\EmailService::class);
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
}
