<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agrega el permiso de asociar VIN a vehículos
     */
    public function up(): void
    {
        // Insertar el permiso de asociar VIN
        DB::table('permission')->insert([
            'code' => 'vehicles.associate_vin',
            'name' => 'Asociar VIN a vehículos',
            'description' => 'Permite asociar o modificar el VIN (Vehicle Identification Number) de vehículos',
            'module' => 'vehicles',
            'vista_id' => null, // Se puede configurar manualmente en UI
            'policy_method' => 'associateVin',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar el permiso
        DB::table('permission')
            ->where('code', 'vehicles.associate_vin')
            ->delete();
    }
};
