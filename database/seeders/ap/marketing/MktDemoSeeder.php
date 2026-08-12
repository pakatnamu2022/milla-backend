<?php

namespace Database\Seeders\ap\marketing;

use App\Models\ap\marketing\MktActivity;
use App\Models\ap\marketing\MktActivityLocation;
use App\Models\ap\marketing\MktBudget;
use App\Models\ap\marketing\MktBudgetFunding;
use App\Models\ap\marketing\MktKpi;
use App\Models\ap\marketing\MktPlan;
use App\Models\ap\marketing\MktProposal;
use App\Models\ap\marketing\MktPurchaseOrder;
use App\Models\ap\marketing\MktSupport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MktDemoSeeder extends Seeder
{
  public function run(): void
  {
    // IDs de referencia que ya existen en la BD
    $currencyId  = 3;  // PEN - Sol Peruano
    $brandId     = 1;  // CHANGAN
    $supplierId  = 3;  // DEPOSITO PAKATNAMU S.A.C (type=AMBOS)
    $userId      = DB::table('usr_users')->value('id');

    $this->command->info('Limpiando datos demo anteriores...');
    $this->truncate();

    $this->command->info('Creando Plan de Marketing 2026...');

    // ── PLAN ──────────────────────────────────────────────────────────────────
    $plan = MktPlan::create([
      'brand_id'    => $brandId,
      'name'        => 'Plan Marketing CHANGAN 2026',
      'concept'     => 'Incrementar participación de mercado con campañas digitales y eventos presenciales',
      'year'        => 2026,
      'description' => 'Plan anual de marketing para la marca CHANGAN enfocado en lanzamiento del modelo CS55 Plus',
      'status'      => MktPlan::STATUS_ACTIVE,
      'created_by'  => $userId,
      'updated_by'  => $userId,
    ]);

    $this->command->info("  Plan creado: ID {$plan->id}");

    // ── PRESUPUESTO ───────────────────────────────────────────────────────────
    $budget = MktBudget::create([
      'plan_id'          => $plan->id,
      'type'             => MktBudget::TYPE_REGULAR,
      'period_month'     => 8,
      'currency_id'      => $currencyId,
      'amount_estimated' => 50000.00,
      'amount_executed'  => 0.00,
      'status'           => MktBudget::STATUS_APPROVED,
      'notes'            => 'Presupuesto aprobado para Q3 2026',
      'created_by'       => $userId,
      'updated_by'       => $userId,
    ]);

    // Fondeo del presupuesto
    MktBudgetFunding::create([
      'budget_id'   => $budget->id,
      'source'      => 'brand',
      'currency_id' => $currencyId,
      'amount'      => 35000.00,
      'notes'       => 'Transferencia aprobada por casa matriz CHANGAN',
    ]);
    MktBudgetFunding::create([
      'budget_id'   => $budget->id,
      'source'      => 'AP',
      'currency_id' => $currencyId,
      'amount'      => 15000.00,
      'notes'       => 'Aporte concesionario local',
    ]);

    $this->command->info("  Presupuesto creado: ID {$budget->id} (S/ 50,000)");

    // ── ACTIVIDAD 1: Evento presencial (flujo completo hasta soporte) ─────────
    $this->command->info('  Creando Actividad 1: Evento de lanzamiento (flujo completo)...');

    $activity1 = MktActivity::create([
      'budget_id'        => $budget->id,
      'activity_type'    => 'evento',
      'name'             => 'Lanzamiento CS55 Plus - Agosto 2026',
      'description'      => 'Evento presencial de lanzamiento del nuevo modelo CS55 Plus con pruebas de manejo',
      'objective'        => 'Generar 50 leads calificados y 5 reservas durante el evento',
      'responsible'      => 'Gerencia de Marketing',
      'channel'          => 'presencial',
      'start_date'       => '2026-08-20',
      'end_date'         => '2026-08-20',
      'currency_id'      => $currencyId,
      'estimated_amount' => 18000.00,
      'supplier_id'      => $supplierId,
      'status'           => MktActivity::STATUS_EXECUTED,
      'notes'            => 'Evento realizado con éxito, pendiente de rendición de cuentas',
      'created_by'       => $userId,
      'updated_by'       => $userId,
    ]);

    // Locación de la actividad
    MktActivityLocation::create([
      'activity_id'   => $activity1->id,
      'sede_id'       => 1,
      'location_name' => 'Patio Automotriz Norte - Lima',
      'currency_id'   => $currencyId,
      'amount'        => 500.00,
      'notes'         => 'Alquiler de espacio para el evento de lanzamiento',
    ]);

    // Propuesta aprobada → genera OC
    $proposal1 = MktProposal::create([
      'activity_id' => $activity1->id,
      'supplier_id' => $supplierId,
      'currency_id' => $currencyId,
      'amount'      => 17500.00,
      'description' => 'Propuesta integral: montaje, catering, animador y seguridad para evento de lanzamiento',
      'status'      => MktProposal::STATUS_APPROVED,
      'notes'       => 'Incluye IGV. Propuesta más competitiva de 3 recibidas.',
      'reviewed_by' => $userId,
      'reviewed_at' => now(),
      'created_by'  => $userId,
      'updated_by'  => $userId,
    ]);

    // Orden de compra generada de la propuesta → ya soportada
    $oc1 = MktPurchaseOrder::create([
      'activity_id'  => $activity1->id,
      'proposal_id'  => $proposal1->id,
      'supplier_id'  => $supplierId,
      'currency_id'  => $currencyId,
      'number'       => 'OC-MKT-2026-001',
      'amount'       => 17500.00,
      'issue_date'   => '2026-08-10',
      'status'       => MktPurchaseOrder::STATUS_SUPPORTED,
      'sent_at'      => now()->subDays(10),
      'notes'        => 'OC enviada al proveedor el 10/08/2026',
      'created_by'   => $userId,
      'updated_by'   => $userId,
    ]);

    // Soportes de la OC (comprobantes)
    MktSupport::create([
      'activity_id'       => $activity1->id,
      'purchase_order_id' => $oc1->id,
      'type'              => MktSupport::TYPE_INVOICE,
      'document_series'   => 'F001',
      'document_number'   => '00001234',
      'issue_date'        => '2026-08-20',
      'supplier_id'       => $supplierId,
      'currency_id'       => $currencyId,
      'amount'            => 12500.00,
      'notes'             => 'Factura por servicio de montaje y catering',
      'created_by'        => $userId,
    ]);
    MktSupport::create([
      'activity_id'       => $activity1->id,
      'purchase_order_id' => $oc1->id,
      'type'              => MktSupport::TYPE_RECEIPT,
      'document_series'   => 'B001',
      'document_number'   => '00005678',
      'issue_date'        => '2026-08-20',
      'supplier_id'       => $supplierId,
      'currency_id'       => $currencyId,
      'amount'            => 5000.00,
      'notes'             => 'Boleta por animador y seguridad',
      'created_by'        => $userId,
    ]);

    // KPI del evento
    MktKpi::create([
      'activity_id'  => $activity1->id,
      'period_month' => 8,
      'period_year'  => 2026,
      'leads'        => 63,
      'sales'        => 7,
      'investment'   => 17500.00,
      'currency_id'  => $currencyId,
      'notes'        => 'Resultado real post-evento: 63 leads, 7 reservas confirmadas',
      'created_by'   => $userId,
    ]);

    $this->command->info("    Actividad 1 completa: propuesta aprobada → OC soportada → 2 comprobantes → KPI");

    // ── ACTIVIDAD 2: Campaña digital (en progreso, propuesta pendiente) ───────
    $this->command->info('  Creando Actividad 2: Campaña digital (en progreso)...');

    $activity2 = MktActivity::create([
      'budget_id'        => $budget->id,
      'activity_type'    => 'digital',
      'name'             => 'Campaña Digital Facebook/Instagram - Septiembre 2026',
      'description'      => 'Pauta en redes sociales para captación de leads digital para el CS55 Plus',
      'objective'        => 'Obtener 200 leads digitales con costo por lead < S/ 25',
      'responsible'      => 'Coordinación Digital',
      'channel'          => 'digital',
      'start_date'       => '2026-09-01',
      'end_date'         => '2026-09-30',
      'currency_id'      => $currencyId,
      'estimated_amount' => 8000.00,
      'supplier_id'      => $supplierId,
      'status'           => MktActivity::STATUS_IN_PROGRESS,
      'notes'            => 'Campaña activa, pendiente OC y rendición',
      'created_by'       => $userId,
      'updated_by'       => $userId,
    ]);

    // Propuesta pendiente de aprobación (aquí el frontend debe mostrar botones Aprobar/Rechazar)
    MktProposal::create([
      'activity_id' => $activity2->id,
      'supplier_id' => $supplierId,
      'currency_id' => $currencyId,
      'amount'      => 7800.00,
      'description' => 'Pauta digital Meta Ads: configuración de campañas, segmentación y gestión mensual',
      'status'      => MktProposal::STATUS_PENDING,
      'notes'       => 'Pendiente de revisión y aprobación por gerencia',
      'created_by'  => $userId,
      'updated_by'  => $userId,
    ]);

    $this->command->info("    Actividad 2: propuesta en STATUS_PENDING → lista para aprobar/rechazar desde frontend");

    // ── ACTIVIDAD 3: Planned (sin propuesta aún, solo planificada) ────────────
    $this->command->info('  Creando Actividad 3: Participación feria (planificada)...');

    MktActivity::create([
      'budget_id'        => $budget->id,
      'activity_type'    => 'feria',
      'name'             => 'Feria Automotriz Lima 2026 - Octubre',
      'description'      => 'Participación con stand propio en la feria automotriz anual de Lima',
      'objective'        => 'Visibilidad de marca y captación de 100+ leads',
      'responsible'      => 'Gerencia Comercial',
      'channel'          => 'presencial',
      'start_date'       => '2026-10-15',
      'end_date'         => '2026-10-18',
      'currency_id'      => $currencyId,
      'estimated_amount' => 24000.00,
      'status'           => MktActivity::STATUS_PLANNED,
      'notes'            => 'Aún en fase de planificación. Pendiente cotizaciones.',
      'created_by'       => $userId,
      'updated_by'       => $userId,
    ]);

    $this->command->info("    Actividad 3: STATUS_PLANNED, sin propuestas → flujo desde cero");

    $this->command->info('');
    $this->command->info('✅ Demo creado correctamente. Resumen:');
    $this->command->table(
      ['Recurso', 'ID', 'Estado'],
      [
        ['Plan',                $plan->id,      MktPlan::STATUS_ACTIVE],
        ['Budget',              $budget->id,    MktBudget::STATUS_APPROVED],
        ['Activity 1 (evento)', $activity1->id, MktActivity::STATUS_EXECUTED],
        ['Activity 2 (digital)',$activity2->id, MktActivity::STATUS_IN_PROGRESS],
        ['Activity 3 (feria)',  3,              MktActivity::STATUS_PLANNED],
        ['Proposal aprobada',   $proposal1->id, MktProposal::STATUS_APPROVED],
        ['Proposal pendiente',  2,              MktProposal::STATUS_PENDING],
        ['OC soportada',        $oc1->id,       MktPurchaseOrder::STATUS_SUPPORTED],
        ['Soportes',            '2',            'receipt + invoice'],
        ['KPI',                 '1',            'leads=63 / ventas=7'],
      ]
    );
  }

  private function truncate(): void
  {
    DB::statement('SET FOREIGN_KEY_CHECKS=0');
    DB::table('ap_mkt_kpis')->truncate();
    DB::table('ap_mkt_supports')->truncate();
    DB::table('ap_mkt_purchase_orders')->truncate();
    DB::table('ap_mkt_proposals')->truncate();
    DB::table('ap_mkt_activity_locations')->truncate();
    DB::table('ap_mkt_activities')->truncate();
    DB::table('ap_mkt_budget_fundings')->truncate();
    DB::table('ap_mkt_budgets')->truncate();
    DB::table('ap_mkt_plans')->truncate();
    DB::statement('SET FOREIGN_KEY_CHECKS=1');
  }
}
