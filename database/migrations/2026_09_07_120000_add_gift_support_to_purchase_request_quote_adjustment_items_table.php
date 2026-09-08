<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('purchase_request_quote_adjustment_items', function (Blueprint $table) {
      // Distingue una línea de bono/descuento (discount_coupons) de una de
      // obsequio (details_approved_accessories_quote con type = OBSEQUIO).
      $table->string('item_type')->default('bonus_discount')->after('action'); // bonus_discount | gift

      // Solo para item_type = gift:
      $table->unsignedBigInteger('accessory_detail_id')->nullable()->after('discount_coupon_id'); // fila objetivo en update/delete
      $table->unsignedBigInteger('approved_accessory_id')->nullable()->after('accessory_detail_id'); // accesorio homologado en create
      $table->unsignedBigInteger('body_type_id')->nullable()->after('approved_accessory_id');
      $table->integer('quantity')->nullable()->after('body_type_id');
      $table->decimal('additional_price', 14, 4)->nullable()->after('quantity');

      $table->foreign('accessory_detail_id', 'prqai_accessory_detail_fk')
        ->references('id')->on('details_approved_accessories_quote')->nullOnDelete();
      $table->foreign('approved_accessory_id', 'prqai_approved_accessory_fk')
        ->references('id')->on('approved_accessories')->nullOnDelete();
    });
  }

  public function down(): void
  {
    Schema::table('purchase_request_quote_adjustment_items', function (Blueprint $table) {
      $table->dropForeign('prqai_accessory_detail_fk');
      $table->dropForeign('prqai_approved_accessory_fk');
      $table->dropColumn([
        'item_type',
        'accessory_detail_id',
        'approved_accessory_id',
        'body_type_id',
        'quantity',
        'additional_price',
      ]);
    });
  }
};
