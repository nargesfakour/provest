<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->string('ulid', 26)->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->decimal('amount', 20, 8);
            $table->decimal('fee', 20, 8)->default('0.00000000');
            $table->string('coin', 20)->default('USDT');
            $table->string('network', 50);
            $table->string('destination_address', 500);
            $table->string('memo', 255)->nullable();
            $table->smallInteger('status')->default(0);              // WithdrawalStatus enum
            $table->string('abantether_id', 255)->nullable();
            $table->string('tx_id', 255)->nullable();
            $table->timestampTz('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
