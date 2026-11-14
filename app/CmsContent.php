<?php

namespace App;

use App\Models\Page;
use App\Models\SiteSetting;

class CmsContent
{
    /** @return array<string, mixed> */
    public static function settingDefaults(): array
    {
        return collect(config('cms.settings'))->map(fn (array $field) => $field['default'])->all();
    }

    /** @return array<string, mixed> */
    public function viewData(?string $key): array
    {
        $settings = array_replace(self::settingDefaults(), SiteSetting::where('singleton_key', 'site')->first()?->data ?? []);
        foreach (['company_name', 'default_meta_title', 'default_meta_description'] as $field) {
            if (blank($settings[$field])) {
                $settings[$field] = self::settingDefaults()[$field];
            }
        }
        $definition = config('cms.pages.'.$key);
        $page = $definition ? Page::where('key', $key)->first() : null;
        $content = $definition ? array_replace(collect($definition['fields'])->map(fn (array $field) => $field['default'])->all(), $page?->content ?? []) : [];
        $seo = array_replace(['meta_title' => $definition['meta_title'] ?? $settings['default_meta_title'], 'meta_description' => $definition['meta_description'] ?? $settings['default_meta_description'], 'canonical_url' => null, 'og_title' => null, 'og_description' => null, 'og_image' => null], $page?->seo ?? []);
        $seo['og_image_url'] = filled($seo['og_image'] ?? $settings['social_image']) ? CatalogImage::url($seo['og_image'] ?? $settings['social_image']) : null;

        return ['siteSettings' => $settings, 'cms' => $content, 'pageSeo' => $seo];
    }
}
