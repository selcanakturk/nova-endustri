<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;

test('dashboard displays real catalog counts and recent products', function () {
    $category = Category::factory()->create();
    Product::factory()->count(2)->for($category)->create();
    $recent = Product::factory()->inactive()->for($category)->create(['name' => 'Recent draft']);

    $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertOk()
        ->assertViewHas('stats', ['total_products' => 3, 'active_products' => 2, 'inactive_products' => 1, 'total_categories' => 1])
        ->assertSeeText('Recent draft')
        ->assertViewHas('recentProducts', fn ($products): bool => $products->first()->is($recent));
});

test('administrators can open catalog forms lists and delete confirmations', function (string $path) {
    $product = Product::factory()->create();
    $id = str_contains($path, 'categories') ? $product->category_id : $product->id;

    $this->actingAs(User::factory()->admin()->create())->get(sprintf($path, $id))->assertOk();
})->with([
    ['/admin/products'], ['/admin/products/create'], ['/admin/products/%d/edit'], ['/admin/products/%d/delete'],
    ['/admin/categories'], ['/admin/categories/create'], ['/admin/categories/%d/edit'], ['/admin/categories/%d/delete'],
]);

test('empty admin lists and dashboard display useful next steps', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->get('/admin')->assertOk()->assertSeeText('İlk ürününüzle başlayın.');
    $this->get('/admin/categories')->assertOk()->assertSeeText('Henüz kategori yok.');
    $this->get('/admin/products')->assertOk()->assertSeeText('Görüntülenecek ürün bulunamadı.');
    $this->get('/admin/products/create')->assertOk()->assertSeeText('bir kategori oluşturun');
});

test('administrators create categories and can generate a Turkish slug from the name', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/admin/categories', adminCategoryData(['name' => 'Özel Üretim', 'slug' => '']))
        ->assertRedirect()->assertSessionHas('status', 'Kategori oluşturuldu.');

    $this->assertDatabaseHas('categories', ['name' => 'Özel Üretim', 'slug' => 'ozel-uretim', 'is_active' => true]);
});

test('category updates preserve identity and hide or restore products on the public site', function () {
    $product = Product::factory()->create(['slug' => 'visibility-product', 'name' => 'Visibility product']);
    $category = $product->category;
    $this->actingAs(User::factory()->admin()->create());

    $this->put(route('admin.categories.update', $category), adminCategoryData(['slug' => $category->slug, 'is_active' => '0', 'sort_order' => '7']))
        ->assertRedirect(route('admin.categories.edit', $category))->assertSessionHas('status', 'Kategori güncellendi.');
    $this->get('/urunler')->assertOk()->assertDontSeeText('Visibility product');
    $this->get('/urunler/visibility-product')->assertNotFound();

    $this->put(route('admin.categories.update', $category), adminCategoryData(['slug' => $category->slug]))->assertRedirect();
    $this->get('/urunler/visibility-product')->assertOk();
    $this->assertDatabaseCount('categories', 1);
});

test('empty categories can be deleted and categories with products cannot be deleted', function () {
    $emptyCategory = Category::factory()->create();
    $product = Product::factory()->create();
    $this->actingAs(User::factory()->admin()->create());

    $this->delete(route('admin.categories.destroy', $emptyCategory))->assertRedirect(route('admin.categories.index'))->assertSessionHas('status', 'Kategori silindi.');
    $this->assertModelMissing($emptyCategory);

    $this->delete(route('admin.categories.destroy', $product->category))->assertRedirect(route('admin.categories.index'))
        ->assertSessionHas('error', 'Bu kategoriye bağlı ürünler var. Önce ürünleri başka bir kategoriye taşıyın.');
    $this->assertModelExists($product->category);
    $this->assertModelExists($product);
});

test('category delete confirmation explains blocked deletion without deleting anything', function () {
    $product = Product::factory()->create();

    $this->actingAs(User::factory()->admin()->create())->get(route('admin.categories.delete', $product->category))
        ->assertOk()->assertSeeText('Kategori şu anda silinemez.')->assertDontSeeText('Evet, sil');
    $this->assertModelExists($product->category);
});

test('category slug conflicts produce Turkish validation errors', function () {
    Category::factory()->create(['slug' => 'existing']);

    $this->actingAs(User::factory()->admin()->create())->post('/admin/categories', adminCategoryData(['slug' => 'existing']))
        ->assertSessionHasErrors(['slug' => 'Slug zaten kullanılıyor.']);
    $this->assertDatabaseCount('categories', 1);
});

