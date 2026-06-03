<?php

declare(strict_types=1);

namespace App\Modules\Trades\Models;

use App\Modules\Auth\Models\User;
use App\Modules\Markets\Models\Event;
use App\Modules\Orders\Models\Order;
use App\Modules\Shared\Traits\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trade extends Model
{
    use HasUlid;

    const UPDATED_AT = null;

    protected $fillable = [
        'event_id',
        'yes_order_id',
        'no_order_id',
        'yes_user_id',
        'no_user_id',
        'price',
        'quantity',
        'yes_fee',
        'no_fee',
    ];

    protected function casts(): array
    {
        return [
            'price'    => 'string',
            'quantity' => 'string',
            'yes_fee'  => 'string',
            'no_fee'   => 'string',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function yesOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'yes_order_id');
    }

    public function noOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'no_order_id');
    }

    public function yesUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'yes_user_id');
    }

    public function noUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'no_user_id');
    }
}
