<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
 public function up()
    {
        DB::statement('ALTER TABLE op_despacho_item MODIFY COLUMN precio_unit DECIMAL(12,3)');
        DB::statement('ALTER TABLE op_despacho_item MODIFY COLUMN total DECIMAL(12,3)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE op_despacho_item MODIFY COLUMN precio_unit DECIMAL(12,2)');
        DB::statement('ALTER TABLE op_despacho_item MODIFY COLUMN total DECIMAL(12,2)');
    }
};
