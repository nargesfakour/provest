<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->string('ulid', 26)->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->string('tx_id')->unique();                  // blockchain tx hash — prevents duplicates
            $table->decimal('amount', 20, 8);
            $table->string('coin', 20)->default('USDT');
            $table->string('network', 50);
            $table->smallInteger('status')->default(0);         // DepositStatus enum
            $table->timestampTz('confirmed_at')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposits');
    }
};
