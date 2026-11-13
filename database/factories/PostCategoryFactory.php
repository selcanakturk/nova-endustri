<?php

namespace Database\Factories;

use App\Models\PostCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PostCategory> */
class PostCategoryFactory extends Factory
{
    /** @return array{name: string, slug: string, description: string, sort_order: int, is_active: bool} */
    public function definition(): array
    {
        return ['name' => fake()->words(2, true), 'slug' => fake()->unique()->slug(), 'description' => fake()->sentence(), 'sort_order' => 0, 'is_active' => true];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => ['is_active' => false]);
    }
}
