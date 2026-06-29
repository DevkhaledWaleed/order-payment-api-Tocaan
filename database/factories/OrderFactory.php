<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'total'   => 0, // Recalculated in configure()
            'status'  => $this->faker->randomElement(['pending', 'confirmed', 'cancelled']),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (\App\Models\Order $order) {
            $items = \App\Models\OrderItem::factory()->count(1)->create(['order_id' => $order->id]);
            $order->update(['total' => $items->sum(fn ($i) => $i->quantity * $i->price)]);
        });
    }

    public function confirmed(): static
    {
        return $this->state(['status' => 'confirmed']);
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => 'cancelled']);
    }
}
