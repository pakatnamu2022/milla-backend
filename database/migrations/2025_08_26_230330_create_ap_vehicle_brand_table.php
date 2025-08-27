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
        Schema::create('ap_vehicle_brand', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', length: 100);
            $table->string('codigo_dyn', length: 100);
            $table->string('nombre', length: 150);
            $table->string('descripcion', length: 255);
            $table->text('logo')->nullable();
            $table->text('logo_min')->nullable();
            $table->boolean('status')->default(true);
            $table->foreignId('grupo_id')
                ->constrained('ap_commercial_masters')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ap_vehicle_brand');
    }
};
