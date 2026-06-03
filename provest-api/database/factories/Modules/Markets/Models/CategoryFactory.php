<?php

declare(strict_types=1);

namespace Database\Factories\Modules\Markets\Models;

use App\Modules\Markets\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name'      => $name,
            'slug'      => Str::slug($name) . '-' . Str::random(4),
            'is_active' => true,
        ];
    }
}
