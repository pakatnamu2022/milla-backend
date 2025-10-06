<?php

namespace Database\Seeders\gp\views;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfigVistaParentIdSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    // Si tiene idHijo, lo tomamos como prioridad (nivel más bajo)
    DB::statement("UPDATE config_vista SET parent_id = idHijo WHERE idHijo IS NOT NULL");

    // Luego los que tienen idSubPadre
    DB::statement("UPDATE config_vista SET parent_id = idSubPadre WHERE idSubPadre IS NOT NULL AND parent_id IS NULL");

    // Finalmente los que tienen idPadre
    DB::statement("UPDATE config_vista SET parent_id = idPadre WHERE idPadre IS NOT NULL AND parent_id IS NULL");

  }
}
