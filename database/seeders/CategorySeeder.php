<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'For Her',      'slug' => 'for-her'],
            ['name' => 'For Him',      'slug' => 'for-him'],
            ['name' => 'Unisex',       'slug' => 'unisex'],
            ['name' => 'Best Sellers', 'slug' => 'best-sellers'],
            ['name' => 'New Arrivals', 'slug' => 'new-arrivals'],
            ['name' => 'Gift Sets',    'slug' => 'gift-sets'],
            ['name' => 'Travel Size',  'slug' => 'travel-size'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
