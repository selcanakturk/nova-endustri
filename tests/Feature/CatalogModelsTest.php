<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\QueryException;

test('categories and products persist with typed attributes and both relationship directions', function () {
    $category = Category::factory()->create(['sort_order' => '3']);
    $product = Product::factory()->featured()->for($category)->create(['sort_order' => '7']);

    $this->assertModelExists($category);
    $this->assertModelExists($product);
    expect($category->products->sole()->is($product))->toBeTrue();
    expect($product->category->is($category))->toBeTrue();
    expect($category->fresh())->is_active->toBeTrue()->sort_order->toBe(3);
    expect($product->fresh())->is_active->toBeTrue()->is_featured->toBeTrue()->sort_order->toBe(7);
});

test('category slugs cannot collide', function () {
    Category::factory()->create(['slug' => 'unique-category']);

    expect(fn () => Category::factory()->create(['slug' => 'unique-category']))
        ->toThrow(QueryException::class);
});

test('product slugs cannot collide', function () {
    $product = Product::factory()->create(['slug' => 'unique-product']);

    expect(fn () => Product::factory()->for($product->category)->create(['slug' => 'unique-product']))
        ->toThrow(QueryException::class);
});

test('product sku values cannot collide', function () {
    $product = Product::factory()->create(['sku' => 'NOVA-001']);

    expect(fn () => Product::factory()->for($product->category)->create(['sku' => 'NOVA-001']))
        ->toThrow(QueryException::class);
});

test('multiple products may have no sku', function () {
    $category = Category::factory()->create();
    Product::factory()->count(2)->for($category)->create(['sku' => null]);

    $this->assertDatabaseCount('products', 2);
});

test('a category with products cannot be deleted accidentally', function () {
    $product = Product::factory()->create();

    expect(fn () => $product->category->delete())->toThrow(QueryException::class);
    $this->assertModelExists($product);
    $this->assertModelExists($product->category);
});

test('products cannot reference a missing category', function () {
    expect(fn () => Product::factory()->create(['category_id' => 999]))
        ->toThrow(QueryException::class);
});

test('missing and unsupported image paths use the local placeholder', function (?string $image) {
    $product = Product::factory()->make(['image' => $image]);

    expect($product->image_url)->toBe(asset('images/product-placeholder.svg'));
})->with([
    'no image' => [null],
    'empty image' => [''],
    'missing file' => ['images/missing-product.svg'],
    'path traversal' => ['images/../../.env'],
    'outside images' => ['images/../favicon.svg'],
    'external URL' => ['https://example.com/tracking.svg'],
    'script URL' => ['javascript:alert(1)'],
]);

test('an existing product image retains its local URL', function () {
    $product = Product::factory()->make(['image' => 'images/precision.svg']);

    expect($product->image_url)->toBe(asset('images/precision.svg'));
});
