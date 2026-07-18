<?php

namespace App\Console\Commands;

use App\Http\Utils\Constants;
use App\Models\ap\comercial\BusinessPartners;
use Illuminate\Console\Command;

class CreateEstablishmentsForNaturalPersons extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'establishments:create-natural-persons
                            {id? : ID del business partner específico}
                            {--all : Procesar todos los business partners de tipo persona natural}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crear establecimientos para business partners de tipo persona natural que no tengan establecimiento';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $id = $this->argument('id');
        $all = $this->option('all');

        if (!$id && !$all) {
            $this->error('Debes proporcionar un ID o usar la opción --all');
            $this->info('Uso:');
            $this->info('  php artisan establishments:create-natural-persons {id}');
            $this->info('  php artisan establishments:create-natural-persons --all');
            return 1;
        }

        if ($id) {
            // Procesar un business partner específico
            return $this->processOne($id);
        } else {
            // Procesar todos
            return $this->processAll();
        }
    }

    /**
     * Procesar un business partner específico
     */
    private function processOne($id)
    {
        $this->info("Procesando business partner ID: {$id}");

        $businessPartner = BusinessPartners::find($id);

        if (!$businessPartner) {
            $this->error("Business partner con ID {$id} no encontrado");
            return 1;
        }

        $this->info("Encontrado: {$businessPartner->full_name} ({$businessPartner->num_doc})");
        $this->info("Tipo persona: " . ($businessPartner->typePerson->name ?? 'N/A'));
        $this->info("Tipo: {$businessPartner->type}");

        if ($businessPartner->type_person_id != Constants::TYPE_NATURAL_PERSON_ID) {
            $this->warn("Este business partner NO es persona natural. Se omite.");
            return 0;
        }

        if ($businessPartner->establishments()->count() > 0) {
            $this->warn("Este business partner YA tiene {$businessPartner->establishments()->count()} establecimiento(s). Se omite.");
            return 0;
        }

        $this->createEstablishment($businessPartner);
        $this->info("✓ Establecimiento creado exitosamente para ID: {$id}");

        return 0;
    }

    /**
     * Procesar todos los business partners de tipo persona natural
     */
    private function processAll()
    {
        $this->info("Buscando business partners de tipo persona natural sin establecimiento...");

        $businessPartners = BusinessPartners::where('type_person_id', Constants::TYPE_NATURAL_PERSON_ID)
            ->whereDoesntHave('establishments')
            ->get();

        $total = $businessPartners->count();

        if ($total === 0) {
            $this->info("No se encontraron business partners que cumplan los criterios");
            return 0;
        }

        $this->info("Se encontraron {$total} business partners para procesar");

        if (!$this->confirm('¿Deseas continuar?')) {
            $this->info('Operación cancelada');
            return 0;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $processed = 0;
        $errors = 0;

        foreach ($businessPartners as $businessPartner) {
            try {
                $this->createEstablishment($businessPartner);
                $processed++;
            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("Error al procesar ID {$businessPartner->id}: {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Procesamiento completado:");
        $this->info("  Total procesados: {$processed}");
        if ($errors > 0) {
            $this->warn("  Errores: {$errors}");
        }

        return 0;
    }

    /**
     * Crear el establecimiento para un business partner
     */
    private function createEstablishment(BusinessPartners $businessPartner)
    {
        $businessPartner->establishments()->create([
            'code' => '0000',
            'type' => 'CENTRAL',
            'activity_economic' => $businessPartner->activityEconomic->name ?? null,
            'address' => $businessPartner->direction ?? '-',
            'full_address' => $businessPartner->direction ?? null,
            'ubigeo' => $businessPartner->district->ubigeo ?? null,
            'business_partner_id' => $businessPartner->id,
        ]);
    }
}
