<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.name' => ['required_with:items', 'string', 'max:255'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.price' => ['required_with:items', 'numeric', 'min:0.01'],
            'status' => ['sometimes', 'in:pending,confirmed,cancelled'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.*.name.required_with' => 'Each item must have a name.',
            'items.*.quantity.required_with' => 'Each item must have a quantity.',
            'items.*.quantity.min' => 'Item quantity must be at least 1.',
            'items.*.price.required_with' => 'Each item must have a price.',
            'items.*.price.min' => 'Item price must be greater than 0.',
            'status.in' => 'Status must be one of: pending, confirmed, cancelled.',
        ];
    }
}
