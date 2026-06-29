<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Runs before every policy check.
     * If admin → bypass everything and grant access.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->is_admin) {
            return true; // admin passes ALL checks immediately
        }

        return null; // non-admin falls through to individual checks below
    }

    public function viewAny(User $user): bool
    {
        return true; // any authenticated user can list their own orders
    }

    public function view(User $user, Order $order): bool
    {
        return $user->id === $order->user_id; // owner only
    }

    public function update(User $user, Order $order): bool
    {
        return $user->id === $order->user_id; // owner only
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->id === $order->user_id; // owner only
    }
}
