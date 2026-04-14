<?php

namespace Database\Seeders;

use App\Models\ItemDefinition;
use App\Models\Theme;
use Illuminate\Database\Seeder;

class ItemDefinitionSeeder extends Seeder
{
    /**
     * Icons served via Iconify API (open-source, CC-licensed).
     * Collections: noto (Google Noto Color Emoji), twemoji, fxemoji, openmoji.
     * Format: https://api.iconify.design/{collection}/{icon}.svg
     */
    public function run(): void
    {
        $this->seedCoffee();
        $this->seedBakery();
        $this->seedProducts();
        $this->seedFabrics();
        $this->seedPottery();
    }

    private function seedCoffee(): void
    {
        $theme = Theme::updateOrCreate(
            ['slug' => 'coffee'],
            [
                'name' => 'Кофейня',
                'generator_name' => 'Кофемашина',
                'unlock_level' => 1,
                'is_active' => true,
                'generator_energy_cost' => 1,
                'generator_generation_limit' => 50,
                'generator_generation_timeout' => 180,
                'chain_config' => [],
            ]
        );

        $items = [
            [1, 'Зёрна', 'https://api.iconify.design/noto/chestnut.svg'],
            [2, 'Молотый кофе', 'https://api.iconify.design/noto/hot-beverage.svg'],
            [3, 'Эспрессо', 'https://api.iconify.design/noto/teacup-without-handle.svg'],
            [4, 'Американо', 'https://api.iconify.design/twemoji/hot-beverage.svg'],
            [5, 'Капучино', 'https://api.iconify.design/openmoji/hot-beverage.svg'],
            [6, 'Латте', 'https://api.iconify.design/noto/glass-of-milk.svg'],
            [7, 'Раф', 'https://api.iconify.design/noto/tumbler-glass.svg'],
            [8, 'Фраппучино', 'https://api.iconify.design/noto/bubble-tea.svg'],
            [9, 'Кофейный торт', 'https://api.iconify.design/noto/shortcake.svg'],
            [10, 'Золотой кофейный сет', 'https://api.iconify.design/noto/trophy.svg'],
        ];

        $this->createDefinitions($theme, $items);
    }

    private function seedBakery(): void
    {
        $theme = Theme::updateOrCreate(
            ['slug' => 'bakery'],
            [
                'name' => 'Выпечка',
                'generator_name' => 'Духовка',
                'unlock_level' => 3,
                'is_active' => true,
                'generator_energy_cost' => 1,
                'generator_generation_limit' => 50,
                'generator_generation_timeout' => 360,
                'chain_config' => [],
            ]
        );

        $items = [
            [1, 'Мука', 'https://api.iconify.design/game-icons/wheat.svg'],
            [2, 'Тесто', 'https://api.iconify.design/noto/dumpling.svg'],
            [3, 'Печенье', 'https://api.iconify.design/twemoji/cookie.svg'],
            [4, 'Маффин', 'https://api.iconify.design/twemoji/cupcake.svg'],
            [5, 'Кекс', 'https://api.iconify.design/twemoji/birthday-cake.svg'],
            [6, 'Круассан', 'https://api.iconify.design/twemoji/croissant.svg'],
            [7, 'Пирог', 'https://api.iconify.design/twemoji/pie.svg'],
            [8, 'Торт', 'https://api.iconify.design/noto/shortcake.svg'],
            [9, 'Свадебный торт', 'https://api.iconify.design/twemoji/wedding.svg'],
            [10, 'Королевский торт', 'https://api.iconify.design/noto/crown.svg'],
        ];

        $this->createDefinitions($theme, $items);
    }

