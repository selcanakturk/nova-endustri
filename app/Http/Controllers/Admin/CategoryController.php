<?php

namespace App\Http\Controllers\Admin;

use App\CatalogImage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.categories.index', ['categories' => Category::withCount('products')->ordered()->paginate(15)]);
    }

    public function create(): View
    {
        return view('admin.categories.form', ['category' => new Category(['is_active' => true, 'sort_order' => 0])]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $category = new Category;
        CatalogImage::save($category, $request->safe()->except(['image', 'remove_image']), $request->file('image'), $request->boolean('remove_image'));

        return to_route('admin.categories.edit', $category)->with('status', 'Kategori oluşturuldu.');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.form', compact('category'));
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        CatalogImage::save($category, $request->safe()->except(['image', 'remove_image']), $request->file('image'), $request->boolean('remove_image'));

        return to_route('admin.categories.edit', $category)->with('status', 'Kategori güncellendi.');
    }

    public function confirmDelete(Category $category): View
    {
        return view('admin.confirm-delete', [
            'entry' => $category,
            'type' => 'Kategori',
            'blocked' => $category->products()->exists(),
            'destroyRoute' => route('admin.categories.destroy', $category),
            'cancelRoute' => route('admin.categories.index'),
        ]);
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return to_route('admin.categories.index')->with('error', 'Bu kategoriye bağlı ürünler var. Önce ürünleri başka bir kategoriye taşıyın.');
        }

        $image = $category->image;

        try {
            $category->delete();
        } catch (QueryException $exception) {
            if ($exception->getCode() !== '23000') {
                throw $exception;
            }

            return to_route('admin.categories.index')->with('error', 'Bu kategoriye bağlı ürünler var. Önce ürünleri başka bir kategoriye taşıyın.');
        }

        CatalogImage::deleteIfUnused($image);

        return to_route('admin.categories.index')->with('status', 'Kategori silindi.');
    }
}
