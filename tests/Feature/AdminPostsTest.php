<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\User;
use Illuminate\Database\QueryException;

dataset('content endpoints', [
    ['GET', '/admin/posts'], ['GET', '/admin/posts/create'], ['POST', '/admin/posts'], ['GET', '/admin/posts/%d/edit'],
    ['PUT', '/admin/posts/%d'], ['GET', '/admin/posts/%d/delete'], ['DELETE', '/admin/posts/%d'], ['POST', '/admin/posts/preview'],
    ['GET', '/admin/post-categories'], ['GET', '/admin/post-categories/create'], ['POST', '/admin/post-categories'],
    ['GET', '/admin/post-categories/%d/edit'], ['PUT', '/admin/post-categories/%d'], ['GET', '/admin/post-categories/%d/delete'], ['DELETE', '/admin/post-categories/%d'],
]);

test('guests cannot access content administration', function (string $method, string $path) {
    $post = Post::factory()->create();
    $id = str_contains($path, 'post-categories') ? $post->post_category_id : $post->id;

    $this->call($method, sprintf($path, $id))->assertRedirect(route('admin.login'));
    $this->assertModelExists($post);
})->with('content endpoints');

test('non administrators cannot access content administration', function (string $method, string $path) {
    $post = Post::factory()->create();
    $id = str_contains($path, 'post-categories') ? $post->post_category_id : $post->id;

    $this->actingAs(User::factory()->create())->call($method, sprintf($path, $id))->assertForbidden();
    $this->assertModelExists($post);
})->with('content endpoints');

test('administrators can open all new lists forms and confirmations', function (string $path) {
    $post = Post::factory()->create();
    $id = str_contains($path, 'post-categories') ? $post->post_category_id : $post->id;

    $this->actingAs($post->author)->get(sprintf($path, $id))->assertOk();
})->with([
    ['/admin/posts'], ['/admin/posts/create'], ['/admin/posts/%d/edit'], ['/admin/posts/%d/delete'],
    ['/admin/post-categories'], ['/admin/post-categories/create'], ['/admin/post-categories/%d/edit'], ['/admin/post-categories/%d/delete'],
]);

test('blog categories have complete CRUD without touching product categories', function () {
    $productCategory = Category::factory()->create(['name' => 'Product category']);
    $this->actingAs(User::factory()->admin()->create());

    $this->post('/admin/post-categories', adminCategoryData(['name' => 'Blog category']))->assertRedirect();
    $category = PostCategory::where('slug', 'yeni-kategori')->firstOrFail();
    $this->put(route('admin.post-categories.update', $category), adminCategoryData(['name' => 'Changed blog category', 'is_active' => '0']))->assertRedirect();
    expect($category->fresh())->name->toBe('Changed blog category')->is_active->toBeFalse();
    $this->delete(route('admin.post-categories.destroy', $category))->assertRedirect();
    $this->assertModelMissing($category);
    expect($productCategory->fresh()->name)->toBe('Product category');
});

test('blog categories with posts cannot be deleted and have a protected foreign key', function () {
    $post = Post::factory()->create();
    $this->actingAs($post->author);

    $this->delete(route('admin.post-categories.destroy', $post->category))->assertSessionHas('error', 'Bu kategoriye bağlı içerikler var. Önce içerikleri başka bir kategoriye taşıyın.');
    $this->get(route('admin.post-categories.delete', $post->category))->assertOk()->assertDontSeeText('Evet, sil');
    expect(fn () => $post->category->delete())->toThrow(QueryException::class);
    expect($post->category->posts->first()->is($post))->toBeTrue();
});

test('content author is taken from the authenticated administrator and remains unchanged on edits', function () {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->admin()->create();
    $category = PostCategory::factory()->create();

    $this->actingAs($admin)->post('/admin/posts', adminPostData($category, ['author_id' => $other->id, 'slug' => '']))->assertRedirect();
    $post = Post::where('slug', 'yeni-yazi')->firstOrFail();
    expect($post->author->is($admin))->toBeTrue();
    expect($admin->posts->first()->is($post))->toBeTrue();

    $this->actingAs($other)->put(route('admin.posts.update', $post), adminPostData($category, ['title' => 'Changed title', 'slug' => '', 'author_id' => $other->id]))->assertRedirect();
    expect($post->fresh())->slug->toBe('yeni-yazi')->title->toBe('Changed title')->author_id->toBe($admin->id);
});

test('admin can publish edit schedule and delete posts with correct public visibility', function () {
    $this->freezeTime();
    $category = PostCategory::factory()->create();
    $this->actingAs(User::factory()->admin()->create());
    $date = now()->timezone(config('nova.editorial_timezone'))->subHour()->format('Y-m-d\TH:i');
    $data = adminPostData($category, ['status' => 'published', 'published_at' => $date, 'is_featured' => '1']);

    $this->post('/admin/posts', $data)->assertRedirect()->assertSessionHasNoErrors();
    $post = Post::where('slug', 'yeni-yazi')->firstOrFail();
    $this->get($post->public_url)->assertOk();
    expect($post->published_at->format('Y-m-d H:i'))->toBe(now()->subHour()->format('Y-m-d H:i'));

    $this->put(route('admin.posts.update', $post), [...$data, 'published_at' => now()->timezone(config('nova.editorial_timezone'))->addDay()->format('Y-m-d\TH:i')])->assertRedirect();
    $this->get($post->public_url)->assertNotFound();
    $this->delete(route('admin.posts.destroy', $post))->assertRedirect()->assertSessionHas('status', 'İçerik silindi.');
    $this->assertModelMissing($post);
});

