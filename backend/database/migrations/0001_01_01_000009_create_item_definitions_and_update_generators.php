<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theme_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('level');
            $table->string('name');
            $table->string('slug');
            $table->string('image_url')->nullable();
            $table->timestamps();

            $table->unique(['theme_id', 'level']);
        });

        Schema::table('generators', function (Blueprint $table) {
            $table->unsignedInteger('generation_limit')->default(5)->after('max_charges');
            $table->unsignedInteger('generation_timeout_seconds')->default(1800)->after('generation_limit');
            $table->unsignedInteger('energy_cost')->default(1)->after('generation_timeout_seconds');
        });

        Schema::table('themes', function (Blueprint $table) {
            $table->unsignedInteger('generator_energy_cost')->default(1)->after('is_active');
            $table->unsignedInteger('generator_generation_limit')->default(5)->after('generator_energy_cost');
            $table->unsignedInteger('generator_generation_timeout')->default(1800)->after('generator_generation_limit');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_definitions');

        Schema::table('generators', function (Blueprint $table) {
            $table->dropColumn(['generation_limit', 'generation_timeout_seconds', 'energy_cost']);
        });

        Schema::table('themes', function (Blueprint $table) {
            $table->dropColumn(['generator_energy_cost', 'generator_generation_limit', 'generator_generation_timeout']);
        });
    }
};
