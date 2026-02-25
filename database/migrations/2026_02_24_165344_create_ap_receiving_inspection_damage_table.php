<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ap_receiving_inspection_damages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receiving_inspection_id')->constrained('ap_receiving_inspection')->onDelete('cascade');
            $table->string('damage_type', 100);
            $table->string('x_coordinate')->nullable();
            $table->string('y_coordinate')->nullable();
            $table->text('description')->nullable();
            $table->string('photo_url')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ap_receiving_inspection_damages');
    }
};
