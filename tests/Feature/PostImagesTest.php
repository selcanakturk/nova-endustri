<?php

use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('covers upload into posts storage and are visible on the public article', function () {
    Storage::fake('public');
    $category = PostCategory::factory()->create();
    $date = now()->timezone(config('nova.editorial_timezone'))->subHour()->format('Y-m-d\TH:i');

    $this->actingAs(User::factory()->admin()->create())->post('/admin/posts', adminPostData($category, ['cover_image' => UploadedFile::fake()->image('cover.png'), 'status' => 'published', 'published_at' => $date]))->assertRedirect();

    $post = Post::where('slug', 'yeni-yazi')->firstOrFail();
    expect($post->cover_image)->toStartWith('posts/');
    Storage::disk('public')->assertExists($post->cover_image);
    $this->get($post->public_url)->assertOk()->assertSee($post->image_url);
});

test('cover replacement and removal clean up unused files and use a placeholder', function () {
    Storage::fake('public');
    Storage::disk('public')->put('posts/old.png', 'cover');
    $post = Post::factory()->create(['cover_image' => 'posts/old.png']);
    $this->actingAs($post->author);

    $this->put(route('admin.posts.update', $post), adminPostData($post->category, ['cover_image' => UploadedFile::fake()->image('new.png')]))->assertRedirect();
    Storage::disk('public')->assertMissing('posts/old.png');
    $newImage = $post->fresh()->cover_image;
    Storage::disk('public')->assertExists($newImage);
    $this->put(route('admin.posts.update', $post), adminPostData($post->category, ['remove_image' => '1']))->assertRedirect();
    Storage::disk('public')->assertMissing($newImage);
    expect($post->fresh()->image_url)->toBe(asset('images/editorial.svg'));
});

test('shared cover files survive deletion until the last post reference is removed', function () {
    Storage::fake('public');
    Storage::disk('public')->put('posts/shared.png', 'cover');
    $post = Post::factory()->create(['cover_image' => 'posts/shared.png']);
    $other = Post::factory()->for($post->category, 'category')->for($post->author, 'author')->create(['cover_image' => 'posts/shared.png']);
    $this->actingAs($post->author);

    $this->delete(route('admin.posts.destroy', $post))->assertRedirect();
    Storage::disk('public')->assertExists('posts/shared.png');
    $this->delete(route('admin.posts.destroy', $other))->assertRedirect();
    Storage::disk('public')->assertMissing('posts/shared.png');
});

test('post and product image references protect each others files', function () {
    Storage::fake('public');
    Storage::disk('public')->put('catalog/shared.png', 'cover');
    $product = Product::factory()->create(['image' => 'catalog/shared.png']);
    $post = Post::factory()->create(['cover_image' => 'catalog/shared.png']);
    $this->actingAs($post->author);

    $this->delete(route('admin.products.destroy', $product))->assertRedirect();
    Storage::disk('public')->assertExists('catalog/shared.png');
    $this->delete(route('admin.posts.destroy', $post))->assertRedirect();
    Storage::disk('public')->assertMissing('catalog/shared.png');
});

test('deleting posts never removes artwork still used by products', function () {
    Storage::fake('public');
    Storage::disk('public')->put('posts/shared.png', 'cover');
    $product = Product::factory()->create(['image' => 'posts/shared.png']);
    $post = Post::factory()->create(['cover_image' => 'posts/shared.png']);

    $this->actingAs($post->author)->delete(route('admin.posts.destroy', $post))->assertRedirect();

    Storage::disk('public')->assertExists('posts/shared.png');
    $this->assertModelExists($product);
});

test('cover validation rejects unsafe types excessive size and raw paths', function (string $kind) {
    Storage::fake('public');
    $category = PostCategory::factory()->create();
    $file = match ($kind) {
        'svg' => UploadedFile::fake()->createWithContent('cover.svg', '<svg onload="alert(1)"></svg>'),
        'html' => UploadedFile::fake()->createWithContent('cover.jpg', '<script>alert(1)</script>')->mimeType('text/html'),
        'large' => UploadedFile::fake()->image('large.png')->size(4097),
        'raw' => '../../.env',
    };

    $this->actingAs(User::factory()->admin()->create())->post('/admin/posts', adminPostData($category, ['cover_image' => $file]))->assertSessionHasErrors('cover_image');
    $this->assertDatabaseCount('posts', 0);
    expect(Storage::disk('public')->allFiles())->toBe([]);
})->with(['svg', 'html', 'large', 'raw']);
