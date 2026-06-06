<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
  public function up(): void
  {
    DB::statement("UPDATE ap_vehicle_delivery SET status_delivery = 'completed' WHERE status_delivery = 'delivered'");
    DB::statement("ALTER TABLE ap_vehicle_delivery MODIFY COLUMN status_delivery ENUM('pending', 'completed') NOT NULL DEFAULT 'pending'");
  }

  public function down(): void
  {
    DB::statement("ALTER TABLE ap_vehicle_delivery MODIFY COLUMN status_delivery ENUM('pending', 'delivered', 'completed') NOT NULL DEFAULT 'pending'");
  }
};
