<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Models;

use App\Modules\Auth\Models\User;
use App\Modules\Shared\Traits\HasUlid;
use App\Modules\Wallet\Enums\DepositStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deposit extends Model
{
    use HasUlid;

    protected $fillable = [
        'user_id',
        'tx_id',
        'amount',
        'coin',
        'network',
        'status',
        'confirmed_at',
        'rejected_reason',
    ];

    protected function casts(): array
    {
        return [
            'status'       => DepositStatus::class,
            'amount'       => 'string',
            'confirmed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
