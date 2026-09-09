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
        Schema::create('op_supplier', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('ruc', 20)->nullable();
            $table->string('address', 500)->nullable();
            $table->string('phone', 50)->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->integer('created_by')->nullable();
            $table->timestamps();
            $table->integer('status_deleted')->default(1);

            $table->index('name');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('op_supplier');
    }
};
