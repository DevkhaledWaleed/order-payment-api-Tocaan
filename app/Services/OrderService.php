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
        return Order::with(['user', 'items'])
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
            $total = $this->calculateTotal($data['items']);

            $statusStr = $data['status'] ?? 'pending';
            $status = OrderStatus::fromString($statusStr) ?? OrderStatus::PENDING;

            $order = Order::create([
                'user_id' => $data['user_id'],
                'total'   => $total,
                'status'  => $status,
            ]);

            $order->items()->createMany($data['items']);

            return $order->load('items');
        });
    }

    /**
     * Update an existing order.
     *
     */
    public function updateOrder(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data) {
            // Lock the order for update to prevent race conditions
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            if (isset($data['items'])) {
                $lockedOrder->total = $this->calculateTotal($data['items']);

                // Replace items entirely
                $lockedOrder->items()->delete();
                $lockedOrder->items()->createMany($data['items']);
            }

            if (isset($data['status']) && is_string($data['status'])) {
                $lockedOrder->status = OrderStatus::fromString($data['status']);
            }

            $lockedOrder->save();

            return $lockedOrder->load('items');
        });
    }

    /**
     * Delete an order — only if no payments are associated.
     *
     */
    public function deleteOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();
            if ($lockedOrder->hasPayments()) {
                throw new LogicException(
                    'Order cannot be deleted because it has associated payments.'
                );
            }
            $lockedOrder->delete();
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
