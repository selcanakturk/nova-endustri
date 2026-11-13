<?php

use App\CatalogImage;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('product uploads use unique public storage paths and render on the public detail', function (string $extension) {
    Storage::fake('public');
    $category = Category::factory()->create();
    $file = UploadedFile::fake()->image('original.'.$extension);

    $this->actingAs(User::factory()->admin()->create())->post('/admin/products', adminProductData($category, ['image' => $file]))
        ->assertRedirect()->assertSessionHasNoErrors();

    $product = Product::where('slug', 'yeni-urun')->firstOrFail();
    Storage::disk('public')->assertExists($product->image);
    expect($product->image)->toStartWith('catalog/')->not->toContain('original');
    $this->get('/urunler/yeni-urun')->assertOk()->assertSee('src="'.Storage::disk('public')->url($product->image).'"', false);
})->with(['jpg', 'png', 'webp']);

test('categories support uploaded images and display their previews', function () {
    Storage::fake('public');

    $this->actingAs(User::factory()->admin()->create())->post('/admin/categories', adminCategoryData(['image' => UploadedFile::fake()->image('category.png')]))
        ->assertRedirect()->assertSessionHasNoErrors();

    $category = Category::where('slug', 'yeni-kategori')->firstOrFail();
    Storage::disk('public')->assertExists($category->image);
    $this->get(route('admin.categories.edit', $category))->assertOk()->assertSee($category->image_url);
});

test('replacing an unshared uploaded product image removes only the old file', function () {
    Storage::fake('public');
    Storage::disk('public')->put('catalog/old.png', 'old image');
    $product = Product::factory()->create(['image' => 'catalog/old.png']);

    $this->actingAs(User::factory()->admin()->create())->put(route('admin.products.update', $product), adminProductData($product->category, ['image' => UploadedFile::fake()->image('replacement.png')]))
        ->assertRedirect()->assertSessionHasNoErrors();

    Storage::disk('public')->assertMissing('catalog/old.png');
    Storage::disk('public')->assertExists($product->fresh()->image);
});

test('replacing a shared product image retains the file used by another product', function () {
    Storage::fake('public');
    Storage::disk('public')->put('catalog/shared.png', 'shared image');
    $product = Product::factory()->create(['image' => 'catalog/shared.png']);
    $other = Product::factory()->for($product->category)->create(['image' => 'catalog/shared.png']);

    $this->actingAs(User::factory()->admin()->create())->put(route('admin.products.update', $product), adminProductData($product->category, ['image' => UploadedFile::fake()->image('replacement.png')]))
        ->assertRedirect();

    Storage::disk('public')->assertExists('catalog/shared.png');
    expect($other->fresh()->image)->toBe('catalog/shared.png');
});

test('removing a product image preserves references from categories', function () {
    Storage::fake('public');
    Storage::disk('public')->put('catalog/shared.png', 'shared image');
    $category = Category::factory()->create(['image' => 'catalog/shared.png']);
    $product = Product::factory()->for($category)->create(['image' => 'catalog/shared.png']);

    $this->actingAs(User::factory()->admin()->create())->put(route('admin.products.update', $product), adminProductData($category, ['remove_image' => '1']))
        ->assertRedirect();

    expect($product->fresh()->image)->toBeNull();
    Storage::disk('public')->assertExists('catalog/shared.png');
});

test('removing a category image preserves references from products', function () {
    Storage::fake('public');
    Storage::disk('public')->put('catalog/shared.png', 'shared image');
    $category = Category::factory()->create(['image' => 'catalog/shared.png']);
    Product::factory()->for($category)->create(['image' => 'catalog/shared.png']);

    $this->actingAs(User::factory()->admin()->create())->put(route('admin.categories.update', $category), adminCategoryData(['remove_image' => '1']))
        ->assertRedirect();

    expect($category->fresh()->image)->toBeNull();
    Storage::disk('public')->assertExists('catalog/shared.png');
});

test('removing the final uploaded image reference deletes its file and restores the placeholder', function () {
    Storage::fake('public');
    Storage::disk('public')->put('catalog/old.png', 'old image');
    $product = Product::factory()->create(['image' => 'catalog/old.png']);

    $this->actingAs(User::factory()->admin()->create())->put(route('admin.products.update', $product), adminProductData($product->category, ['remove_image' => '1']))
        ->assertRedirect();

    Storage::disk('public')->assertMissing('catalog/old.png');
    expect($product->fresh()->image_url)->toBe(asset('images/product-placeholder.svg'));
});

test('editing without uploading or removing keeps the existing image', function () {
    Storage::fake('public');
    Storage::disk('public')->put('catalog/keep.png', 'existing image');
    $product = Product::factory()->create(['image' => 'catalog/keep.png']);

    $this->actingAs(User::factory()->admin()->create())->put(route('admin.products.update', $product), adminProductData($product->category))
        ->assertRedirect();

    expect($product->fresh()->image)->toBe('catalog/keep.png');
    Storage::disk('public')->assertExists('catalog/keep.png');
});

