<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Product;
use App\Models\QuoteRequest;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $productCounts = Product::toBase()->selectRaw('COUNT(*) AS total, COUNT(CASE WHEN is_active = ? THEN 1 END) AS active', [true])->first();
        $postCounts = Post::toBase()->selectRaw('COUNT(*) AS total, COUNT(CASE WHEN status = ? THEN 1 END) AS draft', ['draft'])->first();
        $totalProducts = (int) $productCounts->total;
        $activeProducts = (int) $productCounts->active;

        return view('admin.dashboard', [
            'stats' => [
                'total_products' => $totalProducts,
                'active_products' => $activeProducts,
                'inactive_products' => $totalProducts - $activeProducts,
                'total_categories' => Category::count(),
            ],
            'contentStats' => ['total' => (int) $postCounts->total, 'published' => Post::visible()->count(), 'draft' => (int) $postCounts->draft, 'categories' => PostCategory::count()],
            'inquiryStats' => ['messages' => ContactMessage::newlyReceived()->count(), 'quotes' => QuoteRequest::newlyReceived()->count()],
            'recentMessages' => ContactMessage::ordered()->limit(4)->get(),
            'recentQuotes' => QuoteRequest::ordered()->limit(4)->get(),
            'recentProducts' => Product::with('category')->latest()->orderByDesc('id')->limit(5)->get(),
        ]);
    }
}
