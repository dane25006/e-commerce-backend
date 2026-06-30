<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            'for-her' => [
                'name'        => 'Floral Eau de Parfum',
                'slug'        => 'floral-eau-de-parfum',
                'description' => 'A timeless floral fragrance with notes of rose, jasmine, and sandalwood.',
                'price'       => 89.99,
                'sale_price'  => 69.99,
                'gender'      => 'female',
                'type'        => 'eau-de-parfum',
            ],
            'for-him' => [
                'name'        => 'Woody Cologne',
                'slug'        => 'woody-cologne',
                'description' => 'A bold woody cologne with cedar, amber, and bergamot.',
                'price'       => 79.99,
                'sale_price'  => 59.99,
                'gender'      => 'male',
                'type'        => 'cologne',
            ],
            'unisex' => [
                'name'        => 'Fresh Linen Mist',
                'slug'        => 'fresh-linen-mist',
                'description' => 'A crisp unisex scent with clean linen and white musk.',
                'price'       => 64.99,
                'gender'      => 'unisex',
                'type'        => 'body-mist',
            ],
            'best-sellers' => [
                'name'        => 'Velvet Rose Perfume Oil',
                'slug'        => 'velvet-rose-perfume-oil',
                'description' => 'Our top-rated perfume oil with concentrated rose and vanilla.',
                'price'       => 49.99,
                'sale_price'  => 39.99,
                'gender'      => 'female',
                'type'        => 'perfume-oil',
            ],
            'new-arrivals' => [
                'name'        => 'Midnight Oud Intense',
                'slug'        => 'midnight-oud-intense',
                'description' => 'A newly launched intense oud fragrance with smoky notes.',
                'price'       => 129.99,
                'is_new'      => true,
                'gender'      => 'male',
                'type'        => 'eau-de-parfum',
            ],
            'gift-sets' => [
                'name'        => 'Luxury Discovery Set',
                'slug'        => 'luxury-discovery-set',
                'description' => 'A curated gift set of 5 mini fragrances.',
                'price'       => 44.99,
                'gender'      => 'unisex',
                'type'        => 'gift',
            ],
            'travel-size' => [
                'name'        => 'Travel Trio Set',
                'slug'        => 'travel-trio-set',
                'description' => 'Three 10ml travel-friendly sprays in a carry case.',
                'price'       => 29.99,
                'gender'      => 'unisex',
                'type'        => 'travel',
            ],
        ];

        $categories = Category::whereIn('slug', array_keys($products))->get()->keyBy('slug');

        foreach ($products as $slug => $data) {
            $category = $categories->get($slug);
            if (! $category) {
                continue;
            }

            Product::create(array_merge($data, [
                'category_id' => $category->id,
                'stock'       => 50,
                'image'       => 'products/' . $data['slug'] . '.jpg',
            ]));
        }
    }
}
