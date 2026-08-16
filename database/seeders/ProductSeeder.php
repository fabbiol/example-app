<?php

namespace Database\Seeders;

use App\Enums\ProductUnit;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            ['name' => 'Rocha detonada', 'code' => 'ROCHA', 'density' => 1.50, 'bucket_capacity_m3' => 1.60],
            ['name' => 'Brita 0', 'code' => 'BRITA-0', 'density' => 1.45, 'bucket_capacity_m3' => 1.50],
            ['name' => 'Brita 1', 'code' => 'BRITA-1', 'density' => 1.45, 'bucket_capacity_m3' => 1.50],
            ['name' => 'Brita 2', 'code' => 'BRITA-2', 'density' => 1.45, 'bucket_capacity_m3' => 1.50],
            ['name' => 'Brita 3', 'code' => 'BRITA-3', 'density' => 1.45, 'bucket_capacity_m3' => 1.50],
            ['name' => 'Brita 4', 'code' => 'BRITA-4', 'density' => 1.45, 'bucket_capacity_m3' => 1.50],
            ['name' => 'Pedrisco', 'code' => 'PEDRISCO', 'density' => 1.45, 'bucket_capacity_m3' => 1.50],
            ['name' => 'Pó de pedra', 'code' => 'PO-PEDRA', 'density' => 1.40, 'bucket_capacity_m3' => 1.40],
            ['name' => 'Rachão', 'code' => 'RACHAO', 'density' => 1.50, 'bucket_capacity_m3' => 1.60],
            ['name' => 'Bica corrida', 'code' => 'BICA', 'density' => 1.48, 'bucket_capacity_m3' => 1.50],
        ];

        foreach ($products as $product) {
            $model = Product::query()->firstOrNew(['code' => $product['code']]);

            $model->fill([
                'name' => $product['name'],
                'unit' => ProductUnit::Ton,
                'density' => $product['density'],
                'bucket_capacity_m3' => $product['bucket_capacity_m3'],
                'is_active' => true,
            ]);

            if (! $model->exists) {
                $model->stock_quantity = 0;
            }

            $model->save();
        }
    }
}
