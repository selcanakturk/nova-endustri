<?php

use App\Models\Category;
use App\Models\Product;

test('catalog lists database products in stable order and eager loads categories', function () {
    $category = Category::factory()->create();
    Product::factory()->for($category)->create(['name' => 'Last component', 'sort_order' => 9]);
    Product::factory()->for($category)->create(['name' => 'First component', 'sort_order' => 1]);
    Product::factory()->for($category)->create(['name' => 'Second component', 'sort_order' => 1]);
    $this->expectsDatabaseQueryCount(4);

    $this->get('/urunler')
        ->assertOk()
        ->assertSeeInOrder(['First component', 'Second component', 'Last component'])
        ->assertViewHas('products', fn ($products): bool => $products->every(fn (Product $product): bool => $product->relationLoaded('category')));
});

test('catalog category filter returns only products in the selected active category', function () {
    $category = Category::factory()->create(['name' => 'Selected category', 'slug' => 'selected']);
    Product::factory()->for($category)->create(['name' => 'Matching component']);
    Product::factory()->create(['name' => 'Other component']);

    $this->get('/urunler?category=selected')
        ->assertOk()
        ->assertSeeText('Matching component')
        ->assertDontSeeText('Other component')
        ->assertViewHas('selectedCategory', fn (Category $selectedCategory): bool => $selectedCategory->is($category));
});

test('catalog hides inactive products and products belonging to inactive categories', function () {
    Product::factory()->create(['name' => 'Public component']);
    Product::factory()->inactive()->create(['name' => 'Draft component']);
    Product::factory()->for(Category::factory()->inactive())->create(['name' => 'Private category component']);

    $this->get('/urunler')
        ->assertOk()
        ->assertSeeText('Public component')
        ->assertDontSeeText('Draft component')
        ->assertDontSeeText('Private category component');
});

test('invalid category filters return 404 without revealing products', function (string $query) {
    $category = Category::factory()->inactive()->create(['slug' => 'inactive-category']);
    Product::factory()->for($category)->create();

    $this->get('/urunler?'.$query)->assertNotFound();
})->with([
    'unknown slug' => ['category=not-found'],
    'inactive category' => ['category=inactive-category'],
    'array value' => ['category[]=invalid'],
    'injection attempt' => ['category=%27%20OR%201%3D1--'],
]);

test('empty category parameter shows the unfiltered catalog', function () {
    Product::factory()->create(['name' => 'Available component']);

    $this->get('/urunler?category=')->assertOk()->assertSeeText('Available component');
});

test('category links use stable configured ordering and omit inactive categories', function () {
    Category::factory()->create(['name' => 'Final category', 'sort_order' => 5]);
    Category::factory()->create(['name' => 'First category', 'sort_order' => 1]);
    Category::factory()->create(['name' => 'Second category', 'sort_order' => 1]);
    Category::factory()->inactive()->create(['name' => 'Hidden category']);

    $this->get('/urunler')->assertOk()
        ->assertSeeInOrder(['First category', 'Second category', 'Final category'])
        ->assertDontSeeText('Hidden category');
});

test('pagination limits results and preserves category selection on the next page', function () {
    $category = Category::factory()->create(['slug' => 'machinery']);
    Product::factory()->count(13)->for($category)
        ->sequence(fn ($sequence): array => ['name' => 'Component '.$sequence->index, 'sort_order' => $sequence->index])
        ->create();

    $response = $this->get('/urunler?category=machinery');

    $response->assertOk()->assertSeeText('Component 0')->assertDontSeeText('Component 12')
        ->assertSee('category=machinery&amp;page=2', false);

    $this->get('/urunler?category=machinery&page=2')
        ->assertOk()->assertSeeText('Component 12')->assertDontSeeText('Component 0');
});

test('empty catalogs render a useful empty state', function () {
    $this->get('/urunler')->assertOk()->assertSeeText('Henüz görüntülenecek ürün bulunmuyor.');
});

test('active categories without products render an empty state', function () {
    Category::factory()->create(['slug' => 'empty-category']);

    $this->get('/urunler?category=empty-category')->assertOk()->assertSeeText('Henüz görüntülenecek ürün bulunmuyor.');
});

