<?php

namespace Database\Seeders\gp\viaticos;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ViaticosMasterSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * Este seeder orquesta todos los seeders del módulo de viáticos
   * Primero trunca todas las tablas (en orden inverso respetando foreign keys)
   * Luego ejecuta todos los seeders en el orden correcto
   * php artisan db:seed --class=Database\Seeders\gp\viaticos\ViaticosMasterSeeder
   */
  public function run(): void
  {
    $this->command->info('🗑️  Truncando tablas de viáticos...');

    // Desactivar foreign key checks
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');

    // Truncar tablas en orden inverso (respetando foreign keys)
    DB::table('gh_per_diem_category')->truncate();
    $this->command->info('   ✓ gh_per_diem_category');

    DB::table('gh_per_diem_expense')->truncate();
    $this->command->info('   ✓ gh_per_diem_expense');

    DB::table('gh_hotel_reservation')->truncate();
    $this->command->info('   ✓ gh_hotel_reservation');

    DB::table('gh_per_diem_approval')->truncate();
    $this->command->info('   ✓ gh_per_diem_approval');

    DB::table('gh_request_budget')->truncate();
    $this->command->info('   ✓ gh_request_budget');

    DB::table('gh_per_diem_request')->truncate();
    $this->command->info('   ✓ gh_per_diem_request');

    DB::table('gh_per_diem_rate')->truncate();
    $this->command->info('   ✓ gh_per_diem_rate');

    DB::table('gh_hotel_agreement')->truncate();
    $this->command->info('   ✓ gh_hotel_agreement');

    DB::table('gh_per_diem_policy')->truncate();
    $this->command->info('   ✓ gh_per_diem_policy');

    // Reactivar foreign key checks
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');

//    dd();

    $this->command->info('');
    $this->command->info('📊 Ejecutando seeders en orden...');
    $this->command->info('');

    // Ejecutar seeders en orden (respetando dependencias)
    $seeders = [
      PerDiemCategorySeeder::class,
      PerDiemCategoriesSeeder::class,
      ExpenseTypesSeeder::class,
      PerDiemPolicySeeder::class,
      HotelAgreementSeeder::class,
      PerDiemRatesSeeder::class,
//      PerDiemRequestSeeder::class,
//      RequestBudgetSeeder::class,
//      PerDiemApprovalSeeder::class,
//      HotelReservationSeeder::class,
//      PerDiemExpenseSeeder::class,
    ];

    foreach ($seeders as $seeder) {
      $seederName = class_basename($seeder);
      $this->command->info("   → Ejecutando {$seederName}...");
      $this->call($seeder);
    }

    $this->command->info('');
    $this->command->info('✅ Seeder de viáticos completado exitosamente!');
    $this->command->info('');
    $this->command->info('📋 Resumen:');
    $this->command->info('   - 2 Políticas de viáticos');
    $this->command->info('   - 10 Convenios hoteleros');
    $this->command->info('   - 20 Solicitudes de viáticos (varios estados)');
    $this->command->info('   - ~60 Presupuestos de solicitudes');
    $this->command->info('   - ~50 Aprobaciones de solicitudes');
    $this->command->info('   - 10 Reservas de hotel');
    $this->command->info('   - ~30-45 Gastos registrados');
  }
}
