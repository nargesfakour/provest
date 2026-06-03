<?php

declare(strict_types=1);

namespace App\Modules\Admin\Services;

use App\Modules\Auth\Repositories\UserRepositoryInterface;
use App\Modules\Markets\Repositories\EventRepositoryInterface;
use App\Modules\Trades\Repositories\TradeRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AdminReportService implements AdminReportServiceInterface
{
    public function __construct(
        private readonly TradeRepositoryInterface $tradeRepo,
        private readonly EventRepositoryInterface $eventRepo,
        private readonly UserRepositoryInterface  $userRepo,
    ) {}

    public function fees(array $filters): array
    {
        $resolved = $this->resolveTradeFilters($filters);

        $totals = $this->tradeRepo->sumFeesWithFilters($resolved);

        return array_merge($totals, [
            'filters' => [
                'from'       => $filters['from']       ?? null,
                'to'         => $filters['to']         ?? null,
                'event_ulid' => $filters['event_ulid'] ?? null,
            ],
        ]);
    }

    public function trades(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->tradeRepo->paginateWithFilters(
            $this->resolveTradeFilters($filters),
            $perPage,
        );
    }

    public function wallets(int $perPage): LengthAwarePaginator
    {
        return $this->userRepo->paginateWithFilters([], $perPage);
    }

    public function volume(array $filters): Collection
    {
        $resolved = $this->resolveTradeFilters($filters);

        return $this->tradeRepo->dailyVolume($resolved);
    }

    private function resolveTradeFilters(array $filters): array
    {
        $resolved = [];

        if (!empty($filters['from'])) {
            $resolved['from'] = $filters['from'];
        }

        if (!empty($filters['to'])) {
            $resolved['to'] = $filters['to'];
        }

        if (!empty($filters['event_ulid'])) {
            $event = $this->eventRepo->findByUlid($filters['event_ulid']);
            if ($event !== null) {
                $resolved['event_id'] = $event->id;
            }
        }

        return $resolved;
    }
}
