<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('ulid', 26)->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->decimal('amount', 20, 8);                      // positive = credit, negative = debit
            $table->smallInteger('type');                           // WalletTxType enum
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('balance_after', 20, 8);               // snapshot after this tx
            $table->string('idempotency_key', 100)->unique();
            $table->timestamp('created_at');                        // immutable — no updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
