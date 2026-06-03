<?php

declare(strict_types=1);

namespace App\Modules\Admin\Services;

use App\Modules\Auth\Enums\UserStatus;
use App\Modules\Auth\Models\User;
use App\Modules\Auth\Repositories\UserRepositoryInterface;
use App\Modules\Shared\Exceptions\BusinessException;
use Illuminate\Pagination\LengthAwarePaginator;

class UserManagementService implements UserManagementServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepo,
    ) {}

    public function list(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $resolved = [];

        if (isset($filters['status']) && $filters['status'] !== null) {
            $status = UserStatus::tryFrom((int) $filters['status']);
            if ($status !== null) {
                $resolved['status'] = $status->value;
            }
        }

        if (!empty($filters['search'])) {
            $resolved['search'] = $filters['search'];
        }

        return $this->userRepo->paginateWithFilters($resolved, $perPage);
    }

    public function findByUlid(string $ulid): User
    {
        /** @var User|null */
        $user = $this->userRepo->findByUlid($ulid);

        if (!$user) {
            throw new BusinessException('User not found.', 404);
        }

        return $user;
    }

    public function activate(string $ulid): User
    {
        $user = $this->findByUlid($ulid);
        $this->userRepo->setStatus($user->id, UserStatus::Active);

        return $user->fresh();
    }

    public function deactivate(string $ulid): User
    {
        $user = $this->findByUlid($ulid);
        $this->userRepo->setStatus($user->id, UserStatus::Suspended);

        return $user->fresh();
    }

    public function walletTransactions(string $ulid, int $perPage = 20): LengthAwarePaginator
    {
        $user = $this->findByUlid($ulid);

        return $user->walletTransactions()->latest('created_at')->paginate($perPage);
    }

    public function positions(string $ulid, int $perPage = 20): LengthAwarePaginator
    {
        $user = $this->findByUlid($ulid);

        return $user->positions()->with('event')->latest()->paginate($perPage);
    }

    public function orders(string $ulid, int $perPage = 20): LengthAwarePaginator
    {
        $user = $this->findByUlid($ulid);

        return $user->orders()->with('event')->latest()->paginate($perPage);
    }

    public function deposits(string $ulid, int $perPage = 20): LengthAwarePaginator
    {
        $user = $this->findByUlid($ulid);

        return $user->deposits()->latest()->paginate($perPage);
    }
}
