<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = ['Sports', 'Casino', 'Stocks', 'Crypto'];

        foreach ($categories as $name) {
            Category::firstOrCreate(['name' => $name], ['is_active' => true]);
        }
    }
}
