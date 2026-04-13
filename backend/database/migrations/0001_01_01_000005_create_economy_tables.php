<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('energy_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['spent', 'recovered', 'rewarded', 'bonus']);
            $table->integer('amount');
            $table->string('source')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('chests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['small', 'medium', 'large', 'super']);
            $table->string('source')->nullable();
            $table->json('contents')->nullable();
            $table->timestamp('unlock_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'opened_at']);
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['coins', 'experience', 'decor_resource']);
            $table->integer('amount');
            $table->string('source');
            $table->timestamps();

            $table->index(['user_id', 'type']);
        });

        Schema::create('streaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('current_streak')->default(0);
            $table->date('last_login_date')->nullable();
            $table->unsignedInteger('longest_streak')->default(0);
            $table->boolean('reward_claimed_today')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('streaks');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('chests');
        Schema::dropIfExists('energy_logs');
    }
};
