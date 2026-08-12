<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('ap_mkt_purchase_orders', function (Blueprint $table) {
      $table->id();
      $table->string('number', 50)->nullable();
      $table->decimal('amount', 14, 2)->default(0);
      $table->date('issue_date')->nullable();
      $table->enum('status', [
        'draft',
        'sent',
        'in_execution',
        'pending_support',
        'supported',
        'pending_billing',
        'billed',
        'closed',
        'cancelled',
      ])->default('draft');
      $table->timestamp('sent_at')->nullable();
      $table->timestamp('billed_at')->nullable();
      $table->text('notes')->nullable();
      $table->integer('created_by')->nullable();
      $table->integer('updated_by')->nullable();
      $table->timestamps();
      $table->softDeletes();

      $table->foreignId('activity_id')->nullable()->constrained('ap_mkt_activities')->nullOnDelete();
      $table->foreignId('proposal_id')->nullable()->constrained('ap_mkt_proposals')->nullOnDelete();
      $table->foreignId('supplier_id')->constrained('business_partners');
      $table->foreignId('currency_id')->constrained('type_currency');
      $table->foreign('created_by')->references('id')->on('usr_users')->nullOnDelete();
      $table->foreign('updated_by')->references('id')->on('usr_users')->nullOnDelete();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('ap_mkt_purchase_orders');
  }
};
