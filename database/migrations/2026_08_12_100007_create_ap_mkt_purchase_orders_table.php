<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('ap_mkt_purchase_orders', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('activity_id')->nullable();
      $table->unsignedBigInteger('proposal_id')->nullable();
      $table->unsignedBigInteger('supplier_id');
      $table->unsignedBigInteger('currency_id');
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
      $table->unsignedBigInteger('created_by')->nullable();
      $table->unsignedBigInteger('updated_by')->nullable();
      $table->timestamps();
      $table->softDeletes();

      $table->foreign('activity_id')->references('id')->on('ap_mkt_activities')->nullOnDelete();
      $table->foreign('proposal_id')->references('id')->on('ap_mkt_proposals')->nullOnDelete();
      $table->foreign('supplier_id')->references('id')->on('business_partners');
      $table->foreign('currency_id')->references('id')->on('type_currency');
      $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
      $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('ap_mkt_purchase_orders');
  }
};
