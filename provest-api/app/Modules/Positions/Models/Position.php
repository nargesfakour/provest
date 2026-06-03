<?php

declare(strict_types=1);

namespace App\Modules\Positions\Models;

use App\Modules\Auth\Models\User;
use App\Modules\Markets\Models\Event;
use App\Modules\Orders\Enums\OrderSide;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Position extends Model
{
    protected $fillable = [
        'user_id',
        'event_id',
        'side',
        'quantity',
        'avg_price',
    ];

    protected function casts(): array
    {
        return [
            'side'      => OrderSide::class,
            'quantity'  => 'string',
            'avg_price' => 'string',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
