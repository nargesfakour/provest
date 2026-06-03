<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WithdrawalRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'amount'              => ['required', 'numeric', 'gt:0'],
            'network'             => ['required', 'string', 'in:TRC20,ERC20,BEP20'],
            'destination_address' => ['required', 'string', 'max:500'],
            'memo'                => ['nullable', 'string', 'max:255'],
        ];
    }
}
