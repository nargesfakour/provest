<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('ulid', 26)->unique();
            $table->foreignId('event_id')->constrained('events');
            $table->foreignId('user_id')->constrained('users');
            $table->smallInteger('side');                                         // OrderSide enum
            $table->decimal('price', 5, 4);
            $table->decimal('quantity', 20, 8);
            $table->decimal('filled_quantity', 20, 8)->default('0.00000000');
            $table->smallInteger('status')->default(0);                           // OrderStatus enum
            $table->string('idempotency_key', 100)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
