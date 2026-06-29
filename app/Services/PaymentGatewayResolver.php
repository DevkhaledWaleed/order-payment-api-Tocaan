<?php

namespace App\Services;

use App\Http\Contracts\PaymentGatewayInterface;
use InvalidArgumentException;

/**
 * Resolves a payment method string to the appropriate gateway implementation.
 *
 * The gateway map is driven entirely by config/payment_gateways.php.
 * No code changes here are needed when adding a new gateway.
 */
class PaymentGatewayResolver
{
    /**
     * Resolve a gateway by its method key.
     *
     * @param  string  $method  e.g. 'credit_card', 'paypal', 'stripe'
     *
     * @throws InvalidArgumentException if the method is not registered
     */
    public function resolve(string $method): PaymentGatewayInterface
    {
        $gateways = config('payment_gateways.gateways', []);

        if (! array_key_exists($method, $gateways)) {
            throw new InvalidArgumentException(
                "Payment gateway [{$method}] is not registered. ".
                'Add it to config/payment_gateways.php.'
            );
        }

        $gatewayClass = $gateways[$method];

        return app($gatewayClass);
    }

    /**
     * Return all registered payment method keys.
     *
     * @return string[]
     */
    public function availableMethods(): array
    {
        return array_keys(config('payment_gateways.gateways', []));
    }
}
