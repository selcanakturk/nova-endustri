<?php

use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Product;
use App\Models\User;

test('visible articles and news have separate lists and detail routes', function () {
    $article = Post::factory()->published()->create(['title' => 'Article sentinel']);
    $news = Post::factory()->published()->news()->create(['title' => 'News sentinel']);

    $this->get('/blog')->assertOk()->assertSeeText('Article sentinel')->assertDontSeeText('News sentinel');
    $this->get('/haberler')->assertOk()->assertSeeText('News sentinel')->assertDontSeeText('Article sentinel');
    $this->get('/blog/'.$article->slug)->assertOk();
    $this->get('/haberler/'.$article->slug)->assertNotFound();
    $this->get('/haberler/'.$news->slug)->assertOk();
    $this->get('/blog/'.$news->slug)->assertNotFound();
});

test('hidden contents are excluded from all public surfaces even with a known slug', function (string $status, ?string $time, bool $categoryActive) {
    $this->freezeTime();
    $category = PostCategory::factory()->create(['is_active' => $categoryActive]);
    $post = Post::factory()->for($category, 'category')->create([
        'title' => 'Secret sentinel', 'slug' => 'secret-sentinel', 'status' => $status,
        'published_at' => $time === null ? null : now()->modify($time), 'is_featured' => true,
    ]);

    $this->get('/blog')->assertOk()->assertDontSeeText('Secret sentinel');
    $this->get('/blog/secret-sentinel')->assertNotFound();
    $this->get('/')->assertOk()->assertDontSeeText('Secret sentinel');
    $this->get('/sitemap.xml')->assertOk()->assertDontSee($post->public_url);
})->with([
    'draft' => ['draft', '-1 day', true], 'future' => ['published', '+1 day', true],
    'no publication date' => ['published', null, true], 'inactive category' => ['published', '-1 day', false],
]);

test('publication becomes visible exactly at its scheduled time', function () {
    $this->freezeTime();
    $post = Post::factory()->published()->create(['published_at' => now()->addMinute()]);

    $this->get($post->public_url)->assertNotFound();
    $this->travel(1)->minutes();
    $this->get($post->public_url)->assertOk();
});

test('public category filters return matching articles and controlled errors for invalid categories', function () {
    $category = PostCategory::factory()->create(['slug' => 'engineering']);
    Post::factory()->published()->for($category, 'category')->create(['title' => 'Matching article']);
    Post::factory()->published()->create(['title' => 'Unrelated article']);
    PostCategory::factory()->inactive()->create(['slug' => 'inactive']);

    $this->get('/blog?category=engineering')->assertOk()->assertSeeText('Matching article')->assertDontSeeText('Unrelated article');
    $this->get('/blog?category=missing')->assertNotFound();
    $this->get('/blog?category=inactive')->assertNotFound();
    $this->get('/blog?category[]=engineering')->assertNotFound();
});

test('article pagination retains its category and has stable publication ordering', function () {
    $category = PostCategory::factory()->create(['slug' => 'engineering']);
    Post::factory()->published()->count(10)->for($category, 'category')
        ->sequence(fn ($sequence): array => ['title' => 'Article '.$sequence->index, 'published_at' => now()->subDays($sequence->index + 1)])
        ->create();

    $this->get('/blog?category=engineering')->assertOk()->assertSeeText('Article 0')->assertDontSeeText('Article 9')
        ->assertSee('category=engineering&amp;page=2', false);
    $this->get('/blog?category=engineering&page=2')->assertOk()->assertSeeText('Article 9')->assertDontSeeText('Article 0');
});

test('featured articles appear separately without duplication on later pages', function () {
    $category = PostCategory::factory()->create();
    $author = User::factory()->admin()->create();
    Post::factory()->published()->featured()->for($category, 'category')->for($author, 'author')->create(['title' => 'Featured article']);
    Post::factory()->published()->count(10)->for($category, 'category')->for($author, 'author')->create();

    $this->get('/blog')->assertOk()->assertSeeText('Featured article')->assertSeeText('ÖNE ÇIKAN İÇERİK');
    $this->get('/blog?page=2')->assertOk()->assertDontSeeText('Featured article');
});

test('article details expose author safe formatted content and only related visible articles', function () {
    $post = Post::factory()->published()->create(['content' => "## Heading\n\n**Bold** and *italic*.\n\n- Item\n\n1. Number\n\n> Quote\n\n[Safe](https://example.com)"]);
    Post::factory()->published()->for($post->category, 'category')->create(['title' => 'Related article']);
    Post::factory()->for($post->category, 'category')->create(['title' => 'Draft related']);
    Post::factory()->published()->news()->for($post->category, 'category')->create(['title' => 'News related']);

    $response = $this->get($post->public_url)->assertOk()->assertSeeText($post->author->name)
        ->assertSeeText('Related article')->assertDontSeeText('Draft related')->assertDontSeeText('News related');
    foreach (['<h2>Heading</h2>', '<strong>Bold</strong>', '<em>italic</em>', '<ul>', '<ol>', '<blockquote>', 'href="https://example.com"'] as $html) {
        $response->assertSee($html, false);
    }
});

