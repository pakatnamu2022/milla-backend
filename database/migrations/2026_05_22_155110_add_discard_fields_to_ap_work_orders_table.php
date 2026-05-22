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
        Schema::table('ap_work_orders', function (Blueprint $table) {
            $table->foreignId('discard_reason_id')->nullable()->after('signature_delivery_url')->constrained('ap_masters')->onDelete('set null');
            $table->string('discarded_note')->nullable()->after('discard_reason_id');
            $table->integer('discarded_by')->nullable()->after('discarded_note');
            $table->foreign('discarded_by')->references('id')->on('usr_users')->onDelete('set null');
            $table->dateTime('discarded_at')->nullable()->after('discarded_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ap_work_orders', function (Blueprint $table) {
            $table->dropForeign(['discard_reason_id']);
            $table->dropColumn('discard_reason_id');
            $table->dropColumn('discarded_note');
            $table->dropForeign(['discarded_by']);
            $table->dropColumn('discarded_by');
            $table->dropColumn('discarded_at');
        });
    }
};
