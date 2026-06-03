<?php

declare(strict_types=1);

namespace App\Modules\Shared\Traits;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\Ulid;

trait HasUlid
{
    protected static function bootHasUlid(): void
    {
        static::creating(function (Model $model): void {
            if (empty($model->ulid)) {
                $model->ulid = strtolower((string) new Ulid());
            }
        });
    }

    public function resolveRouteBinding(mixed $value, $field = null): ?Model
    {
        return $this->where('ulid', $value)->first();
    }
}
