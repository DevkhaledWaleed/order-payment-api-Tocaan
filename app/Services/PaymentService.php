<?php

namespace App\Services;

use App\DTOs\ProcessPaymentDTO;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use LogicException;

class PaymentService
{
    public function __construct(
        private readonly PaymentGatewayResolver $resolver
    ) {}

    /**
     * List all payments, optionally filtered by order_id.
     */
    public function listPayments(?int $orderId, int $perPage = 15): LengthAwarePaginator
    {
        return Payment::with('order')
            ->when($orderId, fn ($q) => $q->where('order_id', $orderId))
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Process a payment for an order.
     *
     * Idempotency: if $dto->idempotencyKey is provided and a payment with the same
     * key already exists, the existing payment is returned immediately — no
     * duplicate charge is made.
     *
     * Transactions: the entire operation (create record → call gateway →
     * update status) is wrapped in a DB transaction so a partial failure
     * cannot leave the database in an inconsistent state.
     *
     * @throws LogicException if the order is not confirmed
     */
    public function processPayment(ProcessPaymentDTO $dto): Payment
    {
        // ── Idempotency check ────────────────────────────────────────────────
        // If the client already sent this key, return the cached result.
        if ($dto->idempotencyKey !== null) {
            $existing = Payment::with('order')
                ->where('idempotency_key', $dto->idempotencyKey)
                ->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        // ── Transactional processing ─────────────────────────────────────────
        // All writes are inside a transaction so a gateway/DB failure rolls
        // everything back instead of leaving a stale `pending` record.

        //Business rule & Locking 
        // We lock the order first to ensure any concurrent updates on the order finish first.
        return DB::transaction(function () use ($dto) {
        $order = Order::where('id', $dto->orderId)->lockForUpdate()->firstOrFail();

        if (! $order->isConfirmed()) {
            throw new LogicException(
                'Payments can only be processed for orders with status [confirmed]. ' .
                "Current status: [{$order->status->toString()}]."
            );
        }

            // 1. Create payment record (pending)
            $payment = Payment::create([
                'order_id'        => $order->id,
                'payment_method'  => $dto->paymentMethod,
                'status'          => 'pending',
                'idempotency_key' => $dto->idempotencyKey,
            ]);

            // 2. Load the order relation for gateway use
            $payment->load('order');

            // 3. Resolve the correct gateway and process — returns a GatewayResultDTO
            $gateway = $this->resolver->resolve($dto->paymentMethod);
            $result  = $gateway->process($payment);

            // 4. Update payment using typed DTO properties (no magic array keys)
            $payment->update([
                'status'           => $result->success ? 'successful' : 'failed',
                'gateway_response' => $result->raw,
            ]);

            return $payment->fresh('order');
        });
    }
}
