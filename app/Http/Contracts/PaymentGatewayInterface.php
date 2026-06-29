<?php

namespace App\Http\Contracts;

use App\DTOs\GatewayResultDTO;
use App\Models\Payment;

/**
 * Contract for all payment gateway implementations.
 *
 * To add a new gateway:
 *  1. Create a class that implements this interface
 *  2. Register it in config/payment_gateways under the new key
 *  3. NO controller or service changes required
 */
interface PaymentGatewayInterface
{
    /**
     * Process the given payment through this gateway
     * Returns a typed DTO instead of a raw array for full type safety
     *
     * @param  Payment  $payment  The payment model (pre-saved, status = pending)
     */
    public function process(Payment $payment): GatewayResultDTO;

    /**
     * Return the name of this gateway (matches config key)
     */
    public function getName(): string;
}
