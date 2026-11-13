<?php

use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\PostSeeder;

test('post seeding creates five examples without duplicating or changing existing users and catalog records', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create();
    $userAttributes = $admin->fresh()->getAttributes();
    $productAttributes = $product->fresh()->getAttributes();
    $categoryAttributes = $product->category->fresh()->getAttributes();

    $this->seed(PostSeeder::class);
    $this->seed(PostSeeder::class);

    $this->assertDatabaseCount('posts', 5);
    $this->assertDatabaseCount('post_categories', 2);
    expect(Post::where('type', 'article')->count())->toBe(3);
    expect(Post::where('type', 'news')->count())->toBe(2);
    expect(Post::visible()->count())->toBe(5);
    expect($admin->fresh()->getAttributes())->toBe($userAttributes);
    expect($product->fresh()->getAttributes())->toBe($productAttributes);
    expect($product->category->fresh()->getAttributes())->toBe($categoryAttributes);
});

test('edited demo posts categories and author attribution survive reseeding', function () {
    $admin = User::factory()->admin()->create();
    $this->seed(PostSeeder::class);
    $post = Post::firstOrFail();
    $post->update(['title' => 'Edited demo', 'slug' => 'edited-slug', 'status' => 'draft', 'content' => 'Edited body']);
    $post->category->update(['name' => 'Edited category', 'slug' => 'edited-category', 'is_active' => false]);
    $admin->is_admin = false;
    $admin->save();
    User::factory()->admin()->create();

    $this->seed(PostSeeder::class);

    $this->assertDatabaseCount('posts', 5);
    expect($post->fresh())->title->toBe('Edited demo')->slug->toBe('edited-slug')->content->toBe('Edited body')->status->toBe('draft')->author_id->toBe($admin->id);
    expect($post->category->fresh())->name->toBe('Edited category')->slug->toBe('edited-category')->is_active->toBeFalse();
});

test('post seeder never creates an account when no administrator exists', function () {
    $this->seed(PostSeeder::class);

    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('posts', 0);
});
