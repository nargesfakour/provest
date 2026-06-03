<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('ulid', 26)->unique();
            $table->foreignId('sub_category_id')->constrained('sub_categories');
            $table->string('title', 500);
            $table->text('description')->nullable();
            $table->smallInteger('status')->default(0);          // EventStatus enum
            $table->smallInteger('outcome')->nullable();          // EventOutcome enum
            $table->decimal('yes_initial_price', 5, 4);
            $table->decimal('no_initial_price', 5, 4);
            $table->decimal('fee_rate', 5, 4)->default('0.0100');
            $table->timestampTz('starts_at')->nullable();
            $table->timestampTz('ends_at')->nullable();
            $table->timestampTz('resolve_at')->nullable();
            $table->foreignId('created_by')->constrained('admins');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
