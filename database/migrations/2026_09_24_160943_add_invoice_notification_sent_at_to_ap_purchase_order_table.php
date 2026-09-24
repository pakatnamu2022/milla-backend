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
            $table->timestamp('invoice_notification_sent_at')->nullable()->after('invoice_sync_attempts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ap_purchase_order', function (Blueprint $table) {
            $table->dropColumn('invoice_notification_sent_at');
        });
    }
};
