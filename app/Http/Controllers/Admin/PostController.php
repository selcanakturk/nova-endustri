<?php

namespace App\Http\Controllers\Admin;

use App\CatalogImage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PostFilterRequest;
use App\Http\Requests\Admin\PostRequest;
use App\Models\Post;
use App\Models\PostCategory;
use App\PostMarkdown;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(PostFilterRequest $request): View
    {
        $filters = $request->validated();
        $query = Post::with(['category', 'author'])->orderBy('sort_order')->latest('id');
        foreach (['post_category_id', 'status', 'type'] as $field) {
            if (filled($filters[$field] ?? null)) {
                $query->where($field, $filters[$field]);
            }
        }
        if (filled($filters['q'] ?? null)) {
            $query->where('title', 'like', '%'.$filters['q'].'%');
        }

        return view('admin.posts.index', ['posts' => $query->paginate(15)->appends($filters), 'categories' => PostCategory::ordered()->get(), 'filters' => $filters]);
    }

    public function create(): View
    {
        return view('admin.posts.form', ['post' => new Post(['type' => 'article', 'status' => 'draft', 'is_featured' => false, 'sort_order' => 0]), 'categories' => PostCategory::ordered()->get()]);
    }

    public function store(PostRequest $request): RedirectResponse
    {
        $post = new Post;
        $post->author()->associate($request->user());
        CatalogImage::save($post, $this->attributes($request), $request->file('cover_image'), $request->boolean('remove_image'));

        return to_route('admin.posts.edit', $post)->with('status', 'İçerik oluşturuldu.');
    }

    public function edit(Post $post): View
    {
        return view('admin.posts.form', ['post' => $post, 'categories' => PostCategory::ordered()->get()]);
    }

    public function update(PostRequest $request, Post $post): RedirectResponse
    {
        CatalogImage::save($post, $this->attributes($request), $request->file('cover_image'), $request->boolean('remove_image'));

        return to_route('admin.posts.edit', $post)->with('status', 'İçerik güncellendi.');
    }

    public function confirmDelete(Post $post): View
    {
        return view('admin.confirm-delete', ['entry' => $post, 'type' => 'İçerik', 'blocked' => false, 'entryName' => $post->title, 'destroyRoute' => route('admin.posts.destroy', $post), 'cancelRoute' => route('admin.posts.index')]);
    }

    public function destroy(Post $post): RedirectResponse
    {
        $image = $post->cover_image;
        $post->delete();
        CatalogImage::deleteIfUnused($image);

        return to_route('admin.posts.index')->with('status', 'İçerik silindi.');
    }

    public function preview(Request $request): JsonResponse
    {
        $data = $request->validate(['content' => ['required', 'string', 'max:100000']], ['required' => 'Önizleme için içerik girin.', 'string' => 'İçerik metin olmalıdır.', 'max' => 'İçerik en fazla 100000 karakter olabilir.']);

        return response()->json(['html' => PostMarkdown::render($data['content'])]);
    }

    /** @return array<string, mixed> */
    private function attributes(PostRequest $request): array
    {
        $attributes = $request->safe()->except(['cover_image', 'remove_image']);
        if (filled($attributes['published_at'] ?? null)) {
            $attributes['published_at'] = CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $attributes['published_at'], config('nova.editorial_timezone'))->setTimezone(config('app.timezone'));
        }

        return $attributes;
    }
}
