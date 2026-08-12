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
        Schema::create('ap_vehicle_delivery_reschedule_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_delivery_id');
            $table->timestamp('previous_date');
            $table->timestamp('new_date');
            $table->unsignedBigInteger('rescheduled_by');
            $table->boolean('is_extraordinary')->default(false);
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->foreign('vehicle_delivery_id')->references('id')->on('ap_vehicle_delivery');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ap_vehicle_delivery_reschedule_logs');
    }
};
