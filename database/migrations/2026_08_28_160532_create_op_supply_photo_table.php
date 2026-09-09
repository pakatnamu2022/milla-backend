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
        Schema::create('op_supply_photo', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supply_control_id')->nullable();
            $table->unsignedBigInteger('digital_file_id');
            $table->string('file_name', 255);
            $table->string('file_path', 500);
            $table->integer('file_size')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->string('photo_type', 50)->default('ticket');
            $table->dateTime('uploaded_at');
            $table->integer('uploaded_by')->nullable();
            $table->timestamps();
            $table->integer('status_deleted')->default(1);

            $table->index('supply_control_id');
            $table->index('digital_file_id');
            $table->index('photo_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('op_supply_photo');
    }
};
