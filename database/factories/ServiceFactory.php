<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Service> */
class ServiceFactory extends Factory
{
    public function definition(): array
    {
        return ['title' => fake()->sentence(3), 'slug' => fake()->unique()->slug(), 'short_description' => fake()->sentence(), 'description' => fake()->paragraph(), 'icon' => 'engineering', 'sort_order' => 0, 'is_active' => true];
    }
}
