<?php

namespace App\Http\Controllers\Admin;

use App\CmsContent;
use App\CmsImage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CmsRequest;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SiteSettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.cms.form', ['title' => 'Site Ayarları', 'fields' => config('cms.settings'), 'values' => array_replace(CmsContent::settingDefaults(), SiteSetting::where('singleton_key', 'site')->first()?->data ?? []), 'action' => route('admin.settings.update')]);
    }

    public function update(CmsRequest $request): RedirectResponse
    {
        $entry = SiteSetting::firstOrNew(['singleton_key' => 'site']);
        if (! $entry->exists) {
            $entry->data = CmsContent::settingDefaults();
        }
        CmsImage::save($entry, $request, config('cms.settings'));

        return to_route('admin.settings.edit')->with('status', 'Site ayarları güncellendi.');
    }
}
