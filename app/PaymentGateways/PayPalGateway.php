<?php

namespace App\PaymentGateways;

use App\Http\Contracts\PaymentGatewayInterface;
use App\DTOs\GatewayResultDTO;
use App\Models\Payment;
use Illuminate\Support\Str;

class PayPalGateway implements PaymentGatewayInterface
{
    public function getName(): string
    {
        return 'paypal';
    }

    public function process(Payment $payment): GatewayResultDTO
    {
        // Simulate PayPal payment processing
        $transactionId = 'PAYPAL-' . strtoupper(Str::random(10));

        $success = true;

        return new GatewayResultDTO(
            success:       $success,
            transactionId: $transactionId,
            message:       $success
                ? 'PayPal payment completed successfully.'
                : 'PayPal payment failed.',
            raw: [
                'gateway'        => 'paypal',
                'transaction_id' => $transactionId,
                'payer_id'       => 'PAYER-' . strtoupper(Str::random(8)),
                'order_id'       => $payment->order_id,
                'amount'         => $payment->order->total,
                'currency'       => 'USD',
                'status'         => $success ? 'COMPLETED' : 'FAILED',
                'mode'           => config('payment_gateways.paypal.mode', 'sandbox'),
                'processed_at'   => now()->toIso8601String(),
            ],
        );
    }
}
