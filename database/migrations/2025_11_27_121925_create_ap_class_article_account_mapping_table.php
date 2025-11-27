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
        Schema::create('ap_class_article_account_mapping', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ap_class_article_id')
                ->constrained('ap_class_article')
                ->onDelete('cascade');
            $table->enum('account_type', ['PRECIO', 'DESCUENTO']);
            $table->string('account_origin', 50)->comment('Cuenta origen (ej: 4961100)');
            $table->string('account_destination', 50)->comment('Cuenta destino (ej: 7011111)');
            $table->boolean('is_debit_origin')->default(true)->comment('true si origen va en débito, false si va en crédito');
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->unique(['ap_class_article_id', 'account_type'], 'ap_class_account_unique');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ap_class_article_account_mapping');
    }
};
