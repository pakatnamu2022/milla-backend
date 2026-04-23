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
        Schema::table('ap_order_quotations', function (Blueprint $table) {
            $table->string('confirmation_token', 64)->nullable()->unique()->after('customer_signature_delivery_url');
            $table->datetime('confirmation_token_expires_at')->nullable()->after('confirmation_token');
            $table->datetime('confirmed_at')->nullable()->after('confirmation_token_expires_at');
            $table->string('confirmation_channel', 20)->nullable()->after('confirmed_at')->comment('presencial o virtual');
            $table->string('confirmation_ip', 45)->nullable()->after('confirmation_channel');
            $table->json('confirmation_metadata')->nullable()->after('confirmation_ip')->comment('Datos del dispositivo/navegador');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ap_order_quotations', function (Blueprint $table) {
            $table->dropColumn([
                'confirmation_token',
                'confirmation_token_expires_at',
                'confirmed_at',
                'confirmation_channel',
                'confirmation_ip',
                'confirmation_metadata'
            ]);
        });
    }
};
