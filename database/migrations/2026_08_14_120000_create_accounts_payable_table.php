<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('accounts_payable', function (Blueprint $table) {
      $table->id();
      $table->string('documento', 60)->unique();
      $table->string('proveedor_documento', 30)->nullable();
      $table->string('proveedor_nombre', 120)->nullable();
      $table->date('fecha_documento')->nullable();
      $table->date('fecha_contable')->nullable();
      $table->string('moneda', 20)->nullable();
      $table->decimal('monto', 14, 5)->default(0);
      $table->decimal('monto_sin_aplicar', 14, 5)->default(0);
      $table->timestamp('synced_at')->nullable();
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('accounts_payable');
  }
};
