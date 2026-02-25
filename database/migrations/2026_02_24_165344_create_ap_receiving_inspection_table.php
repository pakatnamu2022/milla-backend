<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ap_receiving_inspection', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_guide_id')->unique()->constrained('shipping_guides')->onDelete('cascade');
            $table->string('photo_front_url')->nullable();
            $table->string('photo_back_url')->nullable();
            $table->string('photo_left_url')->nullable();
            $table->string('photo_right_url')->nullable();
            $table->text('general_observations')->nullable();
            $table->integer('inspected_by')->nullable();
            $table->foreign('inspected_by')->references('id')->on('usr_users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ap_receiving_inspection');
    }
};
