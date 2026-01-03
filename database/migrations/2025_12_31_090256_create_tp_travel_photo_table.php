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
            $table->enum('photo_type', ['start', 'end', 'fuel', 'incident', 'invoice']);
            $table->string('file_name', 255);
            $table->string('path', 500);
            $table->string('public_url', 1000)->nullable();
            $table->string('mime_type',100)->default('image/jpeg');
            $table->decimal('latitude', 10,8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('user_agent',500)->nullable();
            $table->string('operating_system', 100)->nullable();
            $table->string('browser', 100)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            //indices
            $table->index('dispatch_id');
            $table->index('driver_id');
            $table->index('photo_type');
            $table->index('created_by');
            $table->index(['dispatch_id', 'photo_type', 'created_at']);
        });

        

        DB::statement('
            ALTER TABLE tp_travel_photo
            ADD CONSTRAINT chk_latitude_range
            CHECK (latitude IS NULL OR (latitude >= -90 AND latitude <= 90))
        ');

        DB::statement('
            ALTER TABLE tp_travel_photo
            ADD CONSTRAINT chk_longitude_range
            CHECK (longitude IS NULL OR (longitude >= -180 AND longitude <= 180))
        ');

        DB::statement('
            ALTER TABLE tp_travel_photo
            ADD CONSTRAINT chk_complete_coordinates
            CHECK (
                (latitude IS NULL AND longitude IS NULL) OR
                (latitude IS NOT NULL AND longitude IS NOT NULL)
            )
        ');

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tp_travel_photo');
    }
};
