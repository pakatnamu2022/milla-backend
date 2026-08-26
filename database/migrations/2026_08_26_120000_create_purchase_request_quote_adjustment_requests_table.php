<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('purchase_request_quote_adjustment_requests', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('purchase_request_quote_id');
      $table->integer('requested_by_id'); // usr_users.id es int (signed), no bigint
      $table->string('status')->default('pending'); // pending | approved | rejected
      $table->text('reason')->nullable();
      $table->decimal('margin_amount_before', 14, 4)->default(0);
      $table->decimal('margin_pct_before', 8, 4)->default(0);
      $table->decimal('margin_amount_after', 14, 4)->default(0);
      $table->decimal('margin_pct_after', 8, 4)->default(0);
      $table->integer('resolved_by_id')->nullable();
      $table->timestamp('resolved_at')->nullable();
      $table->text('rejection_reason')->nullable();
      $table->timestamps();
      $table->softDeletes();

      $table->foreign('purchase_request_quote_id', 'prqar_prq_id_fk')
        ->references('id')->on('purchase_request_quote')->cascadeOnDelete();
      $table->foreign('requested_by_id', 'prqar_requested_by_fk')
        ->references('id')->on('usr_users');
      $table->foreign('resolved_by_id', 'prqar_resolved_by_fk')
        ->references('id')->on('usr_users');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('purchase_request_quote_adjustment_requests');
  }
};
