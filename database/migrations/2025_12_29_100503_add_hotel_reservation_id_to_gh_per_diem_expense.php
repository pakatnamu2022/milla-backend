<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('gh_per_diem_expense', function (Blueprint $table) {
            $table->unsignedBigInteger('hotel_reservation_id')->nullable()->after('per_diem_request_id');
            $table->foreign('hotel_reservation_id')->references('id')->on('gh_hotel_reservation')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gh_per_diem_expense', function (Blueprint $table) {
            $table->dropForeign(['hotel_reservation_id']);
            $table->dropColumn('hotel_reservation_id');
        });
    }
};
