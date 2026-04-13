<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ThemeSeeder::class,
            ItemDefinitionSeeder::class,
            CharacterSeeder::class,
            CharacterLineSeeder::class,
            DecorLocationSeeder::class,
        ]);
    }
}
