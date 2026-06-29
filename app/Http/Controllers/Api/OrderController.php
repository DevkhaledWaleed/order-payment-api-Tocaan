<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use LogicException;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    /**
     * GET /api/orders
     * List all orders, optionally filtered by ?status=pending|confirmed|cancelled
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'status'   => ['nullable', 'in:pending,confirmed,cancelled'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $orders = $this->orderService->listOrders(
            $request->query('status'),
            (int) $request->query('per_page', 15)
        );

        return OrderResource::collection($orders);
    }

    /**
     * POST /api/orders
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $data            = $request->validated();
        $data['user_id'] = Auth::id();

        $order = $this->orderService->createOrder($data);
        $order->load('user');

        return response()->json([
            'message' => 'Order created successfully.',
            'data'    => new OrderResource($order),
        ], 201);
    }

    /**
     * GET /api/orders/{order}
     */
    public function show(Order $order): JsonResponse
    {
        $order->load('user', 'payments');

        return response()->json([
            'data' => new OrderResource($order),
        ]);
    }

    /**
     * PUT /api/orders/{order}
     */
    public function update(UpdateOrderRequest $request, Order $order): JsonResponse
    {
        if ($order->user_id !== $request->user()->id) {
            abort(403);
        }
        $updated = $this->orderService->updateOrder($order, $request->validated());
        $updated->load('user');

        return response()->json([
            'message' => 'Order updated successfully.',
            'data'    => new OrderResource($updated),
        ]);
    }

    /**
     * DELETE /api/orders/{order}
     */
    public function destroy(Order $order): JsonResponse
    {
        try {
            $this->orderService->deleteOrder($order);
        } catch (LogicException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Order deleted successfully.',
        ]);
    }
}
