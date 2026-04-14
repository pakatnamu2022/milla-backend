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
        Schema::table('inventory_movements', function (Blueprint $table) {
            // Add currency_id (default PEN = 3)
            $table->foreignId('currency_id')
                ->default(3)
                ->after('warehouse_destination_id')
                ->constrained('type_currency');

            // Add exchange_rate value (default 1.0 for PEN)
            $table->decimal('exchange_rate', 15, 6)
                ->default(1)
                ->after('currency_id')
                ->comment('Exchange rate used for currency conversion to PEN');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropForeign(['currency_id']);
            $table->dropColumn(['currency_id', 'exchange_rate']);
        });
    }
};
