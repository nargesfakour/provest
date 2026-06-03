<?php

declare(strict_types=1);

namespace App\Modules\Markets\Http\Requests;

use App\Modules\Markets\DTOs\SettleEventDTO;
use App\Modules\Markets\Enums\EventOutcome;
use Illuminate\Foundation\Http\FormRequest;

class SettleEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'outcome' => ['required', 'string', 'in:yes,no'],
        ];
    }

    public function toDTO(): SettleEventDTO
    {
        return new SettleEventDTO(
            outcome: $this->input('outcome') === 'yes' ? EventOutcome::Yes : EventOutcome::No,
        );
    }
}
