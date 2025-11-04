<?php

use App\Models\ap\facturacion\ElectronicDocument;
use App\Models\ap\facturacion\ElectronicDocumentInstallment;
use App\Models\ap\facturacion\ElectronicDocumentItem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    ElectronicDocumentInstallment::query()->truncate();
    ElectronicDocumentItem::query()->truncate();
    ElectronicDocument::query()->truncate();
    Schema::table('ap_billing_electronic_documents', function (Blueprint $table) {
      $table->foreignId('exchange_rate_id')
        ->after('tipo_de_cambio')
        ->constrained('exchange_rate');
      $table->foreignId('client_id')
        ->after('sunat_concept_identity_document_type_id')->constrained('business_partners');
      $table->enum('financing_type', ['CONVENIO', 'VEHICULAR'])->default('VEHICULAR')->after('client_id');
      $table->foreignId('bank_id')->after('financing_type')->nullable()->constrained('ap_bank');
      $table->string('operation_number')->after('bank_id')->nullable();
    });
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_billing_electronic_documents', function (Blueprint $table) {
      $table->dropForeign(['exchange_rate_id']);
      $table->dropColumn('exchange_rate_id');
      $table->dropForeign(['bank_id']);
      $table->dropColumn('bank_id');
      $table->dropForeign(['client_id']);
      $table->dropColumn('client_id');
      $table->dropColumn('financing_type');
      $table->dropColumn('operation_number');
    });
  }
};
