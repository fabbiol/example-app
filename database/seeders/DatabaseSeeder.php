<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        User::query()->updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Ana Costa',
                'password' => 'password',
                'role_id' => Role::administrative()->id,
                'email_verified_at' => now(),
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'operador@example.com'],
            [
                'name' => 'Carlos Mendes',
                'password' => 'password',
                'role_id' => Role::operator()->id,
                'email_verified_at' => now(),
            ],
        );

        $this->call([
            ProductSeeder::class,
            CustomerSeeder::class,
            TruckSeeder::class,
            CrushingCircuitSeeder::class,
        ]);
    }
}
