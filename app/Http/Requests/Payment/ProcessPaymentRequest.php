<?php

namespace App\Http\Requests\Payment;

use App\Services\PaymentGatewayResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $availableMethods = app(PaymentGatewayResolver::class)->availableMethods();

        return [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'payment_method' => ['required', 'string', Rule::in($availableMethods)],
        ];
    }

    public function messages(): array
    {
        $methods = implode(', ', app(PaymentGatewayResolver::class)->availableMethods());

        return [
            'order_id.required' => 'An order ID is required.',
            'order_id.exists' => 'The specified order does not exist.',
            'payment_method.required' => 'A payment method is required.',
            'payment_method.in' => "Payment method must be one of: {$methods}.",
        ];
    }
}
