<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('characters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('theme_id')->nullable()->constrained()->nullOnDelete();
            $table->text('personality');
            $table->string('speech_style');
            $table->string('avatar_path')->nullable();
            $table->unsignedInteger('unlock_level')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('character_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->string('trigger', 50)->index();
            $table->json('conditions');
            $table->text('text');
            $table->unsignedInteger('priority')->default(50);
            $table->unsignedInteger('max_shows')->default(10);
            $table->unsignedInteger('cooldown_hours')->default(0);
            $table->timestamps();

            $table->index(['character_id', 'trigger']);
        });

        Schema::create('character_line_shows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('character_line_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('shown_count')->default(0);
            $table->timestamp('last_shown_at')->nullable();

            $table->unique(['user_id', 'character_line_id']);
        });

        Schema::create('character_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('orders_completed')->default(0);
            $table->enum('relationship_level', ['new', 'familiar', 'loyal'])->default('new');
            $table->timestamps();

            $table->unique(['user_id', 'character_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_relationships');
        Schema::dropIfExists('character_line_shows');
        Schema::dropIfExists('character_lines');
        Schema::dropIfExists('characters');
    }
};
