<?php

namespace Database\Seeders\gp\viaticos;

use App\Models\gp\gestionhumana\viaticos\PerDiemPolicy;
use Illuminate\Database\Seeder;

class PerDiemPoliciesSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    PerDiemPolicy::create([
      'version' => 'V2-2024',
      'name' => 'Política de Viáticos V2 - 2024',
      'effective_from' => '2024-08-15',
      'effective_to' => null,
      'is_current' => true,
      'document_path' => 'policies/Politica_de_viaticos_V2_2024.pdf',
      'notes' => 'Adjustment of per diem rates and inclusion of hotel agreements. Approved on August 15, 2024.',
      'created_by' => 1, // Asume que existe user con ID 1
    ]);
  }
}
