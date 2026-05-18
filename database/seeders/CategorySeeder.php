<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Sports', 'icon' => 'sports'],
            ['name' => 'Casino', 'icon' => 'casino'],
            ['name' => 'Stocks', 'icon' => 'stocks'],
            ['name' => 'Crypto', 'icon' => 'crypto'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['name' => $category['name']],
                ['icon' => $category['icon'], 'is_active' => true]
            );
        }
    }
}
