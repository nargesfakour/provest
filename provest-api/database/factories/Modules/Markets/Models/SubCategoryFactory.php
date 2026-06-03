<?php

declare(strict_types=1);

namespace Database\Factories\Modules\Markets\Models;

use App\Modules\Markets\Models\Category;
use App\Modules\Markets\Models\SubCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SubCategory>
 */
class SubCategoryFactory extends Factory
{
    protected $model = SubCategory::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'category_id' => Category::factory(),
            'name'        => $name,
            'slug'        => Str::slug($name) . '-' . Str::random(4),
            'is_active'   => true,
        ];
    }
}
