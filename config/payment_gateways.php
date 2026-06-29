<?php

use App\PaymentGateways\CreditCardGateway;
use App\PaymentGateways\PayPalGateway;
use App\PaymentGateways\StripeGateway;

return [

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways Map
    |--------------------------------------------------------------------------
    | Maps payment method keys (used in API requests) to their gateway classes.
    | To add a new gateway:
    |   1. Create a class implementing App\Contracts\PaymentGatewayInterface
    |   2. Add its key => class mapping here
    |   3. Done — no other code changes required.
    |
    */

    'gateways' => [
        'credit_card' => CreditCardGateway::class,
        'paypal' => PayPalGateway::class,
        'stripe' => StripeGateway::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Gateway Credentials (loaded from .env)
    |--------------------------------------------------------------------------
    */

    'credit_card' => [
        'api_key' => env('CREDIT_CARD_API_KEY', 'test_key'),
        'api_secret' => env('CREDIT_CARD_API_SECRET', 'test_secret'),
    ],

    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID', 'test_client_id'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET', 'test_client_secret'),
        'mode' => env('PAYPAL_MODE', 'sandbox'),
    ],

    'stripe' => [
        'publishable_key' => env('STRIPE_PUBLISHABLE_KEY', 'pk_test_demo'),
        'secret_key' => env('STRIPE_SECRET_KEY', 'sk_test_demo'),
    ],

];
