<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('credit_note_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')
                ->constrained('ap_purchase_order')
                ->onDelete('cascade');
            $table->timestamp('attempted_at');
            $table->enum('status', ['success', 'error']);
            $table->string('credit_note_number')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('execution_time')->nullable()->comment('Execution time in milliseconds');
            $table->timestamps();

            // Índices
            $table->index('purchase_order_id');
            $table->index('attempted_at');
            $table->index('status');
        });

        // Crear índice único compuesto para evitar múltiples intentos en el mismo día
        DB::statement('CREATE UNIQUE INDEX unique_po_sync_per_day ON credit_note_sync_logs (purchase_order_id, (DATE(attempted_at)))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_note_sync_logs');
    }
};
