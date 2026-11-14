<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Product;
use App\Models\Service;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('pages.home', [
            'products' => Product::visible()->featured()->with('category')->ordered()->limit(3)->get(),
            'recentPosts' => Post::visible()->with(['category', 'author'])->ordered()->limit(3)->get(),
            'services' => Service::active()->ordered()->get(),
        ]);
    }
}
