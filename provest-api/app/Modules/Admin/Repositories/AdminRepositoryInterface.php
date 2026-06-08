<?php

declare(strict_types=1);

namespace App\Modules\Admin\Repositories;

use App\Modules\Admin\Models\Admin;
use App\Modules\Shared\Contracts\RepositoryInterface;

interface AdminRepositoryInterface extends RepositoryInterface
{
    public function findByEmail(string $email): ?Admin;
}
