<?php

namespace Database\Seeders\gp\viaticos;

use App\Models\gp\gestionhumana\viaticos\PerDiemPolicy;
use Illuminate\Database\Seeder;

class PerDiemPolicySeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $policies = [
      [
        'version' => 'V1-2024',
        'name' => 'Política de Viáticos V1 - 2024',
        'effective_from' => '2024-01-01',
        'effective_to' => '2024-12-31',
        'is_current' => false,
        'document_path' => 'policies/Politica_de_viaticos_V1_2024.pdf',
        'notes' => 'Política cerrada. Vigente durante el año 2024.',
        'created_by' => 1,
      ],
      [
        'version' => 'V2-2025',
        'name' => 'Política de Viáticos V2 - 2025',
        'effective_from' => '2025-01-01',
        'effective_to' => null,
        'is_current' => true,
        'document_path' => 'policies/Politica_de_viaticos_V2_2025.pdf',
        'notes' => 'Política actual vigente. Incluye nuevas tarifas y convenios hoteleros actualizados para el año 2025.',
        'created_by' => 1,
      ],
    ];

    foreach ($policies as $policy) {
      PerDiemPolicy::firstOrCreate(
        ['version' => $policy['version']],
        $policy
      );
    }
  }
}
