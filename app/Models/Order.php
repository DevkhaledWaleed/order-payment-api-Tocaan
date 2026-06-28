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
        'items',
        'total',
        'status',
    ];

    protected $casts = [
        'items'  => 'array',
        'total'  => 'float',
    ];

    /**
     * Interact with the order status.
     *  string values ('pending') to integer backend, and retrieves it as an OrderStatus Enum instance.
     */
    protected function status(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($value instanceof OrderStatus) {
                    return $value;
                }
                if (is_numeric($value)) {
                    return OrderStatus::from((int) $value);
                }
                if (is_string($value)) {
                    return OrderStatus::fromString($value) ?? OrderStatus::PENDING;
                }
                return $value;
            },
            set: function ($value) {
                if ($value instanceof OrderStatus) {
                    return $value->value;
                }
                if (is_numeric($value)) {
                    return (int) $value;
                }
                if (is_string($value)) {
                    $enum = OrderStatus::fromString($value);
                    if ($enum !== null) {
                        return $enum->value;
                    }
                }
                return $value;
            }
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
