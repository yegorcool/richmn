<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('field_data');
            $table->timestamps();
        });

        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('theme_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('item_level');
            $table->unsignedTinyInteger('grid_x');
            $table->unsignedTinyInteger('grid_y');
            $table->timestamps();

            $table->index(['user_id', 'grid_x', 'grid_y']);
        });

        Schema::create('generators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('theme_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['chargeable', 'cooldown']);
            $table->unsignedTinyInteger('level')->default(1);
            $table->unsignedInteger('charges_left')->default(5);
            $table->unsignedInteger('max_charges')->default(5);
            $table->timestamp('cooldown_until')->nullable();
            $table->unsignedTinyInteger('grid_x');
            $table->unsignedTinyInteger('grid_y');
            $table->timestamps();

            $table->index(['user_id', 'grid_x', 'grid_y']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->json('required_items');
            $table->json('reward');
            $table->enum('status', ['active', 'completed', 'expired'])->default('active');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
        Schema::dropIfExists('generators');
        Schema::dropIfExists('items');
        Schema::dropIfExists('game_states');
    }
};
