<?php

namespace Database\Seeders\ap\commercial;

use App\Models\ap\ApMasters;
use App\Models\ap\comercial\Opportunity;
use App\Models\ap\comercial\OpportunityAction;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class OpportunityActionSeeder extends Seeder
{
  public function run(): void
  {
    // Obtener oportunidades creadas
    $opportunities = Opportunity::take(5)->get();

    if ($opportunities->isEmpty()) {
      $this->command->error('No hay oportunidades en la base de datos. Ejecuta primero OpportunitySeeder.');
      return;
    }

    // Obtener IDs de tipos
    $actionTypes = ApMasters::where('type', 'ACTION_TYPE')->pluck('id')->toArray();
    $actionContactTypes = ApMasters::where('type', 'ACTION_CONTACT_TYPE')->pluck('id')->toArray();

    if (empty($actionTypes) || empty($actionContactTypes)) {
      $this->command->error('No hay tipos de acción configurados. Ejecuta primero ActionTypeSeeder y ActionContactTypeSeeder.');
      return;
    }

    // Descripciones de ejemplo para las acciones
    $descriptions = [
      'Cliente interesado en el modelo SUV, solicita cotización.',
      'Llamada de seguimiento. Cliente consulta sobre formas de pago.',
      'Reunión presencial. Cliente realizó prueba de manejo.',
      'Envío de cotización por email con descuento especial.',
      'Cliente pregunta sobre disponibilidad de colores.',
      'Seguimiento post-cotización. Cliente solicita más tiempo para decidir.',
      'Cliente confirma interés. Agendada visita para firma de contrato.',
      'Llamada de seguimiento. Cliente no contesta.',
      'Cliente solicita información sobre financiamiento.',
      'Reunión cerrada. Cliente firma contrato de compra.',
    ];

    $actionIndex = 0;

    // Crear acciones para cada oportunidad
    foreach ($opportunities as $opportunity) {
      // Número aleatorio de acciones por oportunidad (entre 2 y 5)
      $numActions = rand(2, 5);

      for ($i = 0; $i < $numActions; $i++) {
        // Generar fechas progresivas desde la creación de la oportunidad
        $daysAgo = $numActions - $i - 1; // Para que las fechas sean cronológicas
        $actionDate = Carbon::parse($opportunity->created_at)->addDays($daysAgo);

        // Alternar entre éxito y no éxito
        $result = ($i % 2 == 0) ? true : false;

        OpportunityAction::create([
          'opportunity_id' => $opportunity->id,
          'action_type_id' => $actionTypes[array_rand($actionTypes)],
          'action_contact_type_id' => $actionContactTypes[array_rand($actionContactTypes)],
          'datetime' => $actionDate,
          'description' => $descriptions[$actionIndex % count($descriptions)],
          'result' => $result,
        ]);

        $actionIndex++;
      }
    }

    $this->command->info('✅ Acciones de oportunidades mockeadas creadas exitosamente!');
  }
}