test('markdown raw HTML and unsafe links never execute on article pages', function (string $content) {
    $post = Post::factory()->published()->create(['content' => $content]);

    $this->get($post->public_url)->assertOk()
        ->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('onerror=', false)
        ->assertDontSee('onload=', false)->assertDontSee('href="javascript:', false)->assertDontSee('href="data:', false);
})->with([
    ['<script>alert(1)</script>'], ['<img src=x onerror=alert(1)>'], ['<svg onload=alert(1)></svg>'],
    ['[bad](javascript:alert%281%29)'], ['[bad](jav&#x61;script:alert%281%29)'], ['[bad](data:text/html;base64,PHNjcmlwdD4=)'],
]);

test('markdown headings preserve the page single H1 hierarchy', function () {
    $post = Post::factory()->published()->create(['content' => "# Content heading\n\n###### Deep heading"]);

    $this->get($post->public_url)->assertOk()->assertSee('<h2>Content heading</h2>', false)->assertSee('<h3>Deep heading</h3>', false);
});

test('SEO metadata uses configured fields and a clean canonical URL', function () {
    $post = Post::factory()->published()->create(['meta_title' => 'SEO title', 'meta_description' => 'SEO description', 'cover_image' => 'images/editorial.svg']);

    $this->get($post->public_url.'?tracking=ignored')->assertOk()
        ->assertSee('<title>SEO title | Nova Endüstri</title>', false)
        ->assertSee('<meta name="description" content="SEO description">', false)
        ->assertSee('<link rel="canonical" href="'.$post->public_url.'">', false)
        ->assertSee('<meta property="og:image" content="'.$post->image_url.'">', false)
        ->assertSee('<meta property="og:type" content="article">', false);
});

test('SEO fields and author metadata are escaped', function () {
    $post = Post::factory()->published()->create(['title' => '<script>title()</script>', 'excerpt' => '<script>excerpt()</script>', 'meta_title' => '<script>seo()</script>']);
    $post->author->update(['name' => '<script>author()</script>']);

    $response = $this->get($post->public_url)->assertOk();
    foreach (['title', 'excerpt', 'seo', 'author'] as $field) {
        $response->assertSee('<script>'.$field.'()</script>')->assertDontSee('<script>'.$field.'()</script>', false);
    }
});

test('home shows exactly the latest three visible articles and news without blank cards', function () {
    $this->freezeTime();
    $category = PostCategory::factory()->create();
    Post::factory()->published()->count(4)->for($category, 'category')
        ->sequence(fn ($sequence): array => ['title' => 'Home post '.$sequence->index, 'published_at' => now()->subDays($sequence->index + 1), 'type' => $sequence->index % 2 === 0 ? 'article' : 'news'])->create();
    Post::factory()->scheduled()->create(['title' => 'Future home post']);

    $this->get('/')->assertOk()->assertSeeInOrder(['Home post 0', 'Home post 1', 'Home post 2'])
        ->assertDontSeeText('Home post 3')->assertDontSeeText('Future home post');
});

test('home omits the editorial section when no contents are visible', function () {
    $this->get('/')->assertOk()->assertDontSeeText('Nova’dan Haberler ve İçgörüler');
    $this->get('/blog')->assertOk()->assertSeeText('Henüz yayınlanmış içerik bulunmuyor.');
    $this->get('/haberler')->assertOk()->assertSeeText('Henüz yayınlanmış içerik bulunmuyor.');
});

test('XML sitemap includes visible public pages products and correctly typed post URLs only', function () {
    $article = Post::factory()->published()->create();
    $news = Post::factory()->published()->news()->create();
    $draft = Post::factory()->create();
    $future = Post::factory()->scheduled()->create();
    $hiddenCategory = Post::factory()->published()->for(PostCategory::factory()->inactive(), 'category')->create();
    $product = Product::factory()->create();
    $hiddenProduct = Product::factory()->inactive()->create();

    $response = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee($article->public_url)->assertSee($news->public_url)->assertSee(route('products.show', $product->slug))
        ->assertDontSee($draft->public_url)->assertDontSee($future->public_url)->assertDontSee($hiddenCategory->public_url)
        ->assertDontSee(route('products.show', $hiddenProduct->slug))->assertDontSee('/admin');
    expect(simplexml_load_string($response->getContent()))->not->toBeFalse();
});