test('category validation rejects invalid values without creating a record', function (array $overrides, string $field, string $message) {
    $this->actingAs(User::factory()->admin()->create())->post('/admin/categories', adminCategoryData($overrides))
        ->assertSessionHasErrors([$field => $message]);
    $this->assertDatabaseCount('categories', 0);
})->with([
    [['name' => ''], 'name', 'Ad alanı zorunludur.'],
    [['name' => ['bad']], 'name', 'Ad metin olmalıdır.'],
    [['name' => str_repeat('x', 256)], 'name', 'Ad en fazla 255 karakter olabilir.'],
    [['slug' => 'invalid/slug'], 'slug', 'Slug yalnızca küçük harf, rakam ve aralarda kısa çizgi içerebilir.'],
    [['sort_order' => -1], 'sort_order', 'Sıralama 0 ile 2147483647 arasında olmalıdır.'],
    [['is_active' => 'invalid'], 'is_active', 'Aktiflik için geçerli bir seçim yapın.'],
    [['description' => str_repeat('x', 10001)], 'description', 'Açıklama en fazla 10000 karakter olabilir.'],
]);

test('admin product creation immediately publishes a featured catalog entry with its detail page', function () {
    $category = Category::factory()->create();

    $this->actingAs(User::factory()->admin()->create())->post('/admin/products', adminProductData($category, ['is_featured' => '1']))
        ->assertRedirect()->assertSessionHas('status', 'Ürün oluşturuldu.');

    $this->assertDatabaseHas('products', ['name' => 'Yeni ürün', 'slug' => 'yeni-urun', 'sku' => 'NOVA-TEST', 'is_featured' => true, 'is_active' => true]);
    $this->get('/urunler')->assertOk()->assertSeeText('Yeni ürün');
    $this->get('/urunler/yeni-urun')->assertOk()->assertSeeText('Ürün detaylı açıklaması.');
    $this->get('/')->assertOk()->assertSeeText('Yeni ürün');
});

test('admin can update every product field including category slug sku featured status and ordering', function () {
    $product = Product::factory()->create(['slug' => 'old-slug']);
    $category = Category::factory()->create();

    $this->actingAs(User::factory()->admin()->create())->put(route('admin.products.update', $product), adminProductData($category, [
        'name' => 'Edited product', 'is_featured' => '1', 'sort_order' => '11',
    ]))->assertRedirect(route('admin.products.edit', $product));

    $this->assertDatabaseHas('products', [
        'id' => $product->id, 'category_id' => $category->id, 'name' => 'Edited product', 'slug' => 'yeni-urun',
        'sku' => 'NOVA-TEST', 'short_description' => 'Ürün özeti.', 'description' => 'Ürün detaylı açıklaması.',
        'is_featured' => true, 'is_active' => true, 'sort_order' => 11,
    ]);
    $this->get('/urunler/old-slug')->assertNotFound();
    $this->get('/urunler/yeni-urun')->assertOk()->assertSeeText('Edited product');
});

test('product updates allow their own slug and sku without allowing another products identifiers', function () {
    $product = Product::factory()->create(['sku' => 'SAME-SKU']);
    $other = Product::factory()->for($product->category)->create(['sku' => 'OTHER-SKU']);
    $this->actingAs(User::factory()->admin()->create());

    $this->put(route('admin.products.update', $product), adminProductData($product->category, ['slug' => $product->slug, 'sku' => $product->sku]))->assertRedirect();
    $this->put(route('admin.products.update', $product), adminProductData($product->category, ['id' => $other->id, 'slug' => $other->slug, 'sku' => $other->sku]))
        ->assertSessionHasErrors(['slug' => 'Slug zaten kullanılıyor.', 'sku' => 'SKU zaten kullanılıyor.']);
    expect($product->fresh()->sku)->toBe('SAME-SKU');
});

test('product creation rejects duplicate slug and sku values', function () {
    $product = Product::factory()->create(['slug' => 'existing-product', 'sku' => 'EXISTING']);

    $this->actingAs(User::factory()->admin()->create())->post('/admin/products', adminProductData($product->category, ['slug' => $product->slug, 'sku' => $product->sku]))
        ->assertSessionHasErrors(['slug' => 'Slug zaten kullanılıyor.', 'sku' => 'SKU zaten kullanılıyor.']);
    $this->assertDatabaseCount('products', 1);
});

