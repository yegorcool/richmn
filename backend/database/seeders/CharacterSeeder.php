<?php

namespace Database\Seeders;

use App\Models\Character;
use App\Models\Theme;
use Illuminate\Database\Seeder;

class CharacterSeeder extends Seeder
{
    public function run(): void
    {
        $coffeeTheme = Theme::where('slug', 'coffee')->first();
        $bakeryTheme = Theme::where('slug', 'bakery')->first();

        Character::create([
            'name' => 'Марина',
            'slug' => 'marina',
            'theme_id' => $coffeeTheme?->id,
            'personality' => 'Энергичная оптимистка, владелица кафе. Всё время спешит, но всегда добрая.',
            'speech_style' => 'Восклицания, уменьшительные, много восклицательных знаков',
            'avatar_path' => 'characters/marina.png',
            'unlock_level' => 1,
        ]);

        Character::create([
            'name' => 'Бабушка Зина',
            'slug' => 'grandma_zina',
            'theme_id' => $bakeryTheme?->id,
            'personality' => 'Тёплая, заботливая бабушка. Всех хочет накормить. Мудрая и немного ворчливая.',
            'speech_style' => 'Народные поговорки, ласковые обращения, кулинарные метафоры',
            'avatar_path' => 'characters/grandma_zina.png',
            'unlock_level' => 3,
        ]);

        Character::create([
            'name' => 'Кот Барсик',
            'slug' => 'cat_barsik',
            'theme_id' => null,
            'personality' => 'Кот-талисман. Капризный, своенравный. Появляется в любой тематике.',
            'speech_style' => 'Звукоподражания, короткие кошачьи фразы, мяу-оценки',
            'avatar_path' => 'characters/cat_barsik.png',
            'unlock_level' => 1,
        ]);
    }
}
