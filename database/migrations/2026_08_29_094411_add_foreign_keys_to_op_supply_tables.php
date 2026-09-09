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
        Schema::table('op_supply_control', function (Blueprint $table) {
            $table->foreign('photo_id')
                ->references('id')
                ->on('op_supply_photo')
                ->onDelete('set null');
        });

        // Agregar foreign key en op_supply_photo
        Schema::table('op_supply_photo', function (Blueprint $table) {
            $table->foreign('supply_control_id')
                ->references('id')
                ->on('op_supply_control')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('op_supply_control', function (Blueprint $table) {
            $table->dropForeign(['photo_id']);
        });

        Schema::table('op_supply_photo', function (Blueprint $table) {
            $table->dropForeign(['supply_control_id']);
        });
    }
};
