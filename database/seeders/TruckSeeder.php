<?php

namespace Database\Seeders;

use App\Models\Truck;
use Illuminate\Database\Seeder;

class TruckSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $trucks = [
            ['name' => 'Basculante 01', 'plate' => 'LAV-0001', 'capacity_m3' => 10.0],
            ['name' => 'Basculante 02', 'plate' => 'LAV-0002', 'capacity_m3' => 12.0],
            ['name' => 'Basculante 03', 'plate' => 'LAV-0003', 'capacity_m3' => 14.0],
        ];

        foreach ($trucks as $truck) {
            Truck::query()->updateOrCreate(
                ['plate' => $truck['plate']],
                [
                    'name' => $truck['name'],
                    'capacity_m3' => $truck['capacity_m3'],
                    'is_active' => true,
                ],
            );
        }
    }
}
