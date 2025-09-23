<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\EmailService;
use App\Mail\TestMail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

class EmailTestController extends Controller
{
    private EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Enviar email básico de prueba
     */
    public function sendBasic(Request $request): JsonResponse
    {
        $request->validate([
            'to' => 'required|email',
            'name' => 'nullable|string',
        ]);

        $config = [
            'to' => $request->to,
            'subject' => 'Email de Prueba - Sistema Milla',
            'template' => 'emails.simple',
            'data' => [
                'name' => $request->name ?? 'Usuario',
                'message' => 'Este es un email de prueba del sistema. Si recibes este mensaje, la configuración está funcionando correctamente.'
            ]
        ];

        $result = $this->emailService->send($config);

        return response()->json([
            'success' => $result,
            'message' => $result ? 'Email enviado correctamente' : 'Error al enviar email',
            'to' => $request->to
        ]);
    }

    /**
     * Enviar notificación de prueba
     */
    public function sendNotification(Request $request): JsonResponse
    {
        $request->validate([
            'to' => 'required|email',
            'type' => 'nullable|in:info,success,warning',
            'title' => 'nullable|string',
            'message' => 'nullable|string',
        ]);

        $type = $request->type ?? 'info';
        $title = $request->title ?? 'Notificación de Prueba';
        $message = $request->message ?? 'Esta es una notificación de prueba del sistema.';

        $config = [
            'to' => $request->to,
            'subject' => 'Notificación - ' . $title,
            'template' => 'emails.notification',
            'data' => [
                'title' => $title,
                'subtitle' => 'Sistema de Notificaciones',
                'user_name' => 'Usuario de Prueba',
                'main_message' => $message,
                'alert_type' => $type,
                'alert_message' => $this->getAlertMessage($type),
                'details' => [
                    'Hora: ' . now()->format('H:i:s'),
                    'Fecha: ' . now()->format('d/m/Y'),
                    'Sistema: Milla Backend',
                    'Tipo: Notificación de prueba'
                ],
                'action_needed' => 'Esta es solo una prueba, no se requiere ninguna acción.',
                'date' => now()->format('d/m/Y H:i')
            ]
        ];

        $result = $this->emailService->send($config);

        return response()->json([
            'success' => $result,
            'message' => $result ? 'Notificación enviada correctamente' : 'Error al enviar notificación',
            'to' => $request->to,
            'type' => $type
        ]);
    }

    /**
     * Enviar reporte de prueba
     */
    public function sendReport(Request $request): JsonResponse
    {
        $request->validate([
            'to' => 'required|email',
        ]);

        $config = [
            'to' => $request->to,
            'subject' => 'Reporte de Prueba - Sistema Milla',
            'template' => 'emails.report',
            'data' => [
                'report_title' => 'Reporte de Actividad del Sistema',
                'report_subtitle' => 'Datos de prueba generados automáticamente',
                'generated_date' => now()->format('d/m/Y H:i:s'),
                'recipient_name' => 'Usuario de Prueba',
                'introduction' => 'Este es un reporte de prueba generado por el sistema para verificar el funcionamiento del servicio de emails.',
                'summary_data' => [
                    'Usuarios Activos' => rand(100, 500),
                    'Emails Enviados' => rand(50, 200),
                    'Tiempo de Actividad' => '99.9%',
                    'Última Actualización' => now()->format('d/m/Y H:i')
                ],
                'table_headers' => ['Módulo', 'Estado', 'Última Verificación', 'Observaciones'],
                'table_data' => [
                    ['Autenticación', 'Activo', now()->subMinutes(5)->format('H:i'), 'Sin problemas'],
                    ['Base de Datos', 'Activo', now()->subMinutes(2)->format('H:i'), 'Funcionando correctamente'],
                    ['Email Service', 'Activo', now()->format('H:i'), 'Prueba en progreso'],
                    ['API REST', 'Activo', now()->subMinutes(1)->format('H:i'), 'Respuesta óptima']
                ],
                'important_notes' => [
                    'Este es un reporte de prueba generado automáticamente',
                    'Los datos mostrados son ficticios y solo para testing'
                ],
                'conclusions' => 'El sistema está funcionando correctamente y todos los módulos están operativos.',
                'next_steps' => [
                    'Continuar con las pruebas de funcionalidad',
                    'Verificar configuración de producción',
                    'Documentar procesos de testing'
                ],
                'company_name' => 'Sistema Milla',
                'contact_info' => 'soporte@sistema-milla.com'
            ]
        ];

        $result = $this->emailService->send($config);

        return response()->json([
            'success' => $result,
            'message' => $result ? 'Reporte enviado correctamente' : 'Error al enviar reporte',
            'to' => $request->to
        ]);
    }

