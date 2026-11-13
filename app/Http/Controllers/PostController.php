<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(Request $request): View
    {
        $isNews = $request->routeIs('news.*');
        $categorySlug = $request->query('category');
        $selectedCategory = null;
        if ($categorySlug !== null && $categorySlug !== '') {
            abort_unless(is_string($categorySlug), 404);
            $selectedCategory = PostCategory::active()->where('slug', $categorySlug)->firstOrFail();
        }
        $query = Post::visible()->where('type', $isNews ? 'news' : 'article')->with(['category', 'author']);
        if ($selectedCategory !== null) {
            $query->where('post_category_id', $selectedCategory->id);
        }
        $featured = (clone $query)->where('is_featured', true)->ordered()->first();
        $posts = $query->when($featured, fn ($query) => $query->whereKeyNot($featured->id))->ordered()->paginate(9)->appends(['category' => $selectedCategory?->slug]);
        $listingRoute = $isNews ? 'news.index' : 'blog.index';

        return view('posts.index', [
            'posts' => $posts, 'featured' => $featured, 'categories' => PostCategory::active()->ordered()->get(),
            'selectedCategory' => $selectedCategory, 'isNews' => $isNews, 'listingRoute' => $listingRoute,
            'canonical' => route($listingRoute, array_filter(['category' => $selectedCategory?->slug, 'page' => $posts->currentPage() > 1 ? $posts->currentPage() : null])),
        ]);
    }

    public function show(Request $request, string $slug): View
    {
        $isNews = $request->routeIs('news.*');
        $post = Post::visible()->where('type', $isNews ? 'news' : 'article')->with(['category', 'author'])->where('slug', $slug)->firstOrFail();

        return view('posts.show', [
            'post' => $post, 'isNews' => $isNews,
            'related' => Post::visible()->where('type', $post->type)->where('post_category_id', $post->post_category_id)->whereKeyNot($post->id)->with(['category', 'author'])->ordered()->limit(3)->get(),
        ]);
    }
}
