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
        Schema::create('ap_exhibition_vehicles', function (Blueprint $table) {
            $table->id();

            // Supplier/Provider information
            $table->foreignId('supplier_id')->nullable()
                ->constrained('business_partners')
                ->onDelete('set null');

            // Shipping guide information (Guía de Remisión)
            $table->string('guia_number')->nullable();
            $table->date('guia_date')->nullable();
            $table->date('llegada')->nullable(); // Arrival date

            // Destination warehouse/location
            $table->foreignId('ubicacion_id')->nullable()
                ->constrained('warehouse')
                ->onDelete('set null');

            // Assignment information (at header level)
            $table->integer('advisor_id')->nullable();
            $table->foreign('advisor_id')
                ->references('id')
                ->on('rrhh_persona')
                ->onDelete('set null');

            $table->foreignId('propietario_id')->nullable()
                ->constrained('business_partners')
                ->onDelete('set null');

            // Status of the entire exhibition order
            $table->foreignId('ap_vehicle_status_id')->nullable()
                ->constrained('ap_vehicle_status')
                ->onDelete('set null');

            // Additional fields
            $table->string('pedido_sucursal')->nullable();
            $table->string('dua_number')->nullable();
            $table->text('observaciones')->nullable();

            // Status
            $table->boolean('status')->default(true);

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('supplier_id');
            $table->index('ubicacion_id');
            $table->index('advisor_id');
            $table->index('propietario_id');
            $table->index('ap_vehicle_status_id');
            $table->index('guia_date');
            $table->index('llegada');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ap_exhibition_vehicles');
    }
};
