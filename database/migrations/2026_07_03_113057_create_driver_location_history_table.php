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
        Schema::create('driver_location_history', function (Blueprint $table) {
            $table->id();
            $table->integer('driver_id');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('accuracy', 10, 2)->nullable();
            $table->decimal('speed', 10, 2)->nullable();
            $table->unsignedBigInteger('battery_level')->nullable();
            $table->timestamp('reported_at')->useCurrent();
            $table->timestamp('created_at')->useCurrent();


            //indices
            $table->index(['driver_id', 'reported_at']);
            $table->index('reported_at');

            $table->foreign('driver_id')
                  ->references('id')
                  ->on('rrhh_persona')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_location_history');
    }
};
