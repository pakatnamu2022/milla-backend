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
        Schema::table('ap_vehicle_inspection', function (Blueprint $table) {
            $table->string('photo_optional_3_url')->nullable()->after('photo_optional_2_url');
            $table->string('photo_optional_4_url')->nullable()->after('photo_optional_3_url');
            $table->string('photo_optional_5_url')->nullable()->after('photo_optional_4_url');
            $table->string('photo_optional_6_url')->nullable()->after('photo_optional_5_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ap_vehicle_inspection', function (Blueprint $table) {
            $table->dropColumn([
                'photo_optional_3_url',
                'photo_optional_4_url',
                'photo_optional_5_url',
                'photo_optional_6_url',
            ]);
        });
    }
};
