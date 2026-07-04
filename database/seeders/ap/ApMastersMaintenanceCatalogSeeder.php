<?php

namespace Database\Seeders\ap;

use App\Models\ap\ApMasters;
use Illuminate\Database\Seeder;

class ApMastersMaintenanceCatalogSeeder extends Seeder
{
  const string TYPE = 'TIPO_OPERACION_CITA';
  const int MIN_AMOUNT = 2000;
  const int MAX_AMOUNT = 300000;
  const int STEP = 1000;

  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $this->normalizeDescriptions();
    $this->createMissingAmounts();
  }

  /**
   * Reformatea las descripciones antiguas "MANTENCION X.XXX" al formato
   * "MANTENIMIENTO DE XXXX". No toca id, code ni el monto: ya están
   * vinculados a órdenes de trabajo existentes.
   */
  private function normalizeDescriptions(): void
  {
    $descriptions = [
      918 => 'MANTENIMIENTO DE 10000',
      919 => 'MANTENIMIENTO DE 100000',
      920 => 'MANTENIMIENTO DE 15000',
      921 => 'MANTENIMIENTO DE 20000',
      922 => 'MANTENIMIENTO DE 25000',
      923 => 'MANTENIMIENTO DE 30000',
      924 => 'MANTENIMIENTO DE 35000',
      925 => 'MANTENIMIENTO DE 40000',
      926 => 'MANTENIMIENTO DE 45000',
      927 => 'MANTENIMIENTO DE 5000',
      928 => 'MANTENIMIENTO DE 50000',
      929 => 'MANTENIMIENTO DE 55000',
      930 => 'MANTENIMIENTO DE 60000',
      931 => 'MANTENIMIENTO DE 65000',
      932 => 'MANTENIMIENTO DE 70000',
      933 => 'MANTENIMIENTO DE 75000',
      934 => 'MANTENIMIENTO DE 80000',
      935 => 'MANTENIMIENTO DE 85000',
      936 => 'MANTENIMIENTO DE 90000',
      937 => 'MANTENIMIENTO DE 95000',
      938 => 'MANTENIMIENTO SUP 120000', // antes "MANTENCION SUP. 120000"
      939 => 'MANTENIMIENTO DE 110000',
      940 => 'MANTENIMIENTO DE 120000',
      985 => 'MANTENIMIENTO DE 7500',
    ];

    $updated = 0;

    foreach ($descriptions as $id => $description) {
      $master = ApMasters::find($id);

      if (!$master) {
        $this->command->warn("ap_masters id={$id} no existe, se omite.");
        continue;
      }

      $master->description = $description;
      $master->save();
      $updated++;
    }

    $this->command->info("Descripciones normalizadas: {$updated} registros.");
  }

  /**
   * Crea los montos de mantenimiento faltantes en la grilla de 1.000 en
   * 1.000 entre 2.000 y 300.000. Los montos que ya existen (bajo
   * cualquier código) no se tocan ni se duplican.
   */
  private function createMissingAmounts(): void
  {
    $existing = ApMasters::ofType(self::TYPE)->get(['code', 'description']);

    $existingAmounts = [];
    $existingCodes = [];

    foreach ($existing as $row) {
      $existingCodes[$row->code] = true;

      if (preg_match('/MANTEN(?:CION|IMIENTO)[A-Z\s\.]*?(\d[\d\.]*)/u', $row->description, $m)) {
        $amount = (int) str_replace('.', '', $m[1]);
        $existingAmounts[$amount] = true;
      }
    }

    $created = 0;

    for ($amount = self::MIN_AMOUNT; $amount <= self::MAX_AMOUNT; $amount += self::STEP) {
      if (isset($existingAmounts[$amount])) {
        continue;
      }

      $code = 'M' . ($amount / 1000);
      if (isset($existingCodes[$code])) {
        // Ya existe un code igual con otro significado (ej. M150 = 1500).
        $code .= 'K';
      }

      ApMasters::create([
        'code' => $code,
        'description' => 'MANTENIMIENTO DE ' . $amount,
        'type' => self::TYPE,
        'status' => true,
      ]);

      $existingCodes[$code] = true;
      $created++;
    }

    $this->command->info("Montos de mantenimiento creados: {$created} registros nuevos.");
  }
}
