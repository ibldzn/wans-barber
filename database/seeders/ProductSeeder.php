<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Barbershop' => [
                'category_type' => 'service',
                'commission_rate_regular' => 0.40,
                'commission_rate_callout' => 0.40,
            ],
            'Reflexology' => [
                'category_type' => 'service',
                'commission_rate_regular' => 0.50,
                'commission_rate_callout' => 0.50,
            ],
            'Retail' => [
                'category_type' => 'retail',
                'commission_rate_regular' => 0.00,
                'commission_rate_callout' => 0.00,
            ],
            'Consumable' => [
                'category_type' => 'consumable',
                'commission_rate_regular' => 0.00,
                'commission_rate_callout' => 0.00,
            ],
        ];

        $products = [
            'Barbershop' => [
                ['name' => 'Haircut Dewasa', 'price' => 60000],
                ['name' => 'Haircut Anak', 'price' => 50000],
                ['name' => 'Hair Colouring', 'price' => 60000, 'price_other' => 110000],
                ['name' => 'Jasa Hair Colouring', 'price' => 60000],
                ['name' => 'Shaving', 'price' => 25000],
                ['name' => 'Keramas + Vit', 'price' => 20000],
                ['name' => 'Royal Cut', 'price' => 90000],
                ['name' => 'Luxury Cut', 'price' => 130000],
            ],
            'Reflexology' => [
                ['name' => 'Reflexy 1 Jam', 'price' => 100000, 'price_other' => 150000],
                ['name' => 'Reflexy 1,5 Jam', 'price' => 125000, 'price_other' => 175000],
                ['name' => 'Massage 1 Jam', 'price' => 100000, 'price_other' => 150000],
                ['name' => 'Massage 1,5 Jam', 'price' => 125000, 'price_other' => 175000],
                ['name' => 'Kombinasi', 'price' => 125000, 'price_other' => 175000],
                ['name' => 'Kop/Kerokan', 'price' => 50000, 'price_other' => 100000],
                ['name' => 'Bekam', 'price' => 120000, 'price_other' => 170000],
                ['name' => 'Totok Wajah', 'price' => 50000, 'price_other' => 100000],
                ['name' => 'Terapi Telinga', 'price' => 60000, 'price_other' => 110000],
            ],
        ];

        foreach ($categories as $name => $meta) {
            ProductCategory::updateOrCreate(
                ['category_name' => $name],
                [
                    'category_description' => null,
                    'category_type' => $meta['category_type'],
                    'commission_rate_regular' => $meta['commission_rate_regular'],
                    'commission_rate_callout' => $meta['commission_rate_callout'],
                ]
            );
        }

        foreach ($products as $categoryName => $items) {
            $category = ProductCategory::where('category_name', $categoryName)->first();

            if (! $category) {
                continue;
            }

            foreach ($items as $item) {
                Product::updateOrCreate(
                    [
                        'product_name' => $item['name'],
                        'category_id' => $category->id,
                    ],
                    [
                        'product_price' => $item['price'],
                        'product_price_other' => $item['price_other'] ?? null,
                        'product_description' => null,
                        'product_type' => 'service',
                        'track_stock' => false,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
