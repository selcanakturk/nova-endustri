<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $categorySlug = $request->query('category');
        $selectedCategory = null;

        if ($categorySlug !== null && $categorySlug !== '') {
            abort_unless(is_string($categorySlug), 404);
            $selectedCategory = Category::active()->where('slug', $categorySlug)->firstOrFail();
        }

        $query = Product::visible()->with('category')->ordered();

        if ($selectedCategory !== null) {
            $query->whereBelongsTo($selectedCategory);
        }

        $products = $query->paginate(12)->appends(['category' => $selectedCategory?->slug]);

        return view('urunler', [
            'products' => $products,
            'canonical' => route('products', array_filter(['category' => $selectedCategory?->slug, 'page' => $products->currentPage() > 1 ? $products->currentPage() : null])),
            'categories' => Category::active()->ordered()->get(),
            'selectedCategory' => $selectedCategory,
        ]);
    }

    public function show(string $slug): View
    {
        return view('products.show', [
            'product' => Product::visible()->with('category')->where('slug', $slug)->firstOrFail(),
        ]);
    }
}
