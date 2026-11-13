<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Product;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $totalProducts = Product::count();
        $activeProducts = Product::active()->count();

        return view('admin.dashboard', [
            'stats' => [
                'total_products' => $totalProducts,
                'active_products' => $activeProducts,
                'inactive_products' => $totalProducts - $activeProducts,
                'total_categories' => Category::count(),
            ],
            'contentStats' => ['total' => Post::count(), 'published' => Post::visible()->count(), 'draft' => Post::where('status', 'draft')->count(), 'categories' => PostCategory::count()],
            'recentProducts' => Product::with('category')->latest()->orderByDesc('id')->limit(5)->get(),
        ]);
    }
}