test('replacing demo artwork never deletes the original SVG', function () {
    Storage::fake('public');
    $product = Product::factory()->create(['image' => 'images/precision.svg']);

    $this->actingAs(User::factory()->admin()->create())->put(route('admin.products.update', $product), adminProductData($product->category, ['image' => UploadedFile::fake()->image('new.png')]))
        ->assertRedirect();

    expect(is_file(public_path('images/precision.svg')))->toBeTrue();
    Storage::disk('public')->assertExists($product->fresh()->image);
});

test('deleting a product removes an unused upload but not shared files', function () {
    Storage::fake('public');
    Storage::disk('public')->put('catalog/shared.png', 'shared');
    Storage::disk('public')->put('catalog/unshared.png', 'unshared');
    $shared = Product::factory()->create(['image' => 'catalog/shared.png']);
    $other = Product::factory()->for($shared->category)->create(['image' => 'catalog/shared.png']);
    $unshared = Product::factory()->for($shared->category)->create(['image' => 'catalog/unshared.png']);
    $this->actingAs(User::factory()->admin()->create());

    $this->delete(route('admin.products.destroy', $shared))->assertRedirect();
    Storage::disk('public')->assertExists('catalog/shared.png');
    $this->assertModelExists($other);

    $this->delete(route('admin.products.destroy', $unshared))->assertRedirect();
    Storage::disk('public')->assertMissing('catalog/unshared.png');
});

test('deleting an empty category cleans up its unused upload', function () {
    Storage::fake('public');
    Storage::disk('public')->put('catalog/category.png', 'image');
    $category = Category::factory()->create(['image' => 'catalog/category.png']);

    $this->actingAs(User::factory()->admin()->create())->delete(route('admin.categories.destroy', $category))->assertRedirect();

    Storage::disk('public')->assertMissing('catalog/category.png');
    $this->assertModelMissing($category);
});

test('unsafe uploads are rejected before writing files or records', function (string $fileType) {
    Storage::fake('public');
    $category = Category::factory()->create();
    $validImage = UploadedFile::fake()->image('valid.png');
    $file = match ($fileType) {
        'svg' => UploadedFile::fake()->createWithContent('drawing.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
        'html' => UploadedFile::fake()->createWithContent('picture.jpg', '<html><script>alert(1)</script></html>')->mimeType('text/html'),
        'oversized' => UploadedFile::fake()->image('large.png')->size(4097),
        'wrong-extension' => new UploadedFile($validImage->getPathname(), 'picture.php', 'image/png', null, true),
    };

    $this->actingAs(User::factory()->admin()->create())->post('/admin/products', adminProductData($category, ['image' => $file]))
        ->assertSessionHasErrors('image');

    $this->assertDatabaseCount('products', 0);
    expect(Storage::disk('public')->allFiles())->toBe([]);
})->with(['svg', 'html', 'oversized', 'wrong-extension']);

test('image size validation displays the Turkish limit', function () {
    Storage::fake('public');

    $this->actingAs(User::factory()->admin()->create())->post('/admin/categories', adminCategoryData(['image' => UploadedFile::fake()->image('large.png')->size(4097)]))
        ->assertSessionHasErrors(['image' => 'Görsel en fazla 4 MB olabilir.']);
    $this->assertDatabaseCount('categories', 0);
});

test('raw image path input cannot replace or delete an existing file', function () {
    Storage::fake('public');
    Storage::disk('public')->put('catalog/keep.png', 'existing image');
    $product = Product::factory()->create(['image' => 'catalog/keep.png']);

    $this->actingAs(User::factory()->admin()->create())->put(route('admin.products.update', $product), adminProductData($product->category, ['image' => '../../.env']))
        ->assertSessionHasErrors('image');

    expect($product->fresh()->image)->toBe('catalog/keep.png');
    Storage::disk('public')->assertExists('catalog/keep.png');
});

test('failed persistence cleans up a newly stored upload', function () {
    Storage::fake('public');
    $existing = Product::factory()->create(['slug' => 'duplicate-slug']);

    expect(fn () => CatalogImage::save(new Product, adminProductData($existing->category, ['slug' => 'duplicate-slug']), UploadedFile::fake()->image('new.png'), false))
        ->toThrow(QueryException::class);

    expect(Storage::disk('public')->allFiles())->toBe([]);
    $this->assertModelExists($existing);
});

test('missing uploaded images fall back without exposing other paths', function () {
    Storage::fake('public');
    $product = Product::factory()->make(['image' => 'catalog/missing.png']);
    $category = Category::factory()->make(['image' => 'catalog/../private.png']);

    expect($product->image_url)->toBe(asset('images/product-placeholder.svg'));
    expect($category->image_url)->toBe(asset('images/product-placeholder.svg'));
});
