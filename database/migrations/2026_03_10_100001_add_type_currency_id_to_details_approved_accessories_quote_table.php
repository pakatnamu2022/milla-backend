<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('details_approved_accessories_quote', function (Blueprint $table) {
            $table->unsignedBigInteger('type_currency_id')->nullable()->after('price');
            $table->foreign('type_currency_id')
                ->references('id')->on('type_currency');
        });
    }

    public function down(): void
    {
        Schema::table('details_approved_accessories_quote', function (Blueprint $table) {
            $table->dropForeign(['type_currency_id']);
            $table->dropColumn('type_currency_id');
        });
    }
};
