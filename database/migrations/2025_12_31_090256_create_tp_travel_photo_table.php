<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tp_travel_photo', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('dispatch_id');
            $table->unsignedInteger('driver_id');
            $table->unsignedBigInteger('digital_file_id')->nullable();
            $table->enum('photo_type', ['start', 'end', 'fuel', 'incident', 'invoice']);
            $table->decimal('latitude', 10,8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('user_agent',500)->nullable();
            $table->string('operating_system', 100)->nullable();
            $table->string('browser', 100)->nullable();
            $table->string('device_model', 100)->nullable()->comment('Ej: iphone, samsung, motorola');
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            //indices
            $table->index('dispatch_id');
            $table->index('driver_id');
            $table->index('photo_type');
            $table->index('created_by');
            $table->index('digital_file_id');
            $table->index(['dispatch_id', 'photo_type', 'created_at']);
            $table->index('deleted_at');

            $table->foreign('digital_file_id')
                  ->references('id')
                  ->on('gp_digital_files')
                  ->onDelete('set null');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tp_travel_photo');
    }
};
