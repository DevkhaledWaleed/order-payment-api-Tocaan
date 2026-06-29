<?php

namespace App\Models;

use App\Http\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'payment_method',
        'status',
        'gateway_response',
        'idempotency_key',
    ];

    protected $casts = [
        'gateway_response' => 'array',
    ];

    /**
     * Interact with the payment status.
     * Maps string values ('pending') to integer backend, and retrieves as PaymentStatus Enum.
     */
    protected function status(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($value instanceof PaymentStatus) {
                    return $value;
                }
                if (is_numeric($value)) {
                    return PaymentStatus::from((int) $value);
                }
                if (is_string($value)) {
                    return PaymentStatus::fromString($value) ?? PaymentStatus::PENDING;
                }

                return $value;
            },
            set: function ($value) {
                if ($value instanceof PaymentStatus) {
                    return $value->value;
                }
                if (is_numeric($value)) {
                    return (int) $value;
                }
                if (is_string($value)) {
                    $enum = PaymentStatus::fromString($value);
                    if ($enum !== null) {
                        return $enum->value;
                    }
                }

                return $value;
            }
        );
    }

    // Relationships

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
