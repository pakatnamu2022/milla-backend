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
        Schema::create('scrum_item_tag', function (Blueprint $table) {
            $table->foreignId('item_id')->constrained('scrum_items')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('scrum_tags')->cascadeOnDelete();

            $table->primary(['item_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scrum_item_tag');
    }
};
