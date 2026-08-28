<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('approved_accessory_prices', function (Blueprint $table) {
      $table->id();
      $table->foreignId('approved_accessory_id')
        ->constrained('approved_accessories')
        ->cascadeOnDelete();
      $table->unsignedBigInteger('body_type_id');
      $table->decimal('price', 14, 2)->default(0);
      $table->timestamps();

      $table->foreign('body_type_id')->references('id')->on('ap_masters');
      $table->unique(['approved_accessory_id', 'body_type_id'], 'aap_accessory_body_unique');
    });

    // Migrar los accesorios homologados existentes: cada uno tenía una sola
    // carrocería y un solo precio -> se convierte en una fila de precio.
    $accessories = DB::table('approved_accessories')
      ->whereNull('deleted_at')
      ->whereNotNull('body_type_id')
      ->get(['id', 'body_type_id', 'price']);

    foreach ($accessories as $accessory) {
      DB::table('approved_accessory_prices')->insert([
        'approved_accessory_id' => $accessory->id,
        'body_type_id'          => $accessory->body_type_id,
        'price'                 => $accessory->price ?? 0,
        'created_at'            => now(),
        'updated_at'            => now(),
      ]);
    }
  }

  public function down(): void
  {
    Schema::dropIfExists('approved_accessory_prices');
  }
};
