<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * `purchase_order_id` was created as NOT NULL, but a support can belong
     * to an Activity OR a Purchase Order (never both), so it must accept NULL.
     * Raw SQL is used because doctrine/dbal (required by Schema::table()->change())
     * is not installed in this project.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE ap_mkt_supports MODIFY purchase_order_id BIGINT UNSIGNED NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE ap_mkt_supports MODIFY purchase_order_id BIGINT UNSIGNED NOT NULL');
    }
};
