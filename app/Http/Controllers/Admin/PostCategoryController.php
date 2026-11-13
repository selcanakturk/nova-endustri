<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PostCategoryRequest;
use App\Models\PostCategory;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PostCategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.post-categories.index', ['categories' => PostCategory::withCount('posts')->ordered()->paginate(15)]);
    }

    public function create(): View
    {
        return view('admin.post-categories.form', ['category' => new PostCategory(['is_active' => true, 'sort_order' => 0])]);
    }

    public function store(PostCategoryRequest $request): RedirectResponse
    {
        $postCategory = new PostCategory;
        $postCategory->fill($request->validated())->save();

        return to_route('admin.post-categories.edit', $postCategory)->with('status', 'Kategori oluşturuldu.');
    }

    public function edit(PostCategory $postCategory): View
    {
        return view('admin.post-categories.form', ['category' => $postCategory]);
    }

    public function update(PostCategoryRequest $request, PostCategory $postCategory): RedirectResponse
    {
        $postCategory->fill($request->validated())->save();

        return to_route('admin.post-categories.edit', $postCategory)->with('status', 'Kategori güncellendi.');
    }

    public function confirmDelete(PostCategory $postCategory): View
    {
        return view('admin.confirm-delete', [
            'entry' => $postCategory,
            'type' => 'Blog kategorisi',
            'blocked' => $postCategory->posts()->exists(),
            'destroyRoute' => route('admin.post-categories.destroy', $postCategory),
            'cancelRoute' => route('admin.post-categories.index'),
        ]);
    }

    public function destroy(PostCategory $postCategory): RedirectResponse
    {
        if ($postCategory->posts()->exists()) {
            return to_route('admin.post-categories.index')->with('error', 'Bu kategoriye bağlı içerikler var. Önce içerikleri başka bir kategoriye taşıyın.');
        }

        try {
            $postCategory->delete();
        } catch (QueryException $exception) {
            if ($exception->getCode() !== '23000') {
                throw $exception;
            }

            return to_route('admin.post-categories.index')->with('error', 'Bu kategoriye bağlı içerikler var. Önce içerikleri başka bir kategoriye taşıyın.');
        }

        return to_route('admin.post-categories.index')->with('status', 'Kategori silindi.');
    }
}
