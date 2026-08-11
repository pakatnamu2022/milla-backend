<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConceptDiscountBondDescriptionSeeder extends Seeder
{
  // 861 = BONO MARCA, 862 = BONO FINANCIERO
  private array $descriptionsByParent = [
    862 => ['MARCA', 'BANCO', 'AP'],
    861 => [
      'BONO NCP',
      'BONO ESPECIAL',
      'PLAN NORTE',
      'BONO ADICIONAL (DERCO)',
      'BONO B2B',
      'BONO DIFERENCIA DE PRECIO',
      'BONO CANALES DIGITALES',
      'BONO PROMOCIONES',
      'BONO PLAN TRADE',
    ],
  ];

  public function run(): void
  {
    $now = now();

    foreach ($this->descriptionsByParent as $parentId => $descriptions) {
      foreach ($descriptions as $description) {
        DB::table('ap_masters')->insert([
          'code'        => strtoupper(str_replace([' ', '(', ')'], ['_', '', ''], $description)),
          'description' => strtoupper($description),
          'type'        => 'CONCEPT_DISCOUNT_BOND_DESCRIPTION',
          'parent_id'   => $parentId,
          'status'      => 1,
          'created_at'  => $now,
          'updated_at'  => $now,
        ]);
      }
    }
  }
}
