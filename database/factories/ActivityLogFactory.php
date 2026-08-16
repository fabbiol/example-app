<?php

namespace Database\Factories;

use App\Enums\ActivityAction;
use App\Enums\ActivityDomain;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'domain' => ActivityDomain::Operational,
            'action' => ActivityAction::Created,
            'subject_type' => (new Order)->getMorphClass(),
            'subject_id' => fake()->numberBetween(1, 99),
            'description' => 'Criou o pedido #'.fake()->numberBetween(1, 99),
            'properties' => [],
        ];
    }

    public function operational(): static
    {
        return $this->state(fn (array $attributes) => [
            'domain' => ActivityDomain::Operational,
        ]);
    }

    public function administrative(): static
    {
        return $this->state(fn (array $attributes) => [
            'domain' => ActivityDomain::Administrative,
            'action' => ActivityAction::Updated,
            'description' => 'Atualizou o produto '.fake()->word(),
        ]);
    }
}
