<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('warehouse', function (Blueprint $table) {
            $table->foreignId('header_warehouse_id')
                ->nullable()
                ->after('type_operation_id')
                ->constrained('header_warehouses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouse', function (Blueprint $table) {
            $table->dropForeign(['header_warehouse_id']);
            $table->dropColumn('header_warehouse_id');
        });
    }
};
