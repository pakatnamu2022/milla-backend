<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usr_users', function (Blueprint $table) {
            $table->string('totp_secret')->nullable()->after('password');
            $table->boolean('two_factor_enabled')->default(false)->after('totp_secret');
        });
    }

    public function down(): void
    {
        Schema::table('usr_users', function (Blueprint $table) {
            $table->dropColumn(['totp_secret', 'two_factor_enabled']);
        });
    }
};
