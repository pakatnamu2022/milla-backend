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
        Schema::create('driver_status_log', function (Blueprint $table) {
            $table->id();
            $table->integer('driver_id');
            $table->enum('status',['active', 'inactive', 'disconnected']);
            $table->timestamp('changed_at');
            $table->timestamps();

            $table->foreign('driver_id')->references('id')->on('rrhh_persona')->onDelete('cascade');
            $table->index(['driver_id', 'changed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_status_log');
    }
};
