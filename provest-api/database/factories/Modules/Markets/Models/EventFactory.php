<?php

declare(strict_types=1);

namespace Database\Factories\Modules\Markets\Models;

use App\Modules\Admin\Models\Admin;
use App\Modules\Markets\Enums\EventStatus;
use App\Modules\Markets\Models\Event;
use App\Modules\Markets\Models\SubCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        return [
            'sub_category_id'   => SubCategory::factory(),
            'title'             => fake()->sentence(6),
            'description'       => fake()->paragraph(),
            'status'            => EventStatus::Pending->value,
            'outcome'           => null,
            'yes_initial_price' => '0.5000',
            'no_initial_price'  => '0.5000',
            'fee_rate'          => '0.0100',
            'starts_at'         => null,
            'ends_at'           => null,
            'resolve_at'        => null,
            'created_by'        => Admin::factory(),
        ];
    }

    public function open(): static
    {
        return $this->state(['status' => EventStatus::Open->value]);
    }

    public function closed(): static
    {
        return $this->state(['status' => EventStatus::Closed->value]);
    }
}
