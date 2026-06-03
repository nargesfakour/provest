<?php

declare(strict_types=1);

namespace App\Modules\Markets\Services;

use App\Modules\Markets\DTOs\CreateEventDTO;
use App\Modules\Markets\DTOs\SettleEventDTO;
use App\Modules\Markets\DTOs\UpdateEventDTO;
use App\Modules\Markets\Enums\EventStatus;
use App\Modules\Markets\Models\Event;
use App\Modules\Markets\Repositories\EventRepositoryInterface;
use App\Modules\Orders\Enums\OrderStatus;
use App\Modules\Settlement\Jobs\SettleEventJob;
use App\Modules\Shared\Exceptions\BusinessException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EventService implements EventServiceInterface
{
    public function __construct(
        private readonly EventRepositoryInterface $repo,
        private readonly SubCategoryServiceInterface $subCategoryService,
    ) {}

    public function list(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $resolved = [];

        if (!empty($filters['status'])) {
            $resolved['status'] = $this->resolveStatusFilter($filters['status']);
        }

        if (!empty($filters['category'])) {
            $resolved['category'] = $filters['category'];
        }

        if (!empty($filters['sub_category'])) {
            $resolved['sub_category'] = $filters['sub_category'];
        }

        return $this->repo->paginateWithFilters($resolved, $perPage);
    }

    public function getOrderbook(string $ulid): array
    {
        $event = $this->findByUlid($ulid);

        $rows = $event->orders()
            ->whereIn('status', [OrderStatus::Open->value, OrderStatus::Partial->value])
            ->selectRaw('side, price, SUM((quantity - filled_quantity)::numeric) AS available_qty')
            ->groupBy('side', 'price')
            ->orderBy('price', 'desc')
            ->get();

        $yes = [];
        $no  = [];

        foreach ($rows as $row) {
            $entry = ['price' => $row->price, 'quantity' => number_format((float) $row->available_qty, 8, '.', '')];

            // OrderSide::Yes = 1, OrderSide::No = 2
            if ((int) $row->side === 1) {
                $yes[] = $entry;
            } else {
                $no[] = $entry;
            }
        }

        return ['yes' => $yes, 'no' => $no];
    }

    public function create(CreateEventDTO $dto): Event
    {
        $subCategory = $this->subCategoryService->findByUlid($dto->subCategoryUlid);

        $noInitialPrice = bcsub('1.0000', $dto->yesInitialPrice, 4);

        /** @var Event */
        return $this->repo->create([
            'sub_category_id'   => $subCategory->id,
            'title'             => $dto->title,
            'description'       => $dto->description,
            'status'            => EventStatus::Pending->value,
            'yes_initial_price' => $dto->yesInitialPrice,
            'no_initial_price'  => $noInitialPrice,
            'fee_rate'          => $dto->feeRate,
            'starts_at'         => $dto->startsAt,
            'ends_at'           => $dto->endsAt,
            'resolve_at'        => $dto->resolveAt,
            'created_by'        => $dto->adminId,
        ]);
    }

    public function findByUlid(string $ulid): Event
    {
        /** @var Event|null */
        $event = $this->repo->findByUlid($ulid);

        if (!$event) {
            throw new BusinessException('Event not found.', 404);
        }

        return $event;
    }

    public function update(string $ulid, UpdateEventDTO $dto): Event
    {
        $event = $this->findByUlid($ulid);

        if ($event->status !== EventStatus::Pending) {
            throw new BusinessException('Only pending events can be updated.', 422);
        }

        $subCategory = $this->subCategoryService->findByUlid($dto->subCategoryUlid);
        $noInitialPrice = bcsub('1.0000', $dto->yesInitialPrice, 4);

        $this->repo->update($event->id, [
            'sub_category_id'   => $subCategory->id,
            'title'             => $dto->title,
            'description'       => $dto->description,
            'yes_initial_price' => $dto->yesInitialPrice,
            'no_initial_price'  => $noInitialPrice,
            'fee_rate'          => $dto->feeRate,
            'starts_at'         => $dto->startsAt,
            'ends_at'           => $dto->endsAt,
            'resolve_at'        => $dto->resolveAt,
        ]);

        return $event->fresh(['subCategory.category', 'creator']);
    }

    public function delete(string $ulid): void
    {
        $event = $this->findByUlid($ulid);

        if ($event->status !== EventStatus::Pending) {
            throw new BusinessException('Only pending events can be deleted.', 422);
        }

        $this->repo->delete($event->id);
    }

    public function open(string $ulid): Event
    {
        $event = $this->findByUlid($ulid);

        if ($event->status !== EventStatus::Pending) {
            throw new BusinessException('Only pending events can be opened.', 422);
        }

        $this->repo->update($event->id, ['status' => EventStatus::Open->value]);

        return $event->fresh(['subCategory.category', 'creator']);
    }

    public function close(string $ulid): Event
    {
        $event = $this->findByUlid($ulid);

        if ($event->status !== EventStatus::Open) {
            throw new BusinessException('Only open events can be closed.', 422);
        }

        $this->repo->update($event->id, ['status' => EventStatus::Closed->value]);

        return $event->fresh(['subCategory.category', 'creator']);
    }

    public function settle(string $ulid, SettleEventDTO $dto): Event
    {
        $event = $this->findByUlid($ulid);

        if ($event->status !== EventStatus::Closed) {
            throw new BusinessException('Only closed events can be settled.', 422);
        }

        DB::transaction(function () use ($event, $dto): void {
            $this->repo->update($event->id, [
                'status'  => EventStatus::Settled->value,
                'outcome' => $dto->outcome->value,
            ]);

            SettleEventJob::dispatch($event->id, $dto->outcome);
        });

        return $event->fresh(['subCategory.category', 'creator']);
    }

    public function paginateOrders(string $ulid, int $perPage = 20): LengthAwarePaginator
    {
        $event = $this->findByUlid($ulid);

        return $event->orders()->with('user')->latest()->paginate($perPage);
    }

    public function paginateTrades(string $ulid, int $perPage = 20): LengthAwarePaginator
    {
        $event = $this->findByUlid($ulid);

        return $event->trades()->with(['yesUser', 'noUser'])->latest('created_at')->paginate($perPage);
    }

    public function paginatePositions(string $ulid, int $perPage = 20): LengthAwarePaginator
    {
        $event = $this->findByUlid($ulid);

        return $event->positions()->with('user')->paginate($perPage);
    }

    private function resolveStatusFilter(mixed $value): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        $case = EventStatus::cases();
        foreach ($case as $status) {
            if (strtolower($status->name) === strtolower((string) $value)) {
                return $status->value;
            }
        }

        throw new BusinessException("Invalid status filter: '{$value}'.", 422);
    }
}
