<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\PostCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Post> */
class PostFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'post_category_id' => PostCategory::factory(), 'author_id' => User::factory()->admin(),
            'title' => fake()->sentence(4), 'slug' => fake()->unique()->slug(), 'excerpt' => fake()->sentence(),
            'content' => '## Teknik yaklaşım

Özenli üretim ve açık iletişim.', 'cover_image' => null,
            'type' => 'article', 'status' => 'draft', 'is_featured' => false,
            'published_at' => null, 'meta_title' => null, 'meta_description' => null, 'sort_order' => 0,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => 'published', 'published_at' => now()->subDay()]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => 'published', 'published_at' => now()->addDay()]);
    }

    public function news(): static
    {
        return $this->state(fn (array $attributes): array => ['type' => 'news']);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes): array => ['is_featured' => true]);
    }
}
