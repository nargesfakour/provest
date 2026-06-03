<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->string('ulid', 26)->unique();
            $table->foreignId('event_id')->constrained('events');
            $table->foreignId('yes_order_id')->constrained('orders');
            $table->foreignId('no_order_id')->constrained('orders');
            $table->foreignId('yes_user_id')->constrained('users');
            $table->foreignId('no_user_id')->constrained('users');
            $table->decimal('price', 5, 4);
            $table->decimal('quantity', 20, 8);
            $table->decimal('yes_fee', 20, 8);
            $table->decimal('no_fee', 20, 8);
            $table->timestamp('created_at');                   // immutable — no updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
