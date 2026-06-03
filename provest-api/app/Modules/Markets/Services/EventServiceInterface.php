<?php

declare(strict_types=1);

namespace App\Modules\Markets\Services;

use App\Modules\Markets\DTOs\CreateEventDTO;
use App\Modules\Markets\DTOs\SettleEventDTO;
use App\Modules\Markets\DTOs\UpdateEventDTO;
use App\Modules\Markets\Models\Event;
use App\Modules\Shared\Contracts\ServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;

interface EventServiceInterface extends ServiceInterface
{
    public function list(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function create(CreateEventDTO $dto): Event;

    public function findByUlid(string $ulid): Event;

    public function update(string $ulid, UpdateEventDTO $dto): Event;

    public function delete(string $ulid): void;

    public function open(string $ulid): Event;

    public function close(string $ulid): Event;

    public function settle(string $ulid, SettleEventDTO $dto): Event;

    public function paginateOrders(string $ulid, int $perPage = 20): LengthAwarePaginator;

    public function paginateTrades(string $ulid, int $perPage = 20): LengthAwarePaginator;

    public function paginatePositions(string $ulid, int $perPage = 20): LengthAwarePaginator;

    public function getOrderbook(string $ulid): array;
}
