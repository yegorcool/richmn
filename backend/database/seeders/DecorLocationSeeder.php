<?php

namespace Database\Seeders;

use App\Models\DecorLocation;
use Illuminate\Database\Seeder;

class DecorLocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            [
                'name' => 'Кухня',
                'slug' => 'kitchen',
                'unlock_level' => 1,
                'max_items' => 12,
                'available_items' => [
                    ['key' => 'fridge', 'name' => 'Холодильник', 'styles' => 3],
                    ['key' => 'stove', 'name' => 'Плита', 'styles' => 3],
                    ['key' => 'table', 'name' => 'Стол', 'styles' => 3],
                    ['key' => 'chairs', 'name' => 'Стулья', 'styles' => 3],
                    ['key' => 'curtains', 'name' => 'Шторы', 'styles' => 3],
                    ['key' => 'lamp', 'name' => 'Светильник', 'styles' => 3],
                    ['key' => 'rug', 'name' => 'Коврик', 'styles' => 3],
                    ['key' => 'shelves', 'name' => 'Полки', 'styles' => 3],
                    ['key' => 'clock', 'name' => 'Часы', 'styles' => 3],
                    ['key' => 'flowers', 'name' => 'Цветы на подоконнике', 'styles' => 3],
                    ['key' => 'painting', 'name' => 'Картина', 'styles' => 3],
                    ['key' => 'teapot', 'name' => 'Чайник', 'styles' => 3],
                ],
            ],
            [
                'name' => 'Гостиная',
                'slug' => 'living_room',
                'unlock_level' => 6,
                'max_items' => 15,
                'available_items' => [
                    ['key' => 'sofa', 'name' => 'Диван', 'styles' => 3],
                    ['key' => 'coffee_table', 'name' => 'Журнальный столик', 'styles' => 3],
                    ['key' => 'tv', 'name' => 'Телевизор', 'styles' => 3],
                    ['key' => 'bookshelf', 'name' => 'Книжный шкаф', 'styles' => 3],
                    ['key' => 'carpet', 'name' => 'Ковёр', 'styles' => 3],
                    ['key' => 'floor_lamp', 'name' => 'Торшер', 'styles' => 3],
                    ['key' => 'fireplace', 'name' => 'Камин', 'styles' => 3],
                    ['key' => 'armchair', 'name' => 'Кресло', 'styles' => 3],
                    ['key' => 'vase', 'name' => 'Ваза', 'styles' => 3],
                    ['key' => 'photo_frame', 'name' => 'Фоторамка', 'styles' => 3],
                    ['key' => 'plant', 'name' => 'Растение', 'styles' => 3],
                    ['key' => 'candles', 'name' => 'Свечи', 'styles' => 3],
                    ['key' => 'pillows', 'name' => 'Подушки', 'styles' => 3],
                    ['key' => 'wall_art', 'name' => 'Настенный декор', 'styles' => 3],
                    ['key' => 'side_table', 'name' => 'Тумбочка', 'styles' => 3],
                ],
            ],
        ];

        foreach ($locations as $location) {
            DecorLocation::create($location);
        }
    }
}
