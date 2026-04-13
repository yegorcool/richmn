<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decor_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('unlock_level')->default(1);
            $table->unsignedInteger('max_items')->default(12);
            $table->json('available_items');
            $table->timestamps();
        });

        Schema::create('decor_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('decor_locations')->cascadeOnDelete();
            $table->string('item_key');
            $table->unsignedTinyInteger('style_variant')->default(1);
            $table->timestamp('placed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'location_id', 'item_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decor_items');
        Schema::dropIfExists('decor_locations');
    }
};
