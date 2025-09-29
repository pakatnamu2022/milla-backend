<?php

namespace App\Console\Commands;

use App\Http\Services\common\EmailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmailSimpleCommand extends Command
{
    protected $signature = 'test:email-simple {to=hvaldiviezos@automotorespakatnamu.com}';
    protected $description = 'Test simple email sending';

    public function handle()
    {
        $to = $this->argument('to');

        $this->info('Testing email configuration...');
        $this->info('To: ' . $to);

        try {
            // Probar envío básico con Mail facade
            Mail::raw('Este es un correo de prueba desde Milla Backend', function ($message) use ($to) {
                $message->to($to)->subject('Prueba - Milla Backend');
            });

            $this->info('✅ Basic email sent successfully using Mail facade!');

            // Probar con EmailService
            $emailService = app(EmailService::class);

            $config = [
                'to' => $to,
                'subject' => 'Prueba - EmailService Milla Backend',
                'template' => 'emails.evaluation-reminder',
                'data' => [
                    'title' => 'Prueba de Correo de Evaluación',
                    'subtitle' => 'Sistema de correos funcionando',
                    'leader_name' => 'Usuario de Prueba',
                    'evaluation_name' => 'Evaluación de Prueba 2024',
                    'end_date' => now()->addDays(5)->format('d/m/Y'),
                    'pending_count' => 2,
                    'total_count' => 3,
                    'pending_evaluations' => [
                        [
                            'employee_name' => 'Juan Pérez',
                            'progress_percentage' => 0
                        ],
                        [
                            'employee_name' => 'María González',
                            'progress_percentage' => 50
                        ]
                    ],
                    'evaluation_url' => 'https://sistema.test.com/evaluations/1',
                    'additional_notes' => 'Esta es una prueba del sistema de correos.',
                    'send_date' => now()->format('d/m/Y H:i'),
                    'company_name' => 'Grupo Pakana',
                    'contact_info' => 'rrhh@grupopakatnamu.com'
                ]
            ];

            $result = $emailService->send($config);

            if ($result) {
                $this->info('✅ EmailService test sent successfully!');
            } else {
                $this->error('❌ EmailService test failed');
            }

        } catch (\Exception $e) {
            $this->error('❌ Failed to send email: ' . $e->getMessage());
            $this->line('Full error: ' . get_class($e));

            // Mostrar detalles de configuración
            $this->info('Mail configuration:');
            $this->line('MAIL_MAILER: ' . config('mail.default'));
            $this->line('MAIL_HOST: ' . config('mail.mailers.smtp.host'));
            $this->line('MAIL_PORT: ' . config('mail.mailers.smtp.port'));
            $this->line('MAIL_USERNAME: ' . config('mail.mailers.smtp.username'));
            $this->line('MAIL_ENCRYPTION: ' . config('mail.mailers.smtp.encryption'));
            $this->line('MAIL_FROM_ADDRESS: ' . config('mail.from.address'));
        }
    }
}