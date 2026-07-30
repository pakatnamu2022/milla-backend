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
        Schema::table('ap_order_quotations', function (Blueprint $table) {
            $table->boolean('is_sold_at_valid_price')->nullable()->after('status')
                ->comment('Indica si todos los items se vendieron al precio mínimo o superior');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ap_order_quotations', function (Blueprint $table) {
            $table->dropColumn('is_sold_at_valid_price');
        });
    }
};
