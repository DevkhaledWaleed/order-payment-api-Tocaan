<?php

namespace App\PaymentGateways;

use App\DTOs\GatewayResultDTO;
use App\Http\Contracts\PaymentGatewayInterface;
use App\Models\Payment;
use Illuminate\Support\Str;

class CreditCardGateway implements PaymentGatewayInterface
{
    public function getName(): string
    {
        return 'credit_card';
    }

    public function process(Payment $payment): GatewayResultDTO
    {
        // Simulate credit card processing
        $transactionId = 'CC-'.strtoupper(Str::random(12));

        $success = true;

        return new GatewayResultDTO(
            success: $success,
            transactionId: $transactionId,
            message: $success
                ? 'Credit card payment processed successfully.'
                : 'Credit card payment declined.',
            raw: [
                'gateway' => 'credit_card',
                'transaction_id' => $transactionId,
                'order_id' => $payment->order_id,
                'amount' => $payment->order->total,
                'currency' => 'USD',
                'status' => $success ? 'approved' : 'declined',
                'processed_at' => now()->toIso8601String(),
            ],
        );
    }
}