test('published product detail displays descriptions category sku and contact link', function () {
    $category = Category::factory()->create(['name' => 'Precision category']);
    $product = Product::factory()->for($category)->create([
        'name' => 'Test spindle', 'slug' => 'test-spindle', 'sku' => 'NOVA-01',
        'short_description' => 'Short technical summary.', 'description' => 'Detailed technical explanation.',
    ]);

    $this->get('/urunler/test-spindle')->assertOk()->assertViewIs('products.show')
        ->assertSeeText('Test spindle')->assertSeeText('Precision category')
        ->assertSeeText('Short technical summary.')->assertSeeText('Detailed technical explanation.')
        ->assertSeeText('NOVA-01')->assertSee('src="'.$product->image_url.'"', false)
        ->assertSee('href="'.route('contact').'"', false)
        ->assertSee('href="'.route('products').'"', false);
});

test('unpublished product detail returns 404', function (bool $productIsActive, bool $categoryIsActive) {
    $category = Category::factory()->create(['is_active' => $categoryIsActive]);
    Product::factory()->for($category)->create(['slug' => 'hidden-product', 'is_active' => $productIsActive]);

    $this->get('/urunler/hidden-product')->assertNotFound();
})->with([
    'inactive product' => [false, true],
    'inactive category' => [true, false],
    'both inactive' => [false, false],
]);

test('unknown product slug returns 404', function () {
    $this->get('/urunler/not-found')->assertNotFound();
});

test('missing optional detail fields render useful fallback content', function () {
    Product::factory()->create([
        'slug' => 'minimal-product', 'image' => null, 'sku' => null,
        'short_description' => null, 'description' => null,
    ]);

    $this->get('/urunler/minimal-product')->assertOk()
        ->assertSee('src="'.asset('images/product-placeholder.svg').'"', false)
        ->assertSeeText('Detaylı açıklama henüz eklenmedi.')
        ->assertDontSeeText('Ürün kodu:');
});

test('database content is escaped in catalog and product detail', function () {
    $category = Category::factory()->create(['name' => '<script>category()</script>']);
    Product::factory()->for($category)->create([
        'slug' => 'escaped-product', 'name' => '<script>name()</script>',
        'short_description' => '<script>summary()</script>',
        'description' => '<script>description()</script>', 'sku' => '<script>sku()</script>',
    ]);

    $this->get('/urunler')->assertOk()->assertSee('<script>name()</script>')
        ->assertSee('<script>summary()</script>')->assertSee('<script>category()</script>')
        ->assertDontSee('<script>name()</script>', false)
        ->assertDontSee('<script>summary()</script>', false)
        ->assertDontSee('<script>category()</script>', false);

    $this->get('/urunler/escaped-product')->assertOk()
        ->assertSee('<script>description()</script>')->assertSee('<script>sku()</script>')
        ->assertSee('<script>name()</script>')->assertSee('<script>summary()</script>')->assertSee('<script>category()</script>')
        ->assertDontSee('<script>description()</script>', false)->assertDontSee('<script>sku()</script>', false)
        ->assertDontSee('<script>name()</script>', false)->assertDontSee('<script>summary()</script>', false)
        ->assertDontSee('<script>category()</script>', false);
});

test('home shows at most three ordered published featured products from the database', function () {
    $category = Category::factory()->create();
    Product::factory()->featured()->for($category)->create(['name' => 'Last feature', 'sort_order' => 50]);
    Product::factory()->featured()->for($category)->create(['name' => 'First feature', 'sort_order' => 0]);
    Product::factory()->featured()->for($category)->create(['name' => 'Second feature', 'sort_order' => 0]);
    Product::factory()->featured()->for($category)->create(['name' => 'Third feature', 'sort_order' => 1]);
    Product::factory()->featured()->inactive()->for($category)->create(['name' => 'Inactive feature']);
    Product::factory()->featured()->for(Category::factory()->inactive())->create(['name' => 'Hidden category feature']);
    Product::factory()->for($category)->create(['name' => 'Ordinary product']);
    $this->expectsDatabaseQueryCount(3);

    $this->get('/')->assertOk()
        ->assertSeeInOrder(['First feature', 'Second feature', 'Third feature'])
        ->assertDontSeeText('Last feature')->assertDontSeeText('Inactive feature')
        ->assertDontSeeText('Hidden category feature')->assertDontSeeText('Ordinary product');
});

test('home remains available when there are no featured products', function () {
    $this->get('/')->assertOk()->assertSeeText('Öne çıkan ürünler yakında burada listelenecek.');
});
