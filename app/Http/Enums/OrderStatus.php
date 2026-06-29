<?php

namespace App\Http\Enums;

enum OrderStatus: int
{
    case PENDING = 0;
    case CONFIRMED = 1;
    case CANCELLED = 2;

    public static function fromString(string $status): ?self
    {
        return match (strtolower($status)) {
            'pending' => self::PENDING,
            'confirmed' => self::CONFIRMED,
            'cancelled' => self::CANCELLED,
            default => null,
        };
    }

    public function toString(): string
    {
        return match ($this) {
            self::PENDING => 'pending',
            self::CONFIRMED => 'confirmed',
            self::CANCELLED => 'cancelled',
        };
    }
}
