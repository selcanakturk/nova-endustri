<?php

use App\Models\Category;
use App\Models\Product;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\DatabaseSeeder;

test('catalog seed preserves the six original examples and three categories without duplicates', function () {
    $this->seed(CatalogSeeder::class);
    $this->seed(CatalogSeeder::class);

    $this->assertDatabaseCount('categories', 3);
    $this->assertDatabaseCount('products', 6);
    $this->assertDatabaseHas('categories', ['name' => 'Talaşlı imalat', 'slug' => 'talasli-imalat']);
    $this->assertDatabaseHas('products', [
        'name' => 'Hassas işlenmiş parçalar', 'slug' => 'hassas-islenmis-parcalar',
        'image' => 'images/precision.svg', 'is_featured' => true, 'sort_order' => 0,
        'description' => 'Makine ve ekipmanlarda kullanılmak üzere teknik resme göre şekillendirilen metal bileşenler.',
    ]);
    expect(Product::where('slug', 'hassas-islenmis-parcalar')->firstOrFail()->category->slug)->toBe('talasli-imalat');
    expect(Product::featured()->count())->toBe(3);
});

test('seeding leaves edited demo records and unrelated records unchanged', function () {
    $this->seed(CatalogSeeder::class);
    $category = Category::where('slug', 'talasli-imalat')->firstOrFail();
    $category->update(['name' => 'Edited category', 'is_active' => false, 'sort_order' => 99]);
    $product = Product::where('slug', 'hassas-islenmis-parcalar')->firstOrFail();
    $product->update(['name' => 'Edited product', 'is_active' => false, 'image' => null, 'description' => 'Edited description']);
    $unrelated = Product::factory()->create(['name' => 'Custom product']);

    $this->seed(CatalogSeeder::class);

    $this->assertDatabaseCount('categories', 4);
    $this->assertDatabaseCount('products', 7);
    expect($category->fresh())->name->toBe('Edited category')->is_active->toBeFalse()->sort_order->toBe(99);
    expect($product->fresh())->name->toBe('Edited product')->is_active->toBeFalse()->image->toBeNull()->description->toBe('Edited description');
    $this->assertModelExists($unrelated);
});

test('default database seeder fills the catalog without creating login accounts', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    $this->assertDatabaseCount('products', 6);
    $this->assertDatabaseCount('categories', 3);
    $this->assertDatabaseCount('users', 0);
});
