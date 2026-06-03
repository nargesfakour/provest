<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Models;

use App\Modules\Auth\Models\User;
use App\Modules\Shared\Traits\HasUlid;
use App\Modules\Wallet\Enums\WalletTxType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    use HasUlid;

    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'amount',
        'type',
        'reference_type',
        'reference_id',
        'balance_after',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'type'          => WalletTxType::class,
            'amount'        => 'string',
            'balance_after' => 'string',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