    private function seedProducts(): void
    {
        $theme = Theme::updateOrCreate(
            ['slug' => 'products'],
            [
                'name' => 'Продукты',
                'generator_name' => 'Грядка',
                'unlock_level' => 5,
                'is_active' => true,
                'generator_energy_cost' => 1,
                'generator_generation_limit' => 50,
                'generator_generation_timeout' => 300,
                'chain_config' => [],
            ]
        );

        $items = [
            [1, 'Семена', 'https://api.iconify.design/noto/herb.svg'],
            [2, 'Росток', 'https://api.iconify.design/twemoji/seedling.svg'],
            [3, 'Помидор', 'https://api.iconify.design/twemoji/tomato.svg'],
            [4, 'Салат', 'https://api.iconify.design/twemoji/green-salad.svg'],
            [5, 'Сэндвич', 'https://api.iconify.design/twemoji/sandwich.svg'],
            [6, 'Бургер', 'https://api.iconify.design/twemoji/hamburger.svg'],
            [7, 'Пицца', 'https://api.iconify.design/twemoji/pizza.svg'],
            [8, 'Суши-сет', 'https://api.iconify.design/twemoji/sushi.svg'],
            [9, 'Фуршет', 'https://api.iconify.design/twemoji/fork-and-knife-with-plate.svg'],
            [10, 'Банкет', 'https://api.iconify.design/twemoji/clinking-glasses.svg'],
        ];

        $this->createDefinitions($theme, $items);
    }

    private function seedFabrics(): void
    {
        $theme = Theme::updateOrCreate(
            ['slug' => 'fabrics'],
            [
                'name' => 'Ткани',
                'generator_name' => 'Швейная машинка',
                'unlock_level' => 24,
                'is_active' => true,
                'generator_energy_cost' => 1,
                'generator_generation_limit' => 50,
                'generator_generation_timeout' => 225,
                'chain_config' => [],
            ]
        );

        $items = [
            [1, 'Нитка', 'https://api.iconify.design/noto/thread.svg'],
            [2, 'Клубок', 'https://api.iconify.design/noto/yarn.svg'],
            [3, 'Лоскут', 'https://api.iconify.design/noto/rolled-up-newspaper.svg'],
            [4, 'Полотно', 'https://api.iconify.design/noto/scroll.svg'],
            [5, 'Шарф', 'https://api.iconify.design/noto/scarf.svg'],
            [6, 'Подушка', 'https://api.iconify.design/twemoji/couch-and-lamp.svg'],
            [7, 'Плед', 'https://api.iconify.design/noto/bed.svg'],
            [8, 'Шторы', 'https://api.iconify.design/noto/window.svg'],
            [9, 'Лоскутное одеяло', 'https://api.iconify.design/noto/kimono.svg'],
            [10, 'Дизайнерский гобелен', 'https://api.iconify.design/noto/framed-picture.svg'],
        ];

        $this->createDefinitions($theme, $items);
    }

    private function seedPottery(): void
    {
        $theme = Theme::updateOrCreate(
            ['slug' => 'pottery'],
            [
                'name' => 'Посуда',
                'generator_name' => 'Гончарный круг',
                'unlock_level' => 28,
                'is_active' => true,
                'generator_energy_cost' => 1,
                'generator_generation_limit' => 50,
                'generator_generation_timeout' => 240,
                'chain_config' => [],
            ]
        );

        $items = [
            [1, 'Глина', 'https://api.iconify.design/noto/rock.svg'],
            [2, 'Миска', 'https://api.iconify.design/noto/bowl-with-spoon.svg'],
            [3, 'Чашка', 'https://api.iconify.design/noto/teacup-without-handle.svg'],
            [4, 'Тарелка', 'https://api.iconify.design/noto/shallow-pan-of-food.svg'],
            [5, 'Ваза', 'https://api.iconify.design/twemoji/amphora.svg'],
            [6, 'Чайник', 'https://api.iconify.design/noto/teapot.svg'],
            [7, 'Сервиз', 'https://api.iconify.design/noto/fork-and-knife-with-plate.svg'],
            [8, 'Фарфоровый набор', 'https://api.iconify.design/noto/sake.svg'],
            [9, 'Антикварный сервиз', 'https://api.iconify.design/noto/amphora.svg'],
            [10, 'Золотой сервиз', 'https://api.iconify.design/noto/crown.svg'],
        ];

        $this->createDefinitions($theme, $items);
    }

    private function createDefinitions(Theme $theme, array $items): void
    {
        $chainConfig = [];

        foreach ($items as [$level, $name, $imageUrl]) {
            $slug = $theme->slug . '_' . $level;

            ItemDefinition::updateOrCreate(
                ['theme_id' => $theme->id, 'level' => $level],
                [
                    'name' => $name,
                    'slug' => $slug,
                    'image_url' => $imageUrl,
                ]
            );

            $chainConfig[] = [
                'level' => $level,
                'name' => $name,
                'sprite_key' => $slug,
            ];
        }

        $theme->update(['chain_config' => $chainConfig]);
    }
}
