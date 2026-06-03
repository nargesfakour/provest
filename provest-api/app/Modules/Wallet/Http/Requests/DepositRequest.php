<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepositRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tx_id'   => ['required', 'string', 'max:255'],
            'network' => ['required', 'string', 'in:TRC20,ERC20,BEP20'],
        ];
    }
}
