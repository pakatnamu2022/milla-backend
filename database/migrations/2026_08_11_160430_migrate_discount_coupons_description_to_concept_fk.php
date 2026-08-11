<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    // Remap concept_code_id from parent (861/862) to the matching child in ap_masters,
    // fixing the historical typo "DIFEERENCIA" → "DIFERENCIA" along the way.
    DB::statement("
      UPDATE discount_coupons dc
      JOIN ap_masters am
        ON am.parent_id = dc.concept_code_id
        AND am.type = 'CONCEPT_DISCOUNT_BOND_DESCRIPTION'
        AND UPPER(am.description) = UPPER(
          CASE dc.description
            WHEN 'BONO DIFEERENCIA DE PRECIO' THEN 'BONO DIFERENCIA DE PRECIO'
            ELSE dc.description
          END
        )
      SET dc.concept_code_id = am.id
      WHERE dc.concept_code_id IN (861, 862)
    ");

    Schema::table('discount_coupons', function (Blueprint $table) {
      $table->dropColumn('description');
    });
  }

  public function down(): void
  {
    Schema::table('discount_coupons', function (Blueprint $table) {
      $table->string('description')->nullable()->after('id');
    });

    // Restore description from the child ap_masters record (or parent if no child)
    DB::statement("
      UPDATE discount_coupons dc
      JOIN ap_masters child ON child.id = dc.concept_code_id
      LEFT JOIN ap_masters parent ON parent.id = child.parent_id
      SET dc.description = child.description,
          dc.concept_code_id = COALESCE(child.parent_id, dc.concept_code_id)
      WHERE child.type = 'CONCEPT_DISCOUNT_BOND_DESCRIPTION'
    ");
  }
};
