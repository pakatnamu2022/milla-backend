<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('approved_accessories', function (Blueprint $table) {
      if (Schema::hasColumn('approved_accessories', 'body_type_id')) {
        try {
          $table->dropForeign('approved_accessories_body_type_id_foreign');
        } catch (\Throwable $e) {
          // La FK puede no existir en algunos entornos.
        }
      }
    });

    Schema::table('approved_accessories', function (Blueprint $table) {
      if (Schema::hasColumn('approved_accessories', 'price')) {
        $table->dropColumn('price');
      }
      if (Schema::hasColumn('approved_accessories', 'body_type_id')) {
        $table->dropColumn('body_type_id');
      }
    });
  }

  public function down(): void
  {
    Schema::table('approved_accessories', function (Blueprint $table) {
      if (!Schema::hasColumn('approved_accessories', 'price')) {
        $table->decimal('price', 14, 2)->default(0);
      }
      if (!Schema::hasColumn('approved_accessories', 'body_type_id')) {
        $table->unsignedBigInteger('body_type_id')->nullable();
      }
    });
  }
};