    /**
     * Enviar email con múltiples destinatarios
     */
    public function sendMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'emails' => 'required|array|min:1|max:5',
            'emails.*' => 'email',
            'message' => 'nullable|string',
        ]);

        $message = $request->message ?? 'Este es un email de prueba enviado a múltiples destinatarios.';

        $config = [
            'to' => $request->emails,
            'subject' => 'Email Masivo de Prueba - Sistema Milla',
            'template' => 'emails.default',
            'data' => [
                'title' => 'Email Masivo',
                'greeting' => 'Estimados usuarios,',
                'message' => $message,
                'additional_info' => 'Este email fue enviado a múltiples destinatarios como prueba del sistema de envío masivo.',
                'footer_text' => 'Sistema de emails masivos - Prueba completada.',
                'company_name' => 'Sistema Milla'
            ]
        ];

        $result = $this->emailService->send($config);

        return response()->json([
            'success' => $result,
            'message' => $result ? 'Emails enviados correctamente' : 'Error al enviar emails',
            'recipients_count' => count($request->emails),
            'recipients' => $request->emails
        ]);
    }

    /**
     * Probar cola de emails
     */
    public function testQueue(Request $request): JsonResponse
    {
        $request->validate([
            'to' => 'required|email',
        ]);

        $config = [
            'to' => $request->to,
            'subject' => 'Email en Cola - Sistema Milla',
            'template' => 'emails.notification',
            'data' => [
                'title' => 'Email desde Cola',
                'subtitle' => 'Procesamiento asíncrono',
                'main_message' => 'Este email fue enviado usando el sistema de colas para procesamiento asíncrono.',
                'alert_type' => 'success',
                'alert_message' => 'El sistema de colas está funcionando correctamente.',
                'date' => now()->format('d/m/Y H:i')
            ]
        ];

        $result = $this->emailService->queue($config);

        return response()->json([
            'success' => $result,
            'message' => $result ? 'Email agregado a la cola correctamente' : 'Error al agregar email a la cola',
            'to' => $request->to,
            'note' => 'El email se procesará de forma asíncrona'
        ]);
    }

    /**
     * Obtener estado del servicio de email
     */
    public function status(): JsonResponse
    {
        try {
            $status = [
                'service' => 'EmailService',
                'status' => 'active',
                'mail_driver' => config('mail.default'),
                'from_address' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
                'timestamp' => now()->toISOString(),
                'endpoints' => [
                    'POST /api/email/test/basic' => 'Enviar email básico',
                    'POST /api/email/test/notification' => 'Enviar notificación',
                    'POST /api/email/test/report' => 'Enviar reporte',
                    'POST /api/email/test/multiple' => 'Enviar a múltiples destinatarios',
                    'POST /api/email/test/queue' => 'Probar cola de emails',
                    'GET /api/email/test/status' => 'Estado del servicio'
                ]
            ];

            return response()->json($status);
        } catch (\Exception $e) {
            return response()->json([
                'service' => 'EmailService',
                'status' => 'error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Probar con Mailable nativo de Laravel
     */
    public function testNative(Request $request): JsonResponse
    {
        $request->validate([
            'to' => 'required|email',
        ]);

        try {
            Mail::to($request->to)->send(new TestMail());

            return response()->json([
                'success' => true,
                'message' => 'Email nativo enviado correctamente',
                'to' => $request->to
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'to' => $request->to
            ]);
        }
    }

    private function getAlertMessage(string $type): string
    {
        return match($type) {
            'info' => 'Esta es una notificación informativa del sistema.',
            'success' => 'Operación completada exitosamente.',
            'warning' => 'Atención: Se requiere revisar esta información.',
            default => 'Notificación del sistema.'
        };
    }
}