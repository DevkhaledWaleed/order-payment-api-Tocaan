<?php

namespace App\Http\DTOs;

use App\Http\Requests\Payment\ProcessPaymentRequest;

final readonly class ProcessPaymentDTO
{
    public function __construct(
        public int     $orderId,
        public string  $paymentMethod,
        public ?string $idempotencyKey = null,
    ) {}

    /**
     * Create from an incoming HTTP request.
     * This is the only place where we read headers and validated data together.
     */
    public static function fromRequest(ProcessPaymentRequest $request): self
    {
        return new self(
            orderId:        $request->integer('order_id'),
            paymentMethod:  $request->validated('payment_method'),
            idempotencyKey: $request->header('Idempotency-Key'),
        );
    }
}
