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
        Schema::create('ap_exhibition_vehicle_items', function (Blueprint $table) {
            $table->id();

            // Foreign key to header
            $table->foreignId('exhibition_vehicle_id')
                ->constrained('ap_exhibition_vehicles')
                ->onDelete('cascade');

            // Item type: 'vehicle' or 'equipment'
            $table->enum('item_type', ['vehicle', 'equipment']);

            // Vehicle reference (only for item_type='vehicle')
            $table->foreignId('vehicle_id')->nullable()
                ->constrained('ap_vehicles')
                ->onDelete('set null');

            // Item details
            $table->text('description')->nullable(); // Description of the item
            $table->integer('quantity')->default(1); // Quantity (always 1 for vehicles)
            $table->text('observaciones')->nullable(); // Item-specific observations

            // Status
            $table->boolean('status')->default(true);

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('exhibition_vehicle_id');
            $table->index('vehicle_id');
            $table->index('item_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ap_exhibition_vehicle_items');
    }
};
