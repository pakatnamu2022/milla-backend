<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ap_vehicle_delivery', function (Blueprint $table) {
            $table->text('extraordinary_approval_comment')->nullable()->after('extraordinary_approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('ap_vehicle_delivery', function (Blueprint $table) {
            $table->dropColumn('extraordinary_approval_comment');
        });
    }
};
