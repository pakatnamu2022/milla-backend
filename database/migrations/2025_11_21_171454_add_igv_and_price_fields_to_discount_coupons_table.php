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
        Schema::table('discount_coupons', function (Blueprint $table) {
            $table->decimal('igv', 12, 4)->nullable()->after('amount');
            $table->decimal('valor_unitario', 12, 4)->nullable()->after('igv');
            $table->decimal('precio_unitario', 12, 4)->nullable()->after('valor_unitario');
            $table->boolean('is_negative')->default(false)->after('precio_unitario');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discount_coupons', function (Blueprint $table) {
            $table->dropColumn(['igv', 'valor_unitario', 'precio_unitario', 'is_negative']);
        });
    }
};
