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
        Schema::table('details_approved_accessories_quote', function (Blueprint $table) {
            $table->decimal('additional_price', 12, 4)->default(0)->after('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('details_approved_accessories_quote', function (Blueprint $table) {
            $table->dropColumn('additional_price');
        });
    }
};
