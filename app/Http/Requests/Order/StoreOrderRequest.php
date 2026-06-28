<?php

namespace App\Http\Requests\Order;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Order::class);
    }

    public function rules(): array
    {
        return [
            'items'             => ['required', 'array', 'min:1'],
            'items.*.name'      => ['required', 'string', 'max:255'],
            'items.*.quantity'  => ['required', 'integer', 'min:1'],
            'items.*.price'     => ['required', 'numeric', 'min:0.01'],
            'status'            => ['sometimes', 'in:pending,confirmed,cancelled'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'            => 'At least one item is required.',
            'items.*.name.required'     => 'Each item must have a name.',
            'items.*.quantity.required' => 'Each item must have a quantity.',
            'items.*.quantity.min'      => 'Item quantity must be at least 1.',
            'items.*.price.required'    => 'Each item must have a price.',
            'items.*.price.min'         => 'Item price must be greater than 0.',
            'status.in'                 => 'Status must be one of: pending, confirmed, cancelled.',
        ];
    }
}
