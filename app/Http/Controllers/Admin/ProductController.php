<?php

namespace App\Http\Controllers\Admin;

use App\CatalogImage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductFilterRequest;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(ProductFilterRequest $request): View
    {
        $filters = $request->validated();
        $query = Product::with('category')->ordered();

        if (filled($filters['q'] ?? null)) {
            $search = '%'.$filters['q'].'%';
            $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', $search)->orWhere('sku', 'like', $search)->orWhere('slug', 'like', $search);
            });
        }

        if (filled($filters['category_id'] ?? null)) {
            $query->where('category_id', $filters['category_id']);
        }

        if (filled($filters['status'] ?? null)) {
            $query->where('is_active', $filters['status'] === 'active');
        }

        return view('admin.products.index', [
            'products' => $query->paginate(15)->appends($filters),
            'categories' => Category::ordered()->get(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('admin.products.form', [
            'product' => new Product(['is_active' => true, 'is_featured' => false, 'sort_order' => 0]),
            'categories' => Category::ordered()->get(),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = new Product;
        CatalogImage::save($product, $request->safe()->except(['image', 'remove_image']), $request->file('image'), $request->boolean('remove_image'));

        return to_route('admin.products.edit', $product)->with('status', 'Ürün oluşturuldu.');
    }

    public function edit(Product $product): View
    {
        return view('admin.products.form', ['product' => $product, 'categories' => Category::ordered()->get()]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        CatalogImage::save($product, $request->safe()->except(['image', 'remove_image']), $request->file('image'), $request->boolean('remove_image'));

        return to_route('admin.products.edit', $product)->with('status', 'Ürün güncellendi.');
    }

    public function confirmDelete(Product $product): View
    {
        return view('admin.confirm-delete', [
            'entry' => $product, 'type' => 'Ürün', 'blocked' => false,
            'destroyRoute' => route('admin.products.destroy', $product), 'cancelRoute' => route('admin.products.index'),
        ]);
    }

    public function destroy(Product $product): RedirectResponse
    {
        $image = $product->image;
        $product->delete();
        CatalogImage::deleteIfUnused($image);

        return to_route('admin.products.index')->with('status', 'Ürün silindi.');
    }
}
