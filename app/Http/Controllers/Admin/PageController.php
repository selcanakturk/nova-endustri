<?php

namespace App\Http\Controllers\Admin;

use App\CmsImage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CmsRequest;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PageController extends Controller
{
    public function index(): View
    {
        return view('admin.cms.index', ['pages' => Page::orderBy('id')->get()]);
    }

    public static function fields(Page $page): array
    {
        $fields = config('cms.pages.'.$page->key.'.fields');
        abort_unless(is_array($fields), 404);
        foreach (['meta_title' => 'SEO başlığı', 'meta_description' => 'SEO açıklaması', 'canonical_url' => 'Canonical URL', 'og_title' => 'Paylaşım başlığı', 'og_description' => 'Paylaşım açıklaması', 'og_image' => 'Paylaşım görseli'] as $key => $label) {
            $fields[$key] = ['label' => $label, 'group' => 'SEO ve sosyal paylaşım', 'type' => $key === 'og_image' ? 'image' : ($key === 'canonical_url' ? 'url' : (str_contains($key, 'description') ? 'textarea' : 'text'))];
        }

        return $fields;
    }

    public function edit(Page $page): View
    {
        return view('admin.cms.form', ['title' => $page->title.' · Sayfa düzenleme', 'fields' => self::fields($page), 'values' => array_replace(collect(config('cms.pages.'.$page->key.'.fields'))->map(fn (array $field) => $field['default'])->all(), $page->content ?? [], $page->seo ?? []), 'action' => route('admin.pages.update', $page), 'page' => $page]);
    }

    public function update(CmsRequest $request, Page $page): RedirectResponse
    {
        CmsImage::save($page, $request, self::fields($page));

        return to_route('admin.pages.edit', $page)->with('status', 'Sayfa güncellendi.');
    }
}
