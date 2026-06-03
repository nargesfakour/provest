<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Models;

use App\Modules\Auth\Models\User;
use App\Modules\Shared\Traits\HasUlid;
use App\Modules\Wallet\Enums\WithdrawalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class   Withdrawal extends Model
{
    use HasUlid;

    protected $fillable = [
        'user_id',
        'amount',
        'fee',
        'coin',
        'network',
        'destination_address',
        'memo',
        'status',
        'abantether_id',
        'tx_id',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'status'       => WithdrawalStatus::class,
            'amount'       => 'string',
            'fee'          => 'string',
            'processed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
