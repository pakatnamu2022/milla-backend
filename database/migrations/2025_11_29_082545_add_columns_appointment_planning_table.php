<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::table('appointment_planning', function (Blueprint $table) {
      $table->foreignId('type_operation_appointment_id')->after('id')->constrained('ap_masters')->onDelete('cascade');
      $table->foreignId('type_planning_id')->after('type_operation_appointment_id')->constrained('ap_masters')->onDelete('cascade');
      $table->foreignId('ap_vehicle_id')->after('type_planning_id')->constrained('ap_vehicles')->onDelete('cascade');
      $table->integer('advisor_id')->after('ap_vehicle_id');
      $table->foreign('advisor_id')->references('id')->on('rrhh_persona')->onDelete('cascade');
      $table->string('description')->after('ap_vehicle_id');
      $table->date('delivery_date')->after('description');
      $table->time('delivery_time')->after('delivery_date');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('appointment_planning', function (Blueprint $table) {
      $table->dropForeign(['type_operation_appointment_id']);
      $table->dropForeign(['type_planning_id']);
      $table->dropForeign(['ap_vehicle_id']);
      $table->dropForeign(['advisor_id']);
      $table->dropColumn(['type_operation_appointment_id', 'type_planning_id', 'ap_vehicle_id', 'advisor_id', 'description', 'delivery_date', 'delivery_time']);
    });
  }
};
