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
            $table->string('code', length: 100);
            $table->string('dyn_code', length: 100);
            $table->string('name', length: 150);
            $table->string('description', length: 255);
            $table->text('logo')->nullable();
            $table->text('logo_min')->nullable();
            $table->boolean('status')->default(true);
            $table->foreignId('group_id')
                ->constrained('ap_masters')
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
