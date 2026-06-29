<?php

namespace Tests\Unit;

use App\DTOs\GatewayResultDTO;
use App\Models\Order;
use App\Models\Payment;
use App\PaymentGateways\CreditCardGateway;
use App\PaymentGateways\PayPalGateway;
use App\PaymentGateways\StripeGateway;
use Tests\TestCase;

class PaymentGatewayTest extends TestCase
{
    private function makeFakePayment(): Payment
    {
        $order = new Order(['total' => 99.99]);
        $order->id = 1;

        $payment = new Payment([
            'order_id'       => 1,
            'payment_method' => 'credit_card',
            'status'         => 'pending',
        ]);
        $payment->id = 1;
        $payment->setRelation('order', $order);

        return $payment;
    }

    // ── CreditCardGateway ─────────────────────────────────────────────────────

    public function test_credit_card_gateway_has_correct_name(): void
    {
        $gateway = new CreditCardGateway();
        $this->assertSame('credit_card', $gateway->getName());
    }

    public function test_credit_card_gateway_processes_payment_successfully(): void
    {
        $gateway = new CreditCardGateway();
        $result  = $gateway->process($this->makeFakePayment());

        $this->assertInstanceOf(GatewayResultDTO::class, $result);
        $this->assertTrue($result->success);
        $this->assertStringStartsWith('CC-', $result->transactionId);
        $this->assertIsArray($result->raw);
        $this->assertSame('credit_card', $result->raw['gateway']);
    }

    // ── PayPalGateway ─────────────────────────────────────────────────────────

    public function test_paypal_gateway_has_correct_name(): void
    {
        $gateway = new PayPalGateway();
        $this->assertSame('paypal', $gateway->getName());
    }

    public function test_paypal_gateway_processes_payment_successfully(): void
    {
        $gateway = new PayPalGateway();
        $result  = $gateway->process($this->makeFakePayment());

        $this->assertInstanceOf(GatewayResultDTO::class, $result);
        $this->assertTrue($result->success);
        $this->assertStringStartsWith('PAYPAL-', $result->transactionId);
        $this->assertIsArray($result->raw);
        $this->assertSame('paypal', $result->raw['gateway']);
    }

    // ── StripeGateway ─────────────────────────────────────────────────────────

    public function test_stripe_gateway_has_correct_name(): void
    {
        $gateway = new StripeGateway();
        $this->assertSame('stripe', $gateway->getName());
    }

    public function test_stripe_gateway_processes_payment_successfully(): void
    {
        $gateway = new StripeGateway();
        $result  = $gateway->process($this->makeFakePayment());

        $this->assertInstanceOf(GatewayResultDTO::class, $result);
        $this->assertTrue($result->success);
        $this->assertStringStartsWith('ch_', $result->transactionId);
        $this->assertIsArray($result->raw);
        $this->assertSame('stripe', $result->raw['gateway']);
        // Stripe stores amount in cents
        $this->assertSame(9999, $result->raw['amount']);
    }
}
