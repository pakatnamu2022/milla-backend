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
        Schema::create('ap_internal_notes', function (Blueprint $table) {
            $table->id();
            $table->string('number', 50)->unique()->comment('Internal note sequential number (e.g., IN-00001)');
            $table->foreignId('work_order_id')->constrained('ap_work_orders')->onDelete('cascade');
            $table->date('created_date')->comment('Internal note creation date');
            $table->date('closed_date')->nullable()->comment('Closing date when associated with an invoice');
            $table->enum('status', ['pending', 'invoiced'])->default('pending')->comment('Internal note status');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('work_order_id');
            $table->index('status');
            $table->index('created_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ap_internal_notes');
    }
};