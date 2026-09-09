<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            ['name' => 'BASE TP', 'is_active' => 1, 'status_deleted' => 1],
            ['name' => 'AVA CHANCAY', 'is_active' => 1, 'status_deleted' => 1],
            ['name' => 'AVA SANTO TORIBIO', 'is_active' => 1, 'status_deleted' => 1],
            ['name' => 'REPSOL CHANCAY 1', 'is_active' => 1, 'status_deleted' => 1],
            ['name' => 'REPSOL CHANCAY 2', 'is_active' => 1, 'status_deleted' => 1],
            ['name' => 'REPSOL LUCIANO TARAPOTO', 'is_active' => 1, 'status_deleted' => 1],
            ['name' => 'REPSOL MILANO CHICLAYO', 'is_active' => 1, 'status_deleted' => 1],
            ['name' => 'REPSOL PANAM. SUR TRUJILLO', 'is_active' => 1, 'status_deleted' => 1],
        ];

        DB::table('op_supplier')->insert($suppliers);
    }
}
