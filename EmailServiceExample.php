<?php

/**
 * Ejemplos de uso del EmailService
 *
 * Este archivo contiene ejemplos de cómo usar el servicio de email genérico
 * Para usar estos ejemplos, cópialos en tu controlador o donde necesites enviar emails
 */

namespace App\Http\Controllers;

use App\Services\EmailService;
use Illuminate\Http\Request;

class EmailExampleController extends Controller
{
    private EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Ejemplo 1: Email básico con template por defecto
     */
    public function sendBasicEmail()
    {
        $config = [
            'to' => 'usuario@example.com',
            'subject' => 'Bienvenido al sistema',
            'template' => 'emails.default',
            'data' => [
                'title' => 'Bienvenido',
                'greeting' => 'Hola Juan,',
                'message' => 'Te damos la bienvenida a nuestro sistema. Esperamos que tengas una excelente experiencia.',
                'button_text' => 'Ir al Dashboard',
                'button_url' => 'https://example.com/dashboard',
                'footer_text' => 'Gracias por unirte a nosotros.',
                'company_name' => 'Mi Empresa'
            ]
        ];

        $result = $this->emailService->send($config);

        return response()->json(['sent' => $result]);
    }

    /**
     * Ejemplo 2: Email de notificación
     */
    public function sendNotification()
    {
        $config = [
            'to' => 'admin@example.com',
            'subject' => 'Nueva actividad en el sistema',
            'template' => 'emails.notification',
            'data' => [
                'title' => 'Alerta del Sistema',
                'subtitle' => 'Actividad importante detectada',
                'user_name' => 'Administrador',
                'main_message' => 'Se ha detectado una nueva actividad que requiere tu atención.',
                'alert_type' => 'info', // info, success, warning
                'alert_message' => 'Un nuevo usuario se ha registrado en el sistema.',
                'details' => [
                    'Usuario: Juan Pérez',
                    'Email: juan@example.com',
                    'Fecha de registro: ' . now()->format('d/m/Y H:i'),
                    'IP: 192.168.1.100'
                ],
                'action_needed' => 'Por favor, revisa la información del nuevo usuario y activa su cuenta si es necesario.',
                'date' => now()->format('d/m/Y H:i')
            ]
        ];

        $result = $this->emailService->send($config);

        return response()->json(['sent' => $result]);
    }

    /**
     * Ejemplo 3: Email de reporte con datos y archivos adjuntos
     */
    public function sendReport()
    {
        $config = [
            'to' => ['gerente@example.com', 'supervisor@example.com'],
            'cc' => 'rrhh@example.com',
            'subject' => 'Reporte Mensual de Actividades',
            'template' => 'emails.report',
            'data' => [
                'report_title' => 'Reporte de Ventas - Enero 2024',
                'report_subtitle' => 'Análisis de rendimiento mensual',
                'generated_date' => now()->format('d/m/Y H:i:s'),
                'recipient_name' => 'Equipo Directivo',
                'introduction' => 'Adjunto encontrarán el reporte mensual de actividades con los principales indicadores de rendimiento.',
                'summary_data' => [
                    'Total de Ventas' => '$125,450.00',
                    'Nuevos Clientes' => '47',
                    'Productos Vendidos' => '1,234',
                    'Satisfacción Cliente' => '94.5%'
                ],
                'table_headers' => ['Producto', 'Ventas', 'Cantidad', 'Ingresos'],
                'table_data' => [
                    ['Producto A', '50', '150', '$15,000'],
                    ['Producto B', '75', '200', '$25,000'],
                    ['Producto C', '120', '300', '$45,000']
                ],
                'important_notes' => [
                    'Las ventas han aumentado un 15% respecto al mes anterior',
                    'Se requiere reponer stock del Producto C'
                ],
                'conclusions' => 'El mes ha sido muy exitoso, superando las expectativas establecidas.',
                'next_steps' => [
                    'Reabastecer inventario de productos más vendidos',
                    'Contactar nuevos proveedores para Producto C',
                    'Preparar campaña de marketing para febrero'
                ],
                'company_name' => 'Mi Empresa S.A.',
                'contact_info' => 'reportes@example.com'
            ],
            'attachments' => [
                // Archivo simple (solo ruta)
                '/path/to/reporte.pdf',

                // Archivo con configuración personalizada
                [
                    'path' => '/path/to/datos.xlsx',
                    'name' => 'Datos_Enero_2024.xlsx',
                    'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                ]
            ]
        ];

        $result = $this->emailService->send($config);

        return response()->json(['sent' => $result]);
    }

    /**
     * Ejemplo 4: Email en cola (para envío masivo o procesos pesados)
     */
    public function queueEmail()
    {
        $config = [
            'to' => 'usuario@example.com',
            'subject' => 'Newsletter Semanal',
            'template' => 'emails.default',
            'data' => [
                'title' => 'Newsletter',
                'greeting' => 'Hola suscriptor,',
                'message' => 'Aquí tienes las noticias más importantes de esta semana.',
                'company_name' => 'Mi Empresa'
            ]
        ];

        // Enviar en cola en lugar de inmediatamente
        $result = $this->emailService->queue($config);

        return response()->json(['queued' => $result]);
    }

    /**
     * Ejemplo 5: Email con múltiples destinatarios
     */
    public function sendToMultiple()
    {
        $config = [
            'to' => [
                'usuario1@example.com',
                'usuario2@example.com',
                'usuario3@example.com'
            ],
            'bcc' => 'archivo@example.com', // Copia oculta
            'subject' => 'Comunicado Importante',
            'template' => 'emails.notification',
            'data' => [
                'title' => 'Comunicado General',
                'main_message' => 'Les informamos sobre los cambios en nuestras políticas.',
                'alert_type' => 'warning',
                'alert_message' => 'Estas políticas entrarán en vigor el próximo lunes.'
            ]
        ];

        $result = $this->emailService->send($config);

        return response()->json(['sent' => $result]);
    }
}

/**
 * CONFIGURACIÓN REQUERIDA EN .env:
 *
 * MAIL_MAILER=smtp
 * MAIL_HOST=smtp.gmail.com
 * MAIL_PORT=587
 * MAIL_USERNAME=tu-email@gmail.com
 * MAIL_PASSWORD=tu-app-password
 * MAIL_ENCRYPTION=tls
 * MAIL_FROM_ADDRESS=tu-email@gmail.com
 * MAIL_FROM_NAME="${APP_NAME}"
 *
 * Para Gmail, necesitarás:
 * 1. Activar la verificación en 2 pasos
 * 2. Generar una "Contraseña de aplicación"
 * 3. Usar esa contraseña en MAIL_PASSWORD
 */

/**
 * ESTRUCTURA DE CONFIGURACIÓN:
 *
 * $config = [
 *     'to' => 'email@example.com' | ['email1@example.com', 'email2@example.com'],
 *     'cc' => 'email@example.com' | ['email1@example.com'], // Opcional
 *     'bcc' => 'email@example.com' | ['email1@example.com'], // Opcional
 *     'subject' => 'Asunto del email',
 *     'template' => 'emails.template-name', // Ruta del blade
 *     'data' => [
 *         // Variables que se pasarán al template blade
 *         'variable1' => 'valor1',
 *         'variable2' => 'valor2'
 *     ],
 *     'attachments' => [ // Opcional
 *         '/ruta/archivo.pdf', // Archivo simple
 *         [
 *             'path' => '/ruta/archivo.xlsx',
 *             'name' => 'nombre-personalizado.xlsx', // Opcional
 *             'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' // Opcional
 *         ]
 *     ]
 * ];
 */