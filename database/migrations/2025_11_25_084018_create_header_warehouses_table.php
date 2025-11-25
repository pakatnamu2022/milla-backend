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
        Schema::create('header_warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('dyn_code', 10);
            $table->boolean('status')->default(true);
            $table->boolean('is_received')->default(true);
            $table->integer('sede_id');
            $table->foreign('sede_id')->references('id')->on('config_sede');
            $table->foreignId('type_operation_id')
                ->constrained('ap_commercial_masters');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('header_warehouses');
    }
};
