<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->smallInteger('status')->default(1)->after('balance');
        });

        // Migrate existing data: is_active=true → 1 (Active), is_active=false → 2 (Suspended)
        DB::statement('UPDATE users SET status = CASE WHEN is_active = true THEN 1 ELSE 2 END');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('balance');
        });

        DB::statement('UPDATE users SET is_active = CASE WHEN status = 1 THEN true ELSE false END');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
