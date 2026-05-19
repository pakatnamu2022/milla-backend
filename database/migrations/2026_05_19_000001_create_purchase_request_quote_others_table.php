<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_request_quote_others', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_quote_id')
                ->constrained('purchase_request_quote')
                ->cascadeOnDelete();
            $table->string('description', 100);
            $table->enum('type', ['FIJO', 'PORCENTAJE']);
            $table->decimal('value', 12, 4);
            $table->decimal('amount', 12, 4);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_request_quote_others');
    }
};
