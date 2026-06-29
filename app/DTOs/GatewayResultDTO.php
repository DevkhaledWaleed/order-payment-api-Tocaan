<?php

namespace App\DTOs;

/**
 * Represents the result returned by any payment gateway.
 * Replaces the raw `array` return type with a typed, immutable value object.
 *
 * Benefits over raw arrays:
 *  - `$result->success` instead of `$result['success']` — no typos, no missing key bugs
 *  - Every gateway is forced by the interface to return the same shape
 *  - PHPStan / IDE can verify property access at analysis time
 */
final readonly class GatewayResultDTO
{
    public function __construct(
        public bool   $success,
        public string $transactionId,
        public string $message,
        public array  $raw,
    ) {}
}
