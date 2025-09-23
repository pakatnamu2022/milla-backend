<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EmailTestController extends Controller
{
    private EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Prueba del correo de recordatorio de evaluaciones
     */
    public function testEvaluationReminder(Request $request): JsonResponse
    {
        $request->validate([
            'to' => 'required|email'
        ]);

        $config = [
            'to' => $request->to,
            'subject' => 'PRUEBA - Recordatorio de Evaluación de Desempeño',
            'template' => 'emails.evaluation-reminder',
            'data' => [
                'title' => 'Recordatorio de Evaluación de Desempeño',
                'subtitle' => 'Evaluaciones Pendientes de Completar (PRUEBA)',
                'leader_name' => 'Usuario de Prueba',
                'evaluation_name' => 'Evaluación Anual de Desempeño 2024',
                'end_date' => now()->addDays(5)->format('d/m/Y'),
                'pending_count' => 3,
                'total_count' => 5,
                'pending_evaluations' => [
                    [
                        'employee_name' => 'Juan Carlos Pérez',
                        'progress_percentage' => 0
                    ],
                    [
                        'employee_name' => 'María Elena González',
                        'progress_percentage' => 50
                    ],
                    [
                        'employee_name' => 'Carlos Andrés Rodríguez',
                        'progress_percentage' => 25
                    ]
                ],
                'evaluation_url' => 'https://sistema.grupopakatnamu.com/evaluations/1',
                'additional_notes' => 'Esta evaluación vence en 5 días. Por favor complete las evaluaciones pendientes lo antes posible.',
                'send_date' => now()->format('d/m/Y H:i'),
                'company_name' => 'Grupo Pakana',
                'contact_info' => 'rrhh@grupopakatnamu.com'
            ]
        ];

        $result = $this->emailService->send($config);

        return response()->json([
            'success' => $result,
            'message' => $result ? 'Correo de prueba enviado correctamente' : 'Error al enviar correo',
            'to' => $request->to,
            'template' => 'evaluation-reminder',
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Prueba de correo básico con nueva plantilla
     */
    public function testBasicTemplate(Request $request): JsonResponse
    {
        $request->validate([
            'to' => 'required|email',
            'name' => 'nullable|string'
        ]);

        $config = [
            'to' => $request->to,
            'subject' => 'PRUEBA - Sistema de Correos Milla',
            'template' => 'emails.default',
            'data' => [
                'title' => 'Prueba del Sistema de Correos',
                'subtitle' => 'Nueva plantilla implementada',
                'greeting' => 'Hola ' . ($request->name ?? 'Usuario') . ',',
                'message' => 'Este es un correo de prueba del nuevo sistema de plantillas de email. La plantilla base ha sido actualizada con un diseño moderno y responsivo.',
                'alert_type' => 'success',
                'alert_message' => 'El sistema de correos está funcionando correctamente con las nuevas plantillas.',
                'button_text' => 'Acceder al Sistema',
                'button_url' => 'https://sistema.grupopakatnamu.com',
                'button_style' => 'btn-success',
                'additional_info' => 'Esta nueva plantilla incluye:<br>• Diseño responsivo<br>• Múltiples tipos de alertas<br>• Soporte para botones personalizados<br>• Mejor estructura organizacional',
                'footer_text' => 'Este es un correo automático del sistema de pruebas.',
                'company_name' => 'Grupo Pakana',
                'contact_info' => 'soporte@grupopakatnamu.com'
            ]
        ];

        $result = $this->emailService->send($config);

        return response()->json([
            'success' => $result,
            'message' => $result ? 'Correo de prueba enviado correctamente' : 'Error al enviar correo',
            'to' => $request->to,
            'template' => 'default',
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Prueba de correo de notificación con nueva plantilla
     */
    public function testNotificationTemplate(Request $request): JsonResponse
    {
        $request->validate([
            'to' => 'required|email',
            'type' => 'nullable|in:info,success,warning,danger'
        ]);

        $type = $request->type ?? 'info';

        $config = [
            'to' => $request->to,
            'subject' => 'PRUEBA - Notificación del Sistema',
            'template' => 'emails.notification',
            'data' => [
                'title' => 'Notificación del Sistema',
                'subtitle' => 'Prueba de la nueva plantilla de notificaciones',
                'user_name' => 'Usuario de Prueba',
                'main_message' => 'Esta es una prueba de la nueva plantilla de notificaciones con diseño mejorado.',
                'alert_type' => $type,
                'alert_message' => $this->getAlertMessage($type),
                'details' => [
                    'Fecha de envío: ' . now()->format('d/m/Y H:i:s'),
                    'Tipo de alerta: ' . ucfirst($type),
                    'Sistema: Milla Backend',
                    'Versión de plantilla: 2.0'
                ],
                'action_needed' => 'Esta es solo una prueba del sistema. No se requiere ninguna acción por parte del usuario.',
                'button_text' => 'Ver en el Sistema',
                'button_url' => 'https://sistema.grupopakatnamu.com',
                'button_style' => $this->getButtonStyle($type),
                'date' => now()->format('d/m/Y H:i'),
                'company_name' => 'Grupo Pakana',
                'contact_info' => 'soporte@grupopakatnamu.com'
            ]
        ];

        $result = $this->emailService->send($config);

        return response()->json([
            'success' => $result,
            'message' => $result ? 'Correo de notificación enviado correctamente' : 'Error al enviar correo',
            'to' => $request->to,
            'alert_type' => $type,
            'template' => 'notification',
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Estado del servicio de correos
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'service' => 'EmailService',
            'status' => 'active',
            'templates_available' => [
                'emails.default' => 'Plantilla básica con nueva estructura',
                'emails.notification' => 'Plantilla para notificaciones',
                'emails.evaluation-reminder' => 'Plantilla para recordatorios de evaluación',
                'emails.report' => 'Plantilla para reportes'
            ],
            'endpoints' => [
                'POST /api/email/test/evaluation-reminder' => 'Probar correo de recordatorio de evaluación',
                'POST /api/email/test/basic-template' => 'Probar plantilla básica mejorada',
                'POST /api/email/test/notification-template' => 'Probar plantilla de notificación',
                'GET /api/email/test/status' => 'Estado del servicio'
            ],
            'mail_config' => [
                'driver' => config('mail.default'),
                'from_address' => config('mail.from.address'),
                'from_name' => config('mail.from.name')
            ],
            'timestamp' => now()->toISOString()
        ]);
    }

    private function getAlertMessage(string $type): string
    {
        return match($type) {
            'info' => 'Esta es una notificación informativa para mantenerle al día con el sistema.',
            'success' => 'Operación completada exitosamente. Todo funciona correctamente.',
            'warning' => 'Atención: Se ha detectado una situación que requiere su revisión.',
            'danger' => 'Alerta: Se ha detectado un problema crítico que requiere atención inmediata.',
            default => 'Notificación del sistema.'
        };
    }

    private function getButtonStyle(string $type): string
    {
        return match($type) {
            'success' => 'btn-success',
            'warning' => 'btn-warning',
            'danger' => 'btn-danger',
            default => ''
        };
    }
}