<?php

namespace Database\Seeders\tp\comercial;

use Illuminate\Database\Seeder;
use App\Models\tp\comercial\DriverLocationConfiguration;

class MonitoreoConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configs = [
            ['key' => 'location_interval_minutes', 'value' => 2, 'description' => 'Intervalo de envío de ubicación en minutos'],
            ['key' => 'active_threshold_minutes', 'value' => 5, 'description' => 'Máximo tiempo para considerar activo (minutos)'],
            ['key' => 'inactive_threshold_minutes', 'value' => 30, 'description' => 'Máximo tiempo para considerar inactivo (minutos)'],
        ];

        foreach ($configs as $config) {
            DriverLocationConfiguration::updateOrCreate(
                ['key' => $config['key']],
                ['value' => $config['value'], 'description' => $config['description']]
            );
        }
    }
}
