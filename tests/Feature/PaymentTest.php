<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;
    private Order $confirmedOrder;
    private Order $pendingOrder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user  = User::factory()->create();
        $this->token = auth('api')->login($this->user);

        $this->confirmedOrder = Order::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'confirmed',
            'total'   => 150.00,
        ]);

        $this->pendingOrder = Order::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'pending',
            'total'   => 50.00,
        ]);
    }

    private function auth(): static
    {
        return $this->withToken($this->token);
    }

    // ── Process Payment ───────────────────────────────────────────────────────

    public function test_can_process_payment_with_credit_card(): void
    {
        $response = $this->auth()->postJson('/api/payments', [
            'order_id'       => $this->confirmedOrder->id,
            'payment_method' => 'credit_card',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'successful')
            ->assertJsonPath('data.payment_method', 'credit_card')
            ->assertJsonStructure([
                'data' => [
                    'id', 'order_id', 'payment_method', 'status', 'gateway_response', 'created_at',
                ],
            ]);

        $this->assertDatabaseHas('payments', [
            'order_id'       => $this->confirmedOrder->id,
            'payment_method' => 'credit_card',
            'status'         => 1,
        ]);
    }

    public function test_can_process_payment_with_paypal(): void
    {
        $response = $this->auth()->postJson('/api/payments', [
            'order_id'       => $this->confirmedOrder->id,
            'payment_method' => 'paypal',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'successful')
            ->assertJsonPath('data.payment_method', 'paypal');
    }

    public function test_can_process_payment_with_stripe(): void
    {
        $response = $this->auth()->postJson('/api/payments', [
            'order_id'       => $this->confirmedOrder->id,
            'payment_method' => 'stripe',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'successful')
            ->assertJsonPath('data.payment_method', 'stripe');
    }

    public function test_cannot_process_payment_for_pending_order(): void
    {
        $response = $this->auth()->postJson('/api/payments', [
            'order_id'       => $this->pendingOrder->id,
            'payment_method' => 'credit_card',
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Payments can only be processed for orders with status [confirmed]. Current status: [pending].']);
    }

    public function test_payment_requires_valid_order_id(): void
    {
        $this->auth()->postJson('/api/payments', [
            'order_id'       => 99999,
            'payment_method' => 'credit_card',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['order_id']);
    }

    public function test_payment_rejects_unknown_gateway(): void
    {
        $this->auth()->postJson('/api/payments', [
            'order_id'       => $this->confirmedOrder->id,
            'payment_method' => 'bitcoin',
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['payment_method']);
    }

    // ── List Payments ─────────────────────────────────────────────────────────

    public function test_can_list_all_payments(): void
    {
        Payment::factory()->count(3)->create(['order_id' => $this->confirmedOrder->id]);

        $this->auth()->getJson('/api/payments')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'meta', 'links']);
    }

    public function test_can_filter_payments_by_order_id(): void
    {
        $otherOrder = Order::factory()->create(['user_id' => $this->user->id, 'status' => 'confirmed']);

        Payment::factory()->count(2)->create(['order_id' => $this->confirmedOrder->id]);
        Payment::factory()->count(1)->create(['order_id' => $otherOrder->id]);

        $response = $this->auth()->getJson("/api/payments?order_id={$this->confirmedOrder->id}");

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    // ── Show Payment ──────────────────────────────────────────────────────────

    public function test_can_view_single_payment(): void
    {
        $payment = Payment::factory()->create(['order_id' => $this->confirmedOrder->id]);

        $this->auth()->getJson("/api/payments/{$payment->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.id', $payment->id);
    }

    // ── Auth Guard ────────────────────────────────────────────────────────────

    public function test_unauthenticated_user_cannot_access_payments(): void
    {
        auth('api')->logout();
        $this->getJson('/api/payments')->assertUnauthorized();
    }
}
