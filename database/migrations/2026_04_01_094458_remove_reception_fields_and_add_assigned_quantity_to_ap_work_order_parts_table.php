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
        Schema::table('ap_work_order_parts', function (Blueprint $table) {
            // Eliminar campos de recepción
            $table->dropForeign(['received_by']);
            $table->dropColumn(['is_received', 'received_date', 'received_signature_url', 'received_by']);

            // Agregar campo de cantidad asignada
            $table->decimal('assigned_quantity', 10, 2)->default(0)->after('quantity_used')
                ->comment('Cantidad total asignada a técnicos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ap_work_order_parts', function (Blueprint $table) {
            // Eliminar campo agregado
            $table->dropColumn('assigned_quantity');

            // Restaurar campos de recepción
            $table->boolean('is_received')->default(false)->after('registered_by');
            $table->dateTime('received_date')->nullable()->after('is_received');
            $table->string('received_signature_url')->nullable()->after('received_date');
            $table->integer('received_by')->nullable()->after('received_signature_url');
            $table->foreign('received_by')->references('id')->on('usr_users');
        });
    }
};
