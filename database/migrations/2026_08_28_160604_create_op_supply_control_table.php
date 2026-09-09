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
        Schema::create('op_supply_control', function (Blueprint $table) {
            $table->id();
            $table->integer('vehicle_id');
            $table->integer('driver_id');
            $table->unsignedBigInteger('supplier_id');
            $table->decimal('mileage', 10, 2);
            $table->decimal('gallons', 10, 3);
            $table->unsignedBigInteger('photo_id')->nullable();
            $table->tinyInteger('is_base')->default(1);
            $table->dateTime('recorded_at');
            $table->integer('created_by')->nullable();
            $table->timestamps();
            $table->integer('status_deleted')->default(1);

            $table->index('vehicle_id');
            $table->index('driver_id');
            $table->index('supplier_id');
            $table->index('photo_id');
            $table->index('recorded_at');

            $table->foreign('vehicle_id')
                ->references('id')
                ->on('op_vehiculo');

            $table->foreign('driver_id')
                ->references('id')
                ->on('rrhh_persona');

            $table->foreign('supplier_id')
                ->references('id')
                ->on('op_supplier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('op_supply_control');
    }
};