test('product validation rejects invalid fields without creating records', function (array $overrides, string $field, string $message) {
    $category = Category::factory()->create();

    $this->actingAs(User::factory()->admin()->create())->post('/admin/products', adminProductData($category, $overrides))
        ->assertSessionHasErrors([$field => $message]);
    $this->assertDatabaseCount('products', 0);
})->with([
    [['name' => ''], 'name', 'Ad alanı zorunludur.'],
    [['category_id' => 999], 'category_id', 'Geçerli bir kategori seçin.'],
    [['category_id' => null], 'category_id', 'Kategori alanı zorunludur.'],
    [['short_description' => str_repeat('x', 1001)], 'short_description', 'Kısa açıklama en fazla 1000 karakter olabilir.'],
    [['description' => str_repeat('x', 50001)], 'description', 'Açıklama en fazla 50000 karakter olabilir.'],
    [['sku' => str_repeat('x', 101)], 'sku', 'SKU en fazla 100 karakter olabilir.'],
    [['sort_order' => 'wrong'], 'sort_order', 'Sıralama tam sayı olmalıdır.'],
    [['is_featured' => 'yes'], 'is_featured', 'Öne çıkan ürün için geçerli bir seçim yapın.'],
    [['is_active' => 'yes'], 'is_active', 'Aktiflik için geçerli bir seçim yapın.'],
    [['remove_image' => 'yes'], 'remove_image', 'Görseli kaldır için geçerli bir seçim yapın.'],
]);

test('deactivating products removes them from the catalog details and home', function () {
    $product = Product::factory()->featured()->create(['name' => 'Hidden test product', 'slug' => 'hidden-test-product']);

    $this->actingAs(User::factory()->admin()->create())->put(route('admin.products.update', $product), adminProductData($product->category, [
        'name' => $product->name, 'slug' => $product->slug, 'is_active' => '0', 'is_featured' => '1',
    ]))->assertRedirect();

    $this->get('/urunler')->assertOk()->assertDontSeeText('Hidden test product');
    $this->get('/urunler/hidden-test-product')->assertNotFound();
    $this->get('/')->assertOk()->assertDontSeeText('Hidden test product');
    $this->get('/admin/products')->assertOk()->assertSeeText('Hidden test product');
});

test('product deletion removes the record and public detail', function () {
    $product = Product::factory()->create(['slug' => 'deleted-product']);

    $this->actingAs(User::factory()->admin()->create())->delete(route('admin.products.destroy', $product))
        ->assertRedirect(route('admin.products.index'))->assertSessionHas('status', 'Ürün silindi.');

    $this->assertModelMissing($product);
    $this->get('/urunler/deleted-product')->assertNotFound();
});

test('product search supports name sku category and inactive status together', function () {
    $category = Category::factory()->create();
    Product::factory()->inactive()->for($category)->create(['name' => 'Matching part', 'sku' => 'BOLT-42']);
    Product::factory()->for($category)->create(['name' => 'Wrong status', 'sku' => 'BOLT-43']);
    Product::factory()->inactive()->create(['name' => 'Wrong category', 'sku' => 'BOLT-44']);
    Product::factory()->inactive()->for($category)->create(['name' => 'Wrong search', 'sku' => 'OTHER-1']);

    $this->actingAs(User::factory()->admin()->create())->get('/admin/products?'.http_build_query(['q' => 'BOLT', 'category_id' => $category->id, 'status' => 'inactive']))
        ->assertOk()->assertSeeText('Matching part')->assertDontSeeText('Wrong status')->assertDontSeeText('Wrong category')->assertDontSeeText('Wrong search');
});

test('admin pagination preserves search and category filters', function () {
    $category = Category::factory()->create();
    Product::factory()->count(16)->for($category)->sequence(fn ($sequence): array => ['name' => 'Part '.$sequence->index, 'sort_order' => $sequence->index])->create();

    $this->actingAs(User::factory()->admin()->create())->get('/admin/products?'.http_build_query(['q' => 'Part', 'category_id' => $category->id]))
        ->assertOk()->assertSeeText('Part 0')->assertDontSeeText('Part 15')->assertSee('q=Part&amp;category_id='.$category->id.'&amp;page=2', false);
});

test('admin list filters reject malformed values with Turkish errors', function () {
    $this->actingAs(User::factory()->admin()->create())->get('/admin/products?status=bad&category_id=999&q[]=wrong')
        ->assertSessionHasErrors(['status' => 'Geçerli bir durum filtresi seçin.', 'category_id' => 'Geçerli bir kategori seçin.', 'q' => 'Arama metin olmalıdır.']);
});

test('catalog forms safely redisplay invalid array input', function () {
    $this->actingAs(User::factory()->admin()->create())->followingRedirects()->from('/admin/categories/create')
        ->post('/admin/categories', adminCategoryData(['name' => ['bad']]))->assertOk()->assertSeeText('Ad metin olmalıdır.');
});

test('admin forms tables and confirmations escape stored markup', function () {
    $product = Product::factory()->create(['name' => '<script>attack()</script>', 'description' => '</textarea><script>attack()</script>']);
    $this->actingAs(User::factory()->admin()->create());

    foreach ([route('admin.products.index'), route('admin.products.edit', $product), route('admin.products.delete', $product)] as $url) {
        $this->get($url)->assertOk()->assertSee('<script>attack()</script>')->assertDontSee('<script>attack()</script>', false);
    }
});
