<?php

namespace App\Http\Enums;

enum PaymentStatus: int
{
    case PENDING = 0;
    case SUCCESSFUL = 1;
    case FAILED = 2;

    /**
     * Map a string representation to the corresponding Enum instance.
     */
    public static function fromString(string $status): ?self
    {
        return match (strtolower($status)) {
            'pending' => self::PENDING,
            'successful' => self::SUCCESSFUL,
            'failed' => self::FAILED,
            default => null,
        };
    }

    /**
     * Convert the Enum instance to its string representation.
     */
    public function toString(): string
    {
        return match ($this) {
            self::PENDING => 'pending',
            self::SUCCESSFUL => 'successful',
            self::FAILED => 'failed',
        };
    }
}
