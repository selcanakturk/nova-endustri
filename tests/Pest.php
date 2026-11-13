<?php

use App\Models\Category;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/** @param array<string, mixed> $overrides
 * @return array<string, mixed>
 */
function adminProductData(Category $category, array $overrides = []): array
{
    return array_replace([
        'category_id' => $category->id, 'name' => 'Yeni ürün', 'slug' => 'yeni-urun', 'sku' => 'NOVA-TEST',
        'short_description' => 'Ürün özeti.', 'description' => 'Ürün detaylı açıklaması.',
        'is_active' => '1', 'is_featured' => '0', 'sort_order' => '0',
    ], $overrides);
}

/** @param array<string, mixed> $overrides
 * @return array<string, mixed>
 */
function adminCategoryData(array $overrides = []): array
{
    return array_replace(['name' => 'Yeni kategori', 'slug' => 'yeni-kategori', 'description' => 'Kategori açıklaması.', 'is_active' => '1', 'sort_order' => '0'], $overrides);
}
