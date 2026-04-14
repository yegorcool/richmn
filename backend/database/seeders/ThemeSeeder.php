<?php

namespace Database\Seeders;

use App\Models\Theme;
use Illuminate\Database\Seeder;

class ThemeSeeder extends Seeder
{
    public function run(): void
    {
        $themes = [
            [
                'name' => 'Кофейня',
                'slug' => 'coffee',
                'generator_name' => 'Кофемашина',
                'unlock_level' => 1,
                'generator_energy_cost' => 1,
                'generator_generation_limit' => 50,
                'generator_generation_timeout' => 180,
                'chain_config' => [
                    ['level' => 1, 'name' => 'Зёрна', 'sprite_key' => 'coffee_1'],
                    ['level' => 2, 'name' => 'Молотый кофе', 'sprite_key' => 'coffee_2'],
                    ['level' => 3, 'name' => 'Эспрессо', 'sprite_key' => 'coffee_3'],
                    ['level' => 4, 'name' => 'Американо', 'sprite_key' => 'coffee_4'],
                    ['level' => 5, 'name' => 'Капучино', 'sprite_key' => 'coffee_5'],
                    ['level' => 6, 'name' => 'Латте', 'sprite_key' => 'coffee_6'],
                    ['level' => 7, 'name' => 'Раф', 'sprite_key' => 'coffee_7'],
                    ['level' => 8, 'name' => 'Фраппучино', 'sprite_key' => 'coffee_8'],
                    ['level' => 9, 'name' => 'Кофейный торт', 'sprite_key' => 'coffee_9'],
                    ['level' => 10, 'name' => 'Золотой кофейный сет', 'sprite_key' => 'coffee_10'],
                ],
            ],
            [
                'name' => 'Выпечка',
                'slug' => 'bakery',
                'generator_name' => 'Духовка',
                'unlock_level' => 3,
                'generator_energy_cost' => 1,
                'generator_generation_limit' => 50,
                'generator_generation_timeout' => 360,
                'chain_config' => [
                    ['level' => 1, 'name' => 'Мука', 'sprite_key' => 'bakery_1'],
                    ['level' => 2, 'name' => 'Тесто', 'sprite_key' => 'bakery_2'],
                    ['level' => 3, 'name' => 'Печенье', 'sprite_key' => 'bakery_3'],
                    ['level' => 4, 'name' => 'Маффин', 'sprite_key' => 'bakery_4'],
                    ['level' => 5, 'name' => 'Кекс', 'sprite_key' => 'bakery_5'],
                    ['level' => 6, 'name' => 'Круассан', 'sprite_key' => 'bakery_6'],
                    ['level' => 7, 'name' => 'Пирог', 'sprite_key' => 'bakery_7'],
                    ['level' => 8, 'name' => 'Торт', 'sprite_key' => 'bakery_8'],
                    ['level' => 9, 'name' => 'Свадебный торт', 'sprite_key' => 'bakery_9'],
                    ['level' => 10, 'name' => 'Королевский торт', 'sprite_key' => 'bakery_10'],
                ],
            ],
            [
                'name' => 'Продукты',
                'slug' => 'products',
                'generator_name' => 'Грядка',
                'unlock_level' => 5,
                'generator_energy_cost' => 1,
                'generator_generation_limit' => 50,
                'generator_generation_timeout' => 300,
                'chain_config' => [
                    ['level' => 1, 'name' => 'Семена', 'sprite_key' => 'products_1'],
                    ['level' => 2, 'name' => 'Росток', 'sprite_key' => 'products_2'],
                    ['level' => 3, 'name' => 'Помидор', 'sprite_key' => 'products_3'],
                    ['level' => 4, 'name' => 'Салат', 'sprite_key' => 'products_4'],
                    ['level' => 5, 'name' => 'Сэндвич', 'sprite_key' => 'products_5'],
                    ['level' => 6, 'name' => 'Бургер', 'sprite_key' => 'products_6'],
                    ['level' => 7, 'name' => 'Пицца', 'sprite_key' => 'products_7'],
                    ['level' => 8, 'name' => 'Суши-сет', 'sprite_key' => 'products_8'],
                    ['level' => 9, 'name' => 'Фуршет', 'sprite_key' => 'products_9'],
                    ['level' => 10, 'name' => 'Банкет', 'sprite_key' => 'products_10'],
                ],
            ],
            [
                'name' => 'Ткани',
                'slug' => 'fabrics',
                'generator_name' => 'Швейная машинка',
                'unlock_level' => 24,
                'generator_energy_cost' => 1,
                'generator_generation_limit' => 50,
                'generator_generation_timeout' => 225,
                'chain_config' => [
                    ['level' => 1, 'name' => 'Нитка', 'sprite_key' => 'fabrics_1'],
                    ['level' => 2, 'name' => 'Клубок', 'sprite_key' => 'fabrics_2'],
                    ['level' => 3, 'name' => 'Лоскут', 'sprite_key' => 'fabrics_3'],
                    ['level' => 4, 'name' => 'Полотно', 'sprite_key' => 'fabrics_4'],
                    ['level' => 5, 'name' => 'Шарф', 'sprite_key' => 'fabrics_5'],
                    ['level' => 6, 'name' => 'Подушка', 'sprite_key' => 'fabrics_6'],
                    ['level' => 7, 'name' => 'Плед', 'sprite_key' => 'fabrics_7'],
                    ['level' => 8, 'name' => 'Шторы', 'sprite_key' => 'fabrics_8'],
                    ['level' => 9, 'name' => 'Лоскутное одеяло', 'sprite_key' => 'fabrics_9'],
                    ['level' => 10, 'name' => 'Дизайнерский гобелен', 'sprite_key' => 'fabrics_10'],
                ],
            ],
            [
                'name' => 'Посуда',
                'slug' => 'pottery',
                'generator_name' => 'Гончарный круг',
                'unlock_level' => 28,
                'generator_energy_cost' => 1,
                'generator_generation_limit' => 50,
                'generator_generation_timeout' => 240,
                'chain_config' => [
                    ['level' => 1, 'name' => 'Глина', 'sprite_key' => 'pottery_1'],
                    ['level' => 2, 'name' => 'Миска', 'sprite_key' => 'pottery_2'],
                    ['level' => 3, 'name' => 'Чашка', 'sprite_key' => 'pottery_3'],
                    ['level' => 4, 'name' => 'Тарелка', 'sprite_key' => 'pottery_4'],
                    ['level' => 5, 'name' => 'Ваза', 'sprite_key' => 'pottery_5'],
                    ['level' => 6, 'name' => 'Чайник', 'sprite_key' => 'pottery_6'],
                    ['level' => 7, 'name' => 'Сервиз', 'sprite_key' => 'pottery_7'],
                    ['level' => 8, 'name' => 'Фарфоровый набор', 'sprite_key' => 'pottery_8'],
                    ['level' => 9, 'name' => 'Антикварный сервиз', 'sprite_key' => 'pottery_9'],
                    ['level' => 10, 'name' => 'Золотой сервиз', 'sprite_key' => 'pottery_10'],
                ],
            ],
        ];

        foreach ($themes as $theme) {
            Theme::updateOrCreate(['slug' => $theme['slug']], $theme);
        }
    }
}
