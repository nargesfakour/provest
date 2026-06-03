<?php

declare(strict_types=1);

namespace App\Modules\Orders\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'event_ulid'      => ['required', 'string', 'size:26'],
            'side'            => ['required', 'string', 'in:yes,no'],
            'price'           => ['required', 'numeric', 'between:0.01,0.99'],
            'quantity'        => ['required', 'numeric', 'gt:0'],
            'idempotency_key' => ['required', 'string', 'max:100'],
        ];
    }
}
