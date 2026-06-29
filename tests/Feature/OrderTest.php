<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = auth('api')->login($this->user);
    }

    private function auth(): static
    {
        return $this->withToken($this->token);
    }

    // Create

    public function test_user_can_create_an_order(): void
    {
        $response = $this->auth()->postJson('/api/orders', [
            'items' => [
                ['name' => 'Widget A', 'quantity' => 2, 'price' => 25.00],
                ['name' => 'Widget B', 'quantity' => 1, 'price' => 10.00],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
        ]);

        $this->assertEquals(60.00, $response->json('data.total'));
    }

    public function test_order_creation_requires_items(): void
    {
        $this->auth()->postJson('/api/orders', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_order_total_is_auto_calculated(): void
    {
        $response = $this->auth()->postJson('/api/orders', [
            'items' => [
                ['name' => 'Product', 'quantity' => 3, 'price' => 10.00],
            ],
        ]);

        $this->assertEquals(30.00, $response->json('data.total'));
    }

    // List

    public function test_user_can_list_orders(): void
    {
        Order::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->auth()->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'meta', 'links']);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_user_can_filter_orders_by_status(): void
    {
        Order::factory()->create(['user_id' => $this->user->id, 'status' => 'confirmed']);
        Order::factory()->create(['user_id' => $this->user->id, 'status' => 'pending']);

        $response = $this->auth()->getJson('/api/orders?status=confirmed');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('confirmed', $response->json('data.0.status'));
    }

    public function test_filter_rejects_invalid_status(): void
    {
        $this->auth()->getJson('/api/orders?status=invalid')
            ->assertStatus(422);
    }

    // Show

    public function test_user_can_view_single_order(): void
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);

        $this->auth()->getJson("/api/orders/{$order->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $order->id);
    }

    // Update

    public function test_user_can_update_order_status(): void
    {
        $order = Order::factory()->create(['user_id' => $this->user->id, 'status' => 'pending']);

        $this->auth()->putJson("/api/orders/{$order->id}", ['status' => 'confirmed'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'confirmed');
    }

    public function test_updating_items_recalculates_total(): void
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);

        $response = $this->auth()->putJson("/api/orders/{$order->id}", [
            'items' => [
                ['name' => 'New Item', 'quantity' => 5, 'price' => 20.00],
            ],
        ]);

        $this->assertEquals(100.00, $response->json('data.total'));
    }

    // Delete

    public function test_user_can_delete_order_without_payments(): void
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);

        $this->auth()->deleteJson("/api/orders/{$order->id}")
            ->assertStatus(200)
            ->assertJsonFragment(['message' => 'Order deleted successfully.']);

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    public function test_order_with_payments_cannot_be_deleted(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'confirmed',
        ]);

        Payment::factory()->create(['order_id' => $order->id]);

        $this->auth()->deleteJson("/api/orders/{$order->id}")
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Order cannot be deleted because it has associated payments.']);

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }

    // Auth Guard

    public function test_unauthenticated_user_cannot_access_orders(): void
    {
        auth('api')->logout();
        $this->getJson('/api/orders')->assertUnauthorized();
    }
}
