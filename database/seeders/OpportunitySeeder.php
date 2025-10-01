<?php

namespace Database\Seeders;

use App\Models\ap\ApCommercialMasters;
use App\Models\ap\comercial\BusinessPartners;
use App\Models\ap\comercial\Opportunity;
use App\Models\ap\configuracionComercial\vehiculo\ApFamilies;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OpportunitySeeder extends Seeder
{
  public function run(): void
  {
    // Obtener datos necesarios de la base de datos
    $workers = DB::table('rrhh_persona')->limit(3)->pluck('id')->toArray();
    $clients = BusinessPartners::limit(5)->pluck('id')->toArray();
    $families = ApFamilies::limit(3)->pluck('id')->toArray();

    // Obtener IDs de los masters
    $opportunityTypes = ApCommercialMasters::where('type', 'OPPORTUNITY_TYPE')->pluck('id')->toArray();
    $clientStatuses = ApCommercialMasters::where('type', 'CLIENT_STATUS')->pluck('id')->toArray();
    $opportunityStatuses = ApCommercialMasters::where('type', 'OPPORTUNITY_STATUS')->pluck('id')->toArray();

    // Verificar que tengamos datos
    if (empty($workers)) {
      $this->command->warn('⚠️  No hay workers en rrhh_persona. Usando ID genérico 1.');
      $workers = [1];
    }

    if (empty($clients)) {
      $this->command->warn('⚠️  No hay clientes en business_partners. Usando IDs genéricos.');
      $clients = [1, 2, 3, 4, 5];
    }

    if (empty($families)) {
      $this->command->warn('⚠️  No hay familias en ap_families. Usando IDs genéricos.');
      $families = [1, 2, 3];
    }

    if (empty($opportunityTypes) || empty($clientStatuses) || empty($opportunityStatuses)) {
      $this->command->error('❌ No hay tipos/estados configurados. Ejecuta primero los seeders de OpportunityTypeSeeder, ClientStatusSeeder y OpportunityStatusSeeder.');
      return;
    }

    $this->command->info("📊 Workers disponibles: " . count($workers));
    $this->command->info("📊 Clientes disponibles: " . count($clients));
    $this->command->info("📊 Familias disponibles: " . count($families));

    // Crear oportunidades mockeadas
    $opportunities = [
      [
        'worker_id' => $workers[0],
        'client_id' => $clients[0],
        'family_id' => $families[0],
        'opportunity_type_id' => $opportunityTypes[0],
        'client_status_id' => $clientStatuses[0],
        'opportunity_status_id' => $opportunityStatuses[0], // ABIERTA
        'created_at' => now()->subDays(10),
      ],
      [
        'worker_id' => $workers[0],
        'client_id' => $clients[1] ?? $clients[0],
        'family_id' => $families[1] ?? $families[0],
        'opportunity_type_id' => $opportunityTypes[0],
        'client_status_id' => $clientStatuses[0],
        'opportunity_status_id' => $opportunityStatuses[2] ?? $opportunityStatuses[0], // TEMPLADA
        'created_at' => now()->subDays(8),
      ],
      [
        'worker_id' => $workers[0],
        'client_id' => $clients[2] ?? $clients[0],
        'family_id' => $families[0],
        'opportunity_type_id' => $opportunityTypes[0],
        'client_status_id' => $clientStatuses[1] ?? $clientStatuses[0],
        'opportunity_status_id' => $opportunityStatuses[3] ?? $opportunityStatuses[0], // CALIENTE
        'created_at' => now()->subDays(5),
      ],
      [
        'worker_id' => $workers[1] ?? $workers[0],
        'client_id' => $clients[3] ?? $clients[0],
        'family_id' => $families[2] ?? $families[0],
        'opportunity_type_id' => $opportunityTypes[0],
        'client_status_id' => $clientStatuses[0],
        'opportunity_status_id' => $opportunityStatuses[1] ?? $opportunityStatuses[0], // FRIA
        'created_at' => now()->subDays(15),
      ],
      [
        'worker_id' => $workers[1] ?? $workers[0],
        'client_id' => $clients[4] ?? $clients[0],
        'family_id' => $families[1] ?? $families[0],
        'opportunity_type_id' => $opportunityTypes[0],
        'client_status_id' => $clientStatuses[2] ?? $clientStatuses[0],
        'opportunity_status_id' => $opportunityStatuses[4] ?? $opportunityStatuses[0], // GANADA
        'created_at' => now()->subDays(30),
      ],
    ];

    $created = 0;
    foreach ($opportunities as $opportunity) {
      $result = Opportunity::firstOrCreate(
        [
          'worker_id' => $opportunity['worker_id'],
          'client_id' => $opportunity['client_id'],
        ],
        $opportunity
      );

      if ($result->wasRecentlyCreated) {
        $created++;
      }
    }

    $this->command->info("✅ {$created} oportunidades mockeadas creadas exitosamente!");
  }
}
