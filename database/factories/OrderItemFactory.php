<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'name'     => $this->faker->word() . ' Product',
            'quantity' => $this->faker->numberBetween(1, 5),
            'price'    => $this->faker->randomFloat(2, 5, 100),
        ];
    }
}
