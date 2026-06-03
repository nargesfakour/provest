<?php

declare(strict_types=1);

namespace App\Modules\Orders\Models;

use App\Modules\Auth\Models\User;
use App\Modules\Markets\Models\Event;
use App\Modules\Orders\Enums\OrderSide;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Shared\Traits\HasUlid;
use App\Modules\Trades\Models\Trade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasUlid;

    protected $fillable = [
        'event_id',
        'user_id',
        'side',
        'price',
        'quantity',
        'filled_quantity',
        'status',
        'idempotency_key',
    ];

    protected function casts(): array
    {
        return [
            'side'            => OrderSide::class,
            'status'          => OrderStatus::class,
            'price'           => 'string',
            'quantity'        => 'string',
            'filled_quantity'  => 'string',
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

    public function tradesAsYes(): HasMany
    {
        return $this->hasMany(Trade::class, 'yes_order_id');
    }

    public function tradesAsNo(): HasMany
    {
        return $this->hasMany(Trade::class, 'no_order_id');
    }
}
