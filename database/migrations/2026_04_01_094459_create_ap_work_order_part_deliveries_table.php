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
        Schema::create('ap_work_order_part_deliveries', function (Blueprint $table) {
            $table->id();

            // Relación con el repuesto de la orden de trabajo
            $table->foreignId('work_order_part_id')->comment('Repuesto de la orden de trabajo')
                ->constrained('ap_work_order_parts')->onDelete('cascade');

            // A quién se entrega (técnico)
            $table->integer('delivered_to')->comment('Técnico que recibe el repuesto');
            $table->foreign('delivered_to')->references('id')->on('usr_users')->onDelete('cascade');

            // Cantidad entregada
            $table->decimal('delivered_quantity', 10, 2)->comment('Cantidad entregada al técnico');

            // Fecha y quien entrega (puede ser almacenero o supervisor)
            $table->dateTime('delivered_date')->comment('Fecha de entrega');
            $table->integer('delivered_by')->comment('Usuario que entrega el repuesto');
            $table->foreign('delivered_by')->references('id')->on('usr_users')->onDelete('cascade');

            // Confirmación de recepción
            $table->boolean('is_received')->default(false)->comment('Indica si el técnico confirmó la recepción');
            $table->dateTime('received_date')->nullable()->comment('Fecha de confirmación de recepción');
            $table->string('received_signature_url')->nullable()->comment('URL de la firma de recepción');
            $table->integer('received_by')->nullable()->comment('Usuario que confirma la recepción');
            $table->foreign('received_by')->references('id')->on('usr_users')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('work_order_part_id');
            $table->index('delivered_to');
            $table->index('delivered_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ap_work_order_part_deliveries');
    }
};
