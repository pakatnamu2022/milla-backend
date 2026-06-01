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
        Schema::create('driver_location', function (Blueprint $table) {
            $table->id();
            $table->integer('driver_id');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->float('accuracy')->nullable();
            $table->float('speed')->nullable();
            $table->float('battery_level')->nullable();
            $table->timestamp('reported_at');
            $table->timestamps();

            $table->foreign('driver_id')->references('id')->on('rrhh_persona')->onDelete('cascade');
            $table->unique('driver_id');
            $table->index('reported_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_location');
    }
};
