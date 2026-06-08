<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Sports', 'icon' => 'sports.svg', 'description' => 'Sports predictions'],
            ['name' => 'Casino', 'icon' => 'casino.svg', 'description' => 'Casino predictions'],
            ['name' => 'Stocks', 'icon' => 'stocks.svg', 'description' => 'Stocks predictions'],
            ['name' => 'Crypto', 'icon' => 'crypto.svg', 'description' => 'Crypto predictions'],
        ];

        foreach ($categories as $data) {
            $iconPath = "categories/icons/{$data['icon']}";

            Storage::disk('public')->put($iconPath, $this->svgIcon($data['name']));

            Category::updateOrCreate(
                ['name' => $data['name']],
                [
                    'icon' => $iconPath,
                    'image' => null,
                    'description' => $data['description'],
                    'is_active' => true,
                ],
            );
        }
    }

    private function svgIcon(string $label): string
    {
        $initial = mb_strtoupper(mb_substr($label, 0, 1));

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" role="img" aria-label="{$label}">
  <rect width="64" height="64" rx="16" fill="#111827"/>
  <circle cx="48" cy="16" r="8" fill="#f59e0b"/>
  <text x="32" y="40" text-anchor="middle" font-family="Arial, sans-serif" font-size="28" font-weight="700" fill="#ffffff">{$initial}</text>
</svg>
SVG;
    }
}
