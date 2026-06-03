<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('event_id')->constrained('events');
            $table->smallInteger('side');                           // OrderSide enum
            $table->decimal('quantity', 20, 8)->default('0.00000000');
            $table->decimal('avg_price', 10, 8);
            $table->timestamps();

            $table->unique(['user_id', 'event_id', 'side']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
