<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discount_coupons', function (Blueprint $table) {
            $table->boolean('has_retention')->default(false)->after('is_negative');
        });
    }

    public function down(): void
    {
        Schema::table('discount_coupons', function (Blueprint $table) {
            $table->dropColumn('has_retention');
        });
    }
};
