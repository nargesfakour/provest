<?php

declare(strict_types=1);

namespace App\Modules\Settlement\Models;

use App\Modules\Auth\Models\User;
use App\Modules\Markets\Models\Event;
use App\Modules\Orders\Enums\OrderSide;
use App\Modules\Settlement\Enums\SettleStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Settlement extends Model
{
    protected $fillable = [
        'event_id',
        'user_id',
        'side',
        'quantity',
        'payout',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'side'     => OrderSide::class,
            'status'   => SettleStatus::class,
            'quantity' => 'string',
            'payout'   => 'string',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
