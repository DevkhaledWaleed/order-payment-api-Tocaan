<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory()->confirmed(),
            'payment_method' => $this->faker->randomElement(['credit_card', 'paypal', 'stripe']),
            'status' => $this->faker->randomElement(['pending', 'successful', 'failed']),
            'gateway_response' => [
                'gateway' => 'credit_card',
                'transaction_id' => 'CC-'.strtoupper($this->faker->bothify('????????????')),
                'status' => 'approved',
                'processed_at' => now()->toIso8601String(),
            ],
        ];
    }

    public function successful(): static
    {
        return $this->state(['status' => 'successful']);
    }

    public function failed(): static
    {
        return $this->state(['status' => 'failed']);
    }
}
