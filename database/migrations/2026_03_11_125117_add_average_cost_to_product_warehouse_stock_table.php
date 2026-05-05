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
        Schema::table('product_warehouse_stock', function (Blueprint $table) {
            $table->decimal('average_cost', 10, 2)->nullable()->after('cost_price')->comment('Costo promedio ponderado del producto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_warehouse_stock', function (Blueprint $table) {
            $table->dropColumn('average_cost');
        });
    }
};
