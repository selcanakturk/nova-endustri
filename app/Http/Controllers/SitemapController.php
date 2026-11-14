<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Product;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        return response()->view('sitemap', [
            'pages' => ['home', 'about', 'products', 'services', 'contact', 'privacy', 'blog.index', 'news.index'],
            'products' => Product::visible()->select(['id', 'slug', 'updated_at'])->lazyById(500),
            'posts' => Post::visible()->select(['id', 'slug', 'type', 'updated_at'])->lazyById(500),
        ])->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
