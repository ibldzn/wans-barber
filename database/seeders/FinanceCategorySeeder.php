<?php

namespace Database\Seeders;

use App\Models\FinanceCategory;
use Illuminate\Database\Seeder;

class FinanceCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Pendapatan Jasa', 'type' => 'income'],
            ['name' => 'Pendapatan Barang', 'type' => 'income'],
            ['name' => 'Gaji & Makan', 'type' => 'expense'],
            ['name' => 'Komisi Pegawai', 'type' => 'expense'],
            ['name' => 'Operasional', 'type' => 'expense'],
            ['name' => 'Utilities', 'type' => 'expense'],
            ['name' => 'Inventory', 'type' => 'expense'],
            ['name' => 'Lain-lain', 'type' => 'expense'],
        ];

        foreach ($categories as $category) {
            FinanceCategory::updateOrCreate(
                ['name' => $category['name'], 'type' => $category['type']],
                ['is_system' => true, 'description' => null]
            );
        }
    }
}
