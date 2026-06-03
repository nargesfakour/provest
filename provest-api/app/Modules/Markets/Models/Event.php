<?php

declare(strict_types=1);

namespace App\Modules\Markets\Models;

use App\Modules\Admin\Models\Admin;
use App\Modules\Markets\Enums\EventOutcome;
use App\Modules\Markets\Enums\EventStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Positions\Models\Position;
use App\Modules\Settlement\Models\Settlement;
use App\Modules\Shared\Traits\HasUlid;
use App\Modules\Trades\Models\Trade;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory, HasUlid;

    protected $fillable = [
        'sub_category_id',
        'title',
        'description',
        'status',
        'outcome',
        'yes_initial_price',
        'no_initial_price',
        'fee_rate',
        'starts_at',
        'ends_at',
        'resolve_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status'            => EventStatus::class,
            'outcome'           => EventOutcome::class,
            'yes_initial_price' => 'string',
            'no_initial_price'  => 'string',
            'fee_rate'          => 'string',
            'starts_at'         => 'datetime',
            'ends_at'           => 'datetime',
            'resolve_at'        => 'datetime',
        ];
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function trades(): HasMany
    {
        return $this->hasMany(Trade::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(Settlement::class);
    }
}
