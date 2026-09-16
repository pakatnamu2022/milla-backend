<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rrhh_mensaje_etapa_proceso', function (Blueprint $table) {
            $table->id();
            $table->string('etapa', 50)->unique();
            $table->string('asunto', 200);
            $table->text('contenido');
            $table->boolean('activo')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rrhh_mensaje_etapa_proceso');
    }
};
