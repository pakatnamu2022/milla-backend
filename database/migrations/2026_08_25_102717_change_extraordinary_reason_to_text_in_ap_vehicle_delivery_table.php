<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ap_vehicle_delivery', function (Blueprint $table) {
            $table->text('extraordinary_reason')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ap_vehicle_delivery', function (Blueprint $table) {
            $table->string('extraordinary_reason')->nullable()->change();
        });
    }
};