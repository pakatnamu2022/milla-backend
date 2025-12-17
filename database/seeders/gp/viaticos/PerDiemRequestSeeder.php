<?php

namespace Database\Seeders\gp\viaticos;

use App\Models\gp\gestionhumana\personal\Worker;
use App\Models\gp\gestionhumana\viaticos\PerDiemPolicy;
use App\Models\gp\gestionhumana\viaticos\PerDiemRequest;
use App\Models\gp\gestionhumana\viaticos\PerDiemCategory;
use App\Models\gp\gestionsistema\Company;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PerDiemRequestSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $currentPolicy = PerDiemPolicy::where('is_current', true)->first();
    $gerentes = PerDiemCategory::where('name', 'Gerentes')->first();
    $colaboradores = PerDiemCategory::where('name', 'Colaboradores')->first();

    $company = Company::first();
    $users = Worker::limit(10)->get();

    if (!$currentPolicy || $users->isEmpty() || !$company) {
      return;
    }

    $destinations = ['Lima', 'Arequipa', 'Cusco', 'Trujillo', 'Piura', 'Chiclayo', 'Cajamarca'];
    $purposes = [
      'Reunión con cliente importante',
      'Capacitación técnica',
      'Auditoría de sucursal',
      'Supervisión de proyecto',
      'Negociación de contrato',
      'Visita a obra',
      'Conferencia anual',
      'Presentación de propuesta',
    ];

    $requests = [
      // 3 DRAFT
      [
        'code' => 'PDR-2025-0001',
        'employee_id' => $users[0]->id,
        'per_diem_category_id' => $gerentes->id,
        'destination' => 'Lima',
        'start_date' => Carbon::now()->addDays(15),
        'end_date' => Carbon::now()->addDays(17),
        'days_count' => 3,
        'purpose' => 'Reunión estratégica con inversionistas',
        'status' => 'draft',
        'total_budget' => 0,
      ],
      [
        'code' => 'PDR-2025-0002',
        'employee_id' => $users[1]->id,
        'per_diem_category_id' => $colaboradores->id,
        'destination' => 'Arequipa',
        'start_date' => Carbon::now()->addDays(20),
        'end_date' => Carbon::now()->addDays(22),
        'days_count' => 3,
        'purpose' => 'Capacitación en sistema ERP',
        'status' => 'draft',
        'total_budget' => 0,
      ],
      [
        'code' => 'PDR-2025-0003',
        'employee_id' => $users[2]->id,
        'per_diem_category_id' => $colaboradores->id,
        'destination' => 'Cusco',
        'start_date' => Carbon::now()->addDays(25),
        'end_date' => Carbon::now()->addDays(26),
        'days_count' => 2,
        'purpose' => 'Soporte técnico en sede Cusco',
        'status' => 'draft',
        'total_budget' => 0,
      ],

      // 3 PENDING_MANAGER
      [
        'code' => 'PDR-2025-0004',
        'employee_id' => $users[3]->id,
        'per_diem_category_id' => $colaboradores->id,
        'destination' => 'Trujillo',
        'start_date' => Carbon::now()->addDays(10),
        'end_date' => Carbon::now()->addDays(12),
        'days_count' => 3,
        'purpose' => 'Auditoría de sucursal Trujillo',
        'status' => 'pending_manager',
        'total_budget' => 840.00,
      ],
      [
        'code' => 'PDR-2025-0005',
        'employee_id' => $users[4]->id,
        'per_diem_category_id' => $gerentes->id,
        'destination' => 'Piura',
        'start_date' => Carbon::now()->addDays(12),
        'end_date' => Carbon::now()->addDays(15),
        'days_count' => 4,
        'purpose' => 'Supervisión de proyecto minero',
        'status' => 'pending_manager',
        'total_budget' => 1380.00,
      ],
      [
        'code' => 'PDR-2025-0006',
        'employee_id' => $users[5]->id,
        'per_diem_category_id' => $colaboradores->id,
        'destination' => 'Chiclayo',
        'start_date' => Carbon::now()->addDays(8),
        'end_date' => Carbon::now()->addDays(10),
        'days_count' => 3,
        'purpose' => 'Visita a cliente corporativo',
        'status' => 'pending_manager',
        'total_budget' => 720.00,
      ],

      // 2 PENDING_HR
      [
        'code' => 'PDR-2025-0007',
        'employee_id' => $users[6]->id,
        'per_diem_category_id' => $gerentes->id,
        'destination' => 'Lima',
        'start_date' => Carbon::now()->addDays(7),
        'end_date' => Carbon::now()->addDays(9),
        'days_count' => 3,
        'purpose' => 'Conferencia de liderazgo empresarial',
        'status' => 'pending_hr',
        'total_budget' => 1260.00,
      ],
      [
        'code' => 'PDR-2025-0008',
        'employee_id' => $users[7]->id,
        'per_diem_category_id' => $colaboradores->id,
        'destination' => 'Cajamarca',
        'start_date' => Carbon::now()->addDays(6),
        'end_date' => Carbon::now()->addDays(8),
        'days_count' => 3,
        'purpose' => 'Implementación de sistema contable',
        'status' => 'pending_hr',
        'total_budget' => 630.00,
      ],

      // 2 PENDING_GENERAL
      [
        'code' => 'PDR-2025-0009',
        'employee_id' => $users[8]->id,
        'per_diem_category_id' => $gerentes->id,
        'destination' => 'Arequipa',
        'start_date' => Carbon::now()->addDays(5),
        'end_date' => Carbon::now()->addDays(8),
        'days_count' => 4,
        'purpose' => 'Negociación de contrato marco',
        'status' => 'pending_general',
        'total_budget' => 1480.00,
      ],
      [
        'code' => 'PDR-2025-0010',
        'employee_id' => $users[9]->id,
        'per_diem_category_id' => $gerentes->id,
        'destination' => 'Cusco',
        'start_date' => Carbon::now()->addDays(4),
        'end_date' => Carbon::now()->addDays(7),
        'days_count' => 4,
        'purpose' => 'Presentación de propuesta a inversionistas',
        'status' => 'pending_general',
        'total_budget' => 1560.00,
      ],

      // 5 APPROVED
      [
        'code' => 'PDR-2025-0011',
        'employee_id' => $users[0]->id,
        'per_diem_category_id' => $gerentes->id,
        'destination' => 'Lima',
        'start_date' => Carbon::now()->addDays(3),
        'end_date' => Carbon::now()->addDays(5),
        'days_count' => 3,
        'purpose' => 'Reunión con directorio',
        'status' => 'approved',
        'total_budget' => 1260.00,
        'cash_amount' => 500.00,
        'transfer_amount' => 760.00,
        'paid' => true,
        'payment_date' => Carbon::now()->subDays(1),
        'payment_method' => 'mixed',
      ],
      [
        'code' => 'PDR-2025-0012',
        'employee_id' => $users[1]->id,
        'per_diem_category_id' => $colaboradores->id,
        'destination' => 'Trujillo',
        'start_date' => Carbon::now()->addDays(2),
        'end_date' => Carbon::now()->addDays(4),
        'days_count' => 3,
        'purpose' => 'Capacitación de personal',
        'status' => 'approved',
        'total_budget' => 900.00,
        'cash_amount' => 0.00,
        'transfer_amount' => 900.00,
        'paid' => true,
        'payment_date' => Carbon::now()->subDays(2),
        'payment_method' => 'transfer',
      ],
      [
        'code' => 'PDR-2025-0013',
        'employee_id' => $users[2]->id,
        'per_diem_category_id' => $colaboradores->id,
        'destination' => 'Piura',
        'start_date' => Carbon::now()->addDays(1),
        'end_date' => Carbon::now()->addDays(2),
        'days_count' => 2,
        'purpose' => 'Instalación de equipos',
        'status' => 'approved',
        'total_budget' => 610.00,
        'cash_amount' => 610.00,
        'transfer_amount' => 0.00,
        'paid' => true,
        'payment_date' => Carbon::now()->subDays(3),
        'payment_method' => 'cash',
      ],
      [
        'code' => 'PDR-2025-0014',
        'employee_id' => $users[3]->id,
        'per_diem_category_id' => $gerentes->id,
        'destination' => 'Chiclayo',
        'start_date' => Carbon::now()->subDays(2),
        'end_date' => Carbon::now()->addDays(1),
        'days_count' => 4,
        'purpose' => 'Evaluación de proveedores',
        'status' => 'approved',
        'total_budget' => 1080.00,
        'cash_amount' => 400.00,
        'transfer_amount' => 680.00,
        'paid' => true,
        'payment_date' => Carbon::now()->subDays(5),
        'payment_method' => 'mixed',
      ],
      [
        'code' => 'PDR-2025-0015',
        'employee_id' => $users[4]->id,
        'per_diem_category_id' => $gerentes->id,
        'destination' => 'Arequipa',
        'start_date' => Carbon::now()->subDays(1),
        'end_date' => Carbon::now()->addDays(2),
        'days_count' => 4,
        'purpose' => 'Apertura de nueva oficina',
        'status' => 'approved',
        'total_budget' => 1480.00,
        'cash_amount' => 0.00,
        'transfer_amount' => 1480.00,
        'paid' => true,
        'payment_date' => Carbon::now()->subDays(4),
        'payment_method' => 'transfer',
      ],

      // 2 REJECTED
      [
        'code' => 'PDR-2025-0016',
        'employee_id' => $users[5]->id,
        'per_diem_category_id' => $colaboradores->id,
        'destination' => 'Lima',
        'start_date' => Carbon::now()->addDays(10),
        'end_date' => Carbon::now()->addDays(12),
        'days_count' => 3,
        'purpose' => 'Visita no justificada',
        'status' => 'rejected',
        'total_budget' => 960.00,
      ],
      [
        'code' => 'PDR-2025-0017',
        'employee_id' => $users[6]->id,
        'per_diem_category_id' => $colaboradores->id,
        'destination' => 'Cusco',
        'start_date' => Carbon::now()->addDays(15),
        'end_date' => Carbon::now()->addDays(17),
        'days_count' => 3,
        'purpose' => 'Solicitud duplicada',
        'status' => 'rejected',
        'total_budget' => 1170.00,
      ],

      // 2 IN_PROGRESS
      [
        'code' => 'PDR-2025-0018',
        'employee_id' => $users[7]->id,
        'per_diem_category_id' => $gerentes->id,
        'destination' => 'Trujillo',
        'start_date' => Carbon::now()->subDays(3),
        'end_date' => Carbon::now()->addDays(2),
        'days_count' => 6,
        'purpose' => 'Auditoría integral de procesos',
        'status' => 'in_progress',
        'total_budget' => 2040.00,
        'cash_amount' => 800.00,
        'transfer_amount' => 1240.00,
        'paid' => true,
        'payment_date' => Carbon::now()->subDays(7),
        'payment_method' => 'mixed',
      ],
      [
        'code' => 'PDR-2025-0019',
        'employee_id' => $users[8]->id,
        'per_diem_category_id' => $colaboradores->id,
        'destination' => 'Cajamarca',
        'start_date' => Carbon::now()->subDays(2),
        'end_date' => Carbon::now()->addDays(1),
        'days_count' => 4,
        'purpose' => 'Implementación de mejoras',
        'status' => 'in_progress',
        'total_budget' => 840.00,
        'cash_amount' => 840.00,
        'transfer_amount' => 0.00,
        'paid' => true,
        'payment_date' => Carbon::now()->subDays(6),
        'payment_method' => 'cash',
      ],

      // 1 PENDING_SETTLEMENT
      [
        'code' => 'PDR-2025-0020',
        'employee_id' => $users[9]->id,
        'per_diem_category_id' => $gerentes->id,
        'destination' => 'Lima',
        'start_date' => Carbon::now()->subDays(10),
        'end_date' => Carbon::now()->subDays(7),
        'days_count' => 4,
        'purpose' => 'Reunión con accionistas',
        'status' => 'pending_settlement',
        'total_budget' => 1680.00,
        'cash_amount' => 600.00,
        'transfer_amount' => 1080.00,
        'paid' => true,
        'payment_date' => Carbon::now()->subDays(15),
        'payment_method' => 'mixed',
        'total_spent' => 1520.00,
        'balance_to_return' => 160.00,
      ],
    ];

    foreach ($requests as $index => $requestData) {
      // Asegurar que todos los campos requeridos tengan valor
      $defaults = [
        'cash_amount' => 0,
        'transfer_amount' => 0,
        'paid' => false,
        'payment_date' => null,
        'payment_method' => null,
        'settled' => false,
        'settlement_date' => null,
        'total_spent' => 0,
        'balance_to_return' => 0,
        'notes' => null,
      ];

      PerDiemRequest::firstOrCreate(
        ['code' => $requestData['code']],
        array_merge($defaults, $requestData, [
          'company_id' => $company->id,
          'final_result' => '',
        ])
      );
    }
  }
}
