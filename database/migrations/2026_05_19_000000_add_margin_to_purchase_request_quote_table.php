<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_request_quote', function (Blueprint $table) {
            $table->decimal('margin_amount', 12, 4)->nullable()->after('down_payment');
            $table->decimal('margin_pct', 8, 4)->nullable()->after('margin_amount');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_request_quote', function (Blueprint $table) {
            $table->dropColumn(['margin_amount', 'margin_pct']);
        });
    }
};
