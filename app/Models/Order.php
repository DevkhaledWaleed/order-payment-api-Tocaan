<?php

namespace App\Models;

use App\Http\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total',
        'status',
    ];

    /**
     * Interact with the order status.
     *  string values ('pending') to integer backend, and retrieves it as an OrderStatus Enum instance.
     */
    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn ($status) => is_numeric($status) ? OrderStatus::from((int)$status) : OrderStatus::fromString($status),
            set: fn ($status) => $status instanceof OrderStatus ? $status->value : OrderStatus::fromString($status)?->value,
        );
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeByStatus(Builder $query, $status): Builder
    {
        if ($status !== null) {
            if ($status instanceof OrderStatus) {
                $query->where('status', $status->value);
            } else {
                $enumStatus = OrderStatus::fromString((string) $status);
                if ($enumStatus !== null) {
                    $query->where('status', $enumStatus->value);
                } else {
                    // Fail query or return empty if invalid status string is passed
                    $query->whereRaw('1 = 0');
                }
            }
        }

        return $query;
    }

    // ── Business Logic ────────────────────────────────────────────────────────

    public function hasPayments(): bool
    {
        return $this->payments()->exists();
    }

    public function isConfirmed(): bool
    {
        return $this->status === OrderStatus::CONFIRMED;
    }
}
