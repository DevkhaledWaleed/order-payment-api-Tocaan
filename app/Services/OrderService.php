<?php

namespace App\Services;

use App\Http\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use LogicException;

class OrderService
{
    /**
     * List orders for the authenticated user, optionally filtered by status.
     */
    public function listOrders(?string $status, int $perPage = 15): LengthAwarePaginator
    {
        return Order::with('user')
            ->byStatus($status)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Create a new order and calculate the total from items.
     *
     */
    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $data['total']  = $this->calculateTotal($data['items']);

            $statusStr = $data['status'] ?? 'pending';
            $data['status'] = OrderStatus::fromString($statusStr) ?? OrderStatus::PENDING;

            return Order::create($data);
        });
    }

    /**
     * Update an existing order.
     *
     */
    public function updateOrder(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data) {
            if (isset($data['items'])) {
                $data['total'] = $this->calculateTotal($data['items']);
            }

            if (isset($data['status']) && is_string($data['status'])) {
                $data['status'] = OrderStatus::fromString($data['status']);
            }

            $order->update($data);

            return $order->fresh();
        });
    }

    /**
     * Delete an order — only if no payments are associated.
     *
     */
    public function deleteOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            if ($order->hasPayments()) {
                throw new LogicException(
                    'Order cannot be deleted because it has associated payments.'
                );
            }

            $order->delete();
        });
    }

    // ── Private Helpers ───────────────────────────────────────────────────────

    /**
     * Calculate the total from an items array.
     *
     */
    private function calculateTotal(array $items): float
    {
        return collect($items)->sum(
            fn (array $item) => $item['quantity'] * $item['price']
        );
    }
}
