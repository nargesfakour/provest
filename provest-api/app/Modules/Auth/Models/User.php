<?php

declare(strict_types=1);

namespace App\Modules\Auth\Models;

use App\Modules\Auth\Enums\UserStatus;
use App\Modules\Orders\Models\Order;
use App\Modules\Positions\Models\Position;
use App\Modules\Settlement\Models\Settlement;
use App\Modules\Shared\Traits\HasUlid;
use App\Modules\Wallet\Models\Deposit;
use App\Modules\Wallet\Models\WalletTransaction;
use App\Modules\Wallet\Models\Withdrawal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUlid;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $attributes = [
        'balance' => '0.00000000',
        'status'  => 1,
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'balance'  => 'string',
            'status'   => UserStatus::class,
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(Settlement::class);
    }
}
