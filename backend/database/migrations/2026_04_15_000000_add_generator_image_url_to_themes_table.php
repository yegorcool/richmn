<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->string('generator_image_url', 500)->nullable()->after('generator_name');
        });
    }

    public function down(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->dropColumn('generator_image_url');
        });
    }
};
