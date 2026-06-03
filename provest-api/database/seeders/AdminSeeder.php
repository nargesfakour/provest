<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\Admin\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::firstOrCreate(
            ['email' => 'admin@provest.local'],
            [
                'name'     => 'Super Admin',
                'password' => Hash::make('password'),
            ],
        );
    }
}
