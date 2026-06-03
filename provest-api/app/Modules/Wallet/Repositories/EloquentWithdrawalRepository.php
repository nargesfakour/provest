<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Repositories;

use App\Modules\Wallet\Models\Withdrawal;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentWithdrawalRepository implements WithdrawalRepositoryInterface
{
    public function create(array $data): Withdrawal
    {
        /** @var Withdrawal $withdrawal */
        $withdrawal = Withdrawal::create($data);

        return $withdrawal;
    }

    public function update(int $id, array $data): void
    {
        Withdrawal::where('id', $id)->update($data);
    }

    public function findById(int $id): ?Withdrawal
    {
        /** @var Withdrawal|null $withdrawal */
        $withdrawal = Withdrawal::find($id);

        return $withdrawal;
    }

    public function findByUlid(string $ulid): ?Withdrawal
    {
        /** @var Withdrawal|null $withdrawal */
        $withdrawal = Withdrawal::where('ulid', $ulid)->first();

        return $withdrawal;
    }

    public function paginateForUser(int $userId, int $perPage): LengthAwarePaginator
    {
        return Withdrawal::where('user_id', $userId)
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function paginateWithFilters(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = Withdrawal::with('user')->orderByDesc('id');

        if (isset($filters['status']) && $filters['status'] !== null && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($perPage);
    }
}
