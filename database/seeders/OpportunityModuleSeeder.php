<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class OpportunityModuleSeeder extends Seeder
{
  /**
   * Seed the application's database.
   * Este seeder ejecuta todos los seeders necesarios para el módulo de oportunidades
   */
  public function run(): void
  {
    $this->command->info('🚀 Iniciando seeders del módulo de Oportunidades...');
    $this->command->newLine();

    // 0. Maestros mínimos para BusinessPartners
    $this->command->info('🔧 Paso 0: Creando maestros mínimos...');
    $this->call(ApCommercialMastersMinimalSeeder::class);
    $this->command->newLine();

    // 1. Seeders de configuración (maestros)
    $this->command->info('📋 Paso 1: Creando tipos y estados...');
    $this->call([
      OpportunityTypeSeeder::class,
      OpportunityStatusSeeder::class,
      ClientStatusSeeder::class,
      ActionTypeSeeder::class,
      ActionContactTypeSeeder::class,
    ]);
    $this->command->newLine();

    // 2. Crear clientes de prueba si no existen
    $this->command->info('👥 Paso 2: Creando clientes de prueba...');
    $this->call(BusinessPartnersTestSeeder::class);
    $this->command->newLine();

    // 3. Seeders de datos
    $this->command->info('📊 Paso 3: Creando oportunidades mockeadas...');
    $this->call(OpportunitySeeder::class);
    $this->command->newLine();

    $this->command->info('📝 Paso 4: Creando acciones mockeadas...');
    $this->call(OpportunityActionSeeder::class);
    $this->command->newLine();

    $this->command->info('✅ ¡Todos los seeders del módulo de Oportunidades se ejecutaron correctamente!');
    $this->command->newLine();
    $this->command->info('📍 Puedes probar los endpoints ahora.');
  }
}
