<?php

namespace App\DTOs;

use App\Http\Requests\Payment\ProcessPaymentRequest;

/**
 * Represents the data needed to initiate a payment.
 * Created from the HTTP request in the controller and passed to the service.
 *
 * Benefits over raw arrays:
 *  - Full type safety and IDE autocompletion
 *  - Self-documenting: the constructor shows exactly what data is required
 *  - The `fromRequest()` factory keeps all HTTP-layer logic out of the service
 */
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
