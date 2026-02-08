<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            'Barbershop' => [
                'Haircut Dewasa' => 60_000,
                'Haircut Anak' => 50_000,
                'Hair Color' => 110_000,
                'Jasa Hair Color' => 60_000,
                'Shaving' => 25_000,
                'Keramas + Vit' => 20_000,
            ],
            'Reflexology' => [
                'Reflexy 1 Jam' => 100_000,
                'Reflexy 1,5 Jam' => 125_000,
                'Massage 1 Jam' => 100_000,
                'Massage 1,5 Jam' => 125_000,
                'Kombinasi' => 125_000,
                'Kop / Kerokan' => 50_000,
                'Bekam' => 120_000,
                'Totok Wajah' => 50_000,
                'Terapi Telinga' => 60_000,
            ],
        ];

        foreach ($products as $categoryName => $items) {
            $category = \App\Models\ProductCategory::firstOrCreate(
                ['category_name' => $categoryName],
                ['category_description' => null]
            );

            foreach ($items as $productName => $productPrice) {
                \App\Models\Product::upsert(
                    ['product_name' => $productName, 'category_id' => $category->id],
                    ['product_price' => $productPrice, 'product_description' => null]
                );
            }
        }
    }
}
