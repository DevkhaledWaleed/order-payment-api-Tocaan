<?php

namespace App\Http\Controllers\Api;

use App\DTOs\ProcessPaymentDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\ProcessPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use LogicException;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService) {}

    /**
     * GET /api/payments
     * List all payments. Filter by ?order_id=X
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $payments = $this->paymentService->listPayments(
            $request->query('order_id') ? (int) $request->query('order_id') : null,
            (int) $request->query('per_page', 15)
        );

        return PaymentResource::collection($payments);
    }

    /**
     * POST /api/payments
     * Process a payment for a confirmed order.
     *
     * Clients SHOULD send a unique `Idempotency-Key` header (e.g. a UUID v4)
     * with every request. If the same key is received again, the original
     * response is returned without re-charging the customer.
     */
    public function store(ProcessPaymentRequest $request): JsonResponse
    {
        try {
            $payment = $this->paymentService->processPayment(
                ProcessPaymentDTO::fromRequest($request)
            );
        } catch (LogicException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Payment processed successfully.',
            'data'    => new PaymentResource($payment),
        ], 201);
    }

    /**
     * GET /api/payments/{payment}
     */
    public function show(Payment $payment): JsonResponse
    {
        $payment->load('order');

        return response()->json([
            'data' => new PaymentResource($payment),
        ]);
    }
}
