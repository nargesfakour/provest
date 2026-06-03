<?php

declare(strict_types=1);

use App\Modules\Auth\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Public channel  : event.{ulid}     — trades, order-book changes, settlement
| Private channel : user.{ulid}      — per-user order updates
|
*/

// Private channel: only the owner can subscribe
Broadcast::channel('user.{ulid}', function (User $user, string $ulid): bool {
    return $user->ulid === $ulid;
});
