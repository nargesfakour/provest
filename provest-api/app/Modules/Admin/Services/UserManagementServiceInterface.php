<?php

declare(strict_types=1);

namespace App\Modules\Admin\Services;

use App\Modules\Auth\Models\User;
use App\Modules\Shared\Contracts\ServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserManagementServiceInterface extends ServiceInterface
{
    public function list(array $filters, int $perPage = 20): LengthAwarePaginator;

    public function findByUlid(string $ulid): User;

    public function activate(string $ulid): User;

    public function deactivate(string $ulid): User;

    public function walletTransactions(string $ulid, int $perPage = 20): LengthAwarePaginator;

    public function positions(string $ulid, int $perPage = 20): LengthAwarePaginator;

    public function orders(string $ulid, int $perPage = 20): LengthAwarePaginator;

    public function deposits(string $ulid, int $perPage = 20): LengthAwarePaginator;
}