test('content filters search title and combine type status and category', function () {
    $post = Post::factory()->news()->create(['title' => 'Matching note']);
    Post::factory()->published()->for($post->category, 'category')->create(['title' => 'Wrong status']);
    Post::factory()->for($post->category, 'category')->create(['title' => 'Wrong type']);

    $this->actingAs($post->author)->get('/admin/posts?'.http_build_query(['q' => 'Matching', 'post_category_id' => $post->post_category_id, 'type' => 'news', 'status' => 'draft']))
        ->assertOk()->assertSeeText('Matching note')->assertDontSeeText('Wrong status')->assertDontSeeText('Wrong type');
});

test('admin content pagination preserves filters', function () {
    $category = PostCategory::factory()->create();
    $author = User::factory()->admin()->create();
    Post::factory()->count(16)->for($category, 'category')->for($author, 'author')->sequence(fn ($sequence): array => ['title' => 'Note '.$sequence->index, 'sort_order' => $sequence->index])->create();

    $this->actingAs($author)->get('/admin/posts?q=Note&type=article')->assertOk()->assertDontSeeText('Note 15')->assertSee('q=Note&amp;type=article&amp;page=2', false);
});

test('post validation reports invalid fields without creating contents', function (array $overrides, string $field) {
    $category = PostCategory::factory()->create();

    $this->actingAs(User::factory()->admin()->create())->post('/admin/posts', adminPostData($category, $overrides))->assertSessionHasErrors($field);
    $this->assertDatabaseCount('posts', 0);
})->with([
    [['title' => ''], 'title'], [['title' => ['invalid']], 'title'], [['title' => str_repeat('x', 256)], 'title'],
    [['post_category_id' => 999], 'post_category_id'], [['slug' => '../unsafe'], 'slug'],
    [['type' => 'invalid'], 'type'], [['status' => 'private'], 'status'],
    [['excerpt' => ''], 'excerpt'], [['content' => ''], 'content'],
    [['content' => str_repeat('x', 100001)], 'content'], [['excerpt' => str_repeat('x', 1001)], 'excerpt'],
    [['status' => 'published', 'published_at' => null], 'published_at'], [['published_at' => 'not-a-date'], 'published_at'],
    [['meta_title' => str_repeat('x', 71)], 'meta_title'], [['meta_description' => str_repeat('x', 161)], 'meta_description'],
    [['sort_order' => -1], 'sort_order'], [['is_featured' => 'wrong'], 'is_featured'],
]);

test('post and blog category slugs reject duplicates but allow their own values on updates', function () {
    $post = Post::factory()->create(['slug' => 'taken']);
    $this->actingAs($post->author);

    $this->post('/admin/posts', adminPostData($post->category, ['slug' => 'taken']))->assertSessionHasErrors(['slug' => 'Slug zaten kullanılıyor.']);
    $this->put(route('admin.posts.update', $post), adminPostData($post->category, ['slug' => 'taken']))->assertRedirect()->assertSessionHasNoErrors();
    $this->post('/admin/post-categories', adminCategoryData(['slug' => $post->category->slug]))->assertSessionHasErrors('slug');
    expect(fn () => Post::factory()->for($post->category, 'category')->create(['slug' => 'taken']))->toThrow(QueryException::class);
});

test('preview uses the same safe Markdown pipeline as public content', function () {
    $this->actingAs(User::factory()->admin()->create())->postJson('/admin/posts/preview', ['content' => "## Heading\n\n**Bold** <script>alert(1)</script> [bad](javascript:alert%281%29)"])
        ->assertOk()->assertJsonStructure(['html']);
    $html = $this->getJson('/admin/posts/preview')->getStatusCode();
    expect($html)->toBe(405);
});

test('preview rejects excessive input and returns escaped safe markup', function () {
    $this->actingAs(User::factory()->admin()->create());
    $response = $this->postJson('/admin/posts/preview', ['content' => "**Safe**\n\n<script>alert(1)</script>\n\n[bad](javascript:alert%281%29)"])->assertOk();
    expect($response->json('html'))->toContain('<strong>Safe</strong>')->not->toContain('<script>')->not->toContain('href="javascript:');
    $this->postJson('/admin/posts/preview', ['content' => str_repeat('x', 100001)])->assertUnprocessable()->assertJsonValidationErrors('content');
});

test('preview and content mutations retain CSRF protection', function () {
    $admin = User::factory()->admin()->create();
    $this->app['env'] = 'local';

    $this->actingAs($admin)->postJson('/admin/posts/preview', ['content' => 'test'])->assertStatus(419);
});

test('admin forms safely escape existing Markdown and metadata', function () {
    $post = Post::factory()->create(['title' => '<script>title()</script>', 'content' => '</textarea><script>body()</script>']);

    $this->actingAs($post->author)->get(route('admin.posts.edit', $post))->assertOk()
        ->assertSee('<script>title()</script>')->assertDontSee('<script>title()</script>', false)
        ->assertSee('</textarea><script>body()</script>')->assertDontSee('</textarea><script>body()</script>', false);
});

test('dashboard retains catalog stats and adds truthful content counts', function () {
    Post::factory()->published()->create();
    Post::factory()->scheduled()->create();
    Post::factory()->create();

    $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertOk()
        ->assertViewHas('contentStats', ['total' => 3, 'published' => 1, 'draft' => 1, 'categories' => 3])
        ->assertViewHas('stats', ['total_products' => 0, 'active_products' => 0, 'inactive_products' => 0, 'total_categories' => 0]);
});
