<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('generators')
            ->where('type', 'cooldown')
            ->whereNotNull('cooldown_until')
            ->where('cooldown_until', '>', now())
            ->update(['charges_left' => 0]);

        Schema::table('themes', function (Blueprint $table) {
            $table->dropColumn('generator_type');
        });

        Schema::table('generators', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }

    public function down(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->enum('generator_type', ['chargeable', 'cooldown'])->default('chargeable');
        });

        Schema::table('generators', function (Blueprint $table) {
            $table->enum('type', ['chargeable', 'cooldown'])->default('chargeable');
        });
    }
};
