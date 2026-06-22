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
        Schema::table('ap_purchase_order', function (Blueprint $table) {
            $table->timestamp('invoice_sync_attempted_at')->nullable()->after('invoice_date_dyn');
            $table->unsignedSmallInteger('invoice_sync_attempts')->default(0)->after('invoice_sync_attempted_at');
        });
    }

    public function down(): void
    {
        Schema::table('ap_purchase_order', function (Blueprint $table) {
            $table->dropColumn(['invoice_sync_attempted_at', 'invoice_sync_attempts']);
        });
    }
};
