<?php

namespace Database\Seeders;

use App\Models\ApiProduct;
use Illuminate\Database\Seeder;

class CommerceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name' => 'Tumbler Rimba',
                'description' => 'Botol minum pakai ulang untuk menemani aktivitas harian.',
                'price' => '129000.00',
                'stock_quantity' => 12,
                'category' => 'Lifestyle',
                'is_active' => true,
            ],
            [
                'name' => 'Tas Kain Senja',
                'description' => 'Tas serbaguna ringan untuk belanja dan bepergian.',
                'price' => '89000.00',
                'stock_quantity' => 8,
                'category' => 'Aksesori',
                'is_active' => true,
            ],
            [
                'name' => 'Kopi Pagi',
                'description' => 'Kopi pilihan untuk memulai hari dengan lebih tenang.',
                'price' => '65000.00',
                'stock_quantity' => 20,
                'category' => 'Makanan & Minuman',
                'is_active' => true,
            ],
            [
                'name' => 'Sabun Alami',
                'description' => 'Sabun lembut untuk kebutuhan perawatan sehari-hari.',
                'price' => '42000.00',
                'stock_quantity' => 4,
                'category' => 'Perawatan',
                'is_active' => true,
            ],
        ];

        foreach ($products as $product) {
            ApiProduct::query()->firstOrCreate(
                ['name' => $product['name']],
                $product
            );
        }
    }
}
