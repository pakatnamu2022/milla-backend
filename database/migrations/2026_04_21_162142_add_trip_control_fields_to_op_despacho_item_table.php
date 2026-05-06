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
        Schema::table('op_despacho_item', function (Blueprint $table) {
             $table->integer('order')->default(0)->after('despacho_id');
            $table->decimal('initial_mileage', 12, 2)->nullable()->after('km_viaje');
            $table->decimal('final_mileage', 12, 2)->nullable()->after('initial_mileage');
            $table->decimal('total_mileage', 12, 2)->nullable()->after('final_mileage');
            $table->decimal('total_hours', 12, 2)->nullable()->after('total_mileage');
            $table->dateTime('actual_start')->nullable()->after('total_hours');
            $table->dateTime('actual_end')->nullable()->after('actual_start');
            $table->decimal('start_latitude', 10, 8)->nullable()->after('actual_end');
            $table->decimal('start_longitude', 11, 8)->nullable()->after('start_latitude');
            $table->decimal('end_latitude', 10, 8)->nullable()->after('start_longitude');
            $table->decimal('end_longitude', 11, 8)->nullable()->after('end_latitude');
            $table->string('segment_status')->default('pending')->after('end_longitude');
            $table->index(['despacho_id', 'order']);
            $table->index('segment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('op_despacho_item', function (Blueprint $table) {
            $table->dropIndex(['despacho_id', 'order']);
            $table->dropIndex(['segment_status']);
            
            $table->dropColumn([
                'order',
                'initial_mileage',
                'final_mileage',
                'total_mileage',
                'total_hours',
                'actual_start',
                'actual_end',
                'start_latitude',
                'start_longitude',
                'end_latitude',
                'end_longitude',
                'segment_status'
            ]);
        });
    }
};
