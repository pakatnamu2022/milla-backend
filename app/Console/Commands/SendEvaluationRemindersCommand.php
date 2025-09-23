<?php

namespace App\Console\Commands;

use App\Services\EvaluationNotificationService;
use Illuminate\Console\Command;

class SendEvaluationRemindersCommand extends Command
{
    protected $signature = 'evaluation:send-reminders
                          {--evaluation-id= : ID específico de evaluación}
                          {--include-hr-summary : Incluir resumen para RRHH}
                          {--dry-run : Mostrar qué se enviaría sin enviar realmente}';

    protected $description = 'Envía recordatorios de evaluaciones pendientes a los líderes';

    private EvaluationNotificationService $notificationService;

    public function __construct(EvaluationNotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    public function handle()
    {
        $evaluationId = $this->option('evaluation-id');
        $includeHrSummary = $this->option('include-hr-summary');
        $dryRun = $this->option('dry-run');

        $this->info('🚀 Iniciando proceso de recordatorios de evaluaciones...');

        if ($dryRun) {
            $this->warn('⚠️  MODO DRY-RUN: No se enviarán correos reales');
        }

        if ($evaluationId) {
            $this->info("📋 Procesando evaluación específica ID: {$evaluationId}");
        } else {
            $this->info("📋 Procesando todas las evaluaciones activas");
        }

        // Enviar recordatorios a líderes
        if (!$dryRun) {
            $this->line('');
            $this->info('📧 Enviando recordatorios a líderes...');

            $results = $this->notificationService->sendPendingEvaluationReminders($evaluationId);

            if ($results['success']) {
                $this->info("✅ Se enviaron {$results['total_sent']} recordatorios correctamente");

                if ($results['total_sent'] > 0) {
                    $this->displayResults($results['results']);
                }
            } else {
                $this->error("❌ Error al enviar recordatorios: {$results['error']}");
                return 1;
            }
        } else {
            $this->showDryRunResults($evaluationId);
        }

        // Enviar resumen a RRHH si se solicita
        if ($includeHrSummary && !$dryRun) {
            $this->line('');
            $this->info('📊 Enviando resumen a RRHH...');

            $hrResults = $this->notificationService->sendSummaryToHR($evaluationId);

            if ($hrResults['success']) {
                $this->info("✅ Resumen enviado a RRHH correctamente");
                $this->line("   - Evaluaciones monitoreadas: {$hrResults['evaluations_monitored']}");
                $this->line("   - Evaluaciones pendientes: {$hrResults['total_pending']}");
                $this->line("   - Líderes con pendientes: {$hrResults['leaders_with_pending']}");
            } else {
                $this->error("❌ Error al enviar resumen a RRHH: {$hrResults['error']}");
            }
        }

        $this->line('');
        $this->info('🎉 Proceso completado exitosamente');

        return 0;
    }

    private function displayResults(array $results)
    {
        $this->line('');
        $this->info('📋 Detalle de recordatorios enviados:');

        $headers = ['Líder', 'Email', 'Evaluación', 'Pendientes/Total', 'Estado'];
        $rows = [];

        foreach ($results as $result) {
            $status = $result['sent'] ? '✅ Enviado' : '❌ Error';
            $pendingInfo = "{$result['pending_count']}/{$result['total_count']}";

            $rows[] = [
                $result['leader_name'],
                $result['leader_email'],
                $result['evaluation_name'],
                $pendingInfo,
                $status
            ];

            if (!$result['sent'] && isset($result['error'])) {
                $this->error("   Error: {$result['error']}");
            }
        }

        $this->table($headers, $rows);
    }

    private function showDryRunResults($evaluationId)
    {
        $this->line('');
        $this->info('🔍 SIMULACIÓN - Lo que se enviaría:');

        // Aquí simularíamos la lógica sin enviar correos reales
        // Por simplicidad, mostramos un mensaje indicativo
        $this->warn('Esta funcionalidad mostraría qué correos se enviarían sin enviarlos realmente.');
        $this->line('Para implementar completamente, se necesitaría modificar el servicio para soportar modo dry-run.');
    }
}