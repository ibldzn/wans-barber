<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            ['method_name' => 'Cash', 'method_description' => null],
            ['method_name' => 'QRIS', 'method_description' => null],
            ['method_name' => 'Transfer', 'method_description' => null],
            ['method_name' => 'EDC', 'method_description' => null],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(
                ['method_name' => $method['method_name']],
                [
                    'method_description' => $method['method_description'],
                    'is_active' => true,
                ]
            );
        }
    }
}
