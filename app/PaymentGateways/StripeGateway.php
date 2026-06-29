<?php

namespace App\PaymentGateways;

use App\Http\Contracts\PaymentGatewayInterface;
use App\DTOs\GatewayResultDTO;
use App\Models\Payment;
use Illuminate\Support\Str;
/**
 * Stripe Gateway — Demonstrates how easily a new gateway can be added.
 *
 * Steps to add this gateway (or any new one):
 *  1. Create this class implementing PaymentGatewayInterface  ← You are here
 *  2. Register in config/payment_gateways.php: 'stripe' => StripeGateway::class
 *  3. Add credentials to .env: STRIPE_SECRET_KEY=sk_live_xxx
 *
 * Zero changes to any controller, service, or route file.
 */
class StripeGateway implements PaymentGatewayInterface
{
    public function getName(): string
    {
        return 'stripe';
    }

    public function process(Payment $payment): GatewayResultDTO
    {
        // Simulate Stripe charge creation
        $chargeId = 'ch_' . Str::random(24);

        $success = true;

        return new GatewayResultDTO(
            success:       $success,
            transactionId: $chargeId,
            message:       $success
                ? 'Stripe charge created successfully.'
                : 'Stripe charge failed.',
            raw: [
                'gateway'      => 'stripe',
                'charge_id'    => $chargeId,
                'object'       => 'charge',
                'order_id'     => $payment->order_id,
                'amount'       => (int) ($payment->order->total * 100), // Stripe uses cents
                'currency'     => 'usd',
                'status'       => $success ? 'succeeded' : 'failed',
                'livemode'     => false,
                'processed_at' => now()->toIso8601String(),
            ],
        );
    }
}
