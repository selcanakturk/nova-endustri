<?php

namespace App\Http\Requests\Admin;

use App\Rules\SafeLink;

class CmsRequest extends CatalogRequest
{
    public function rules(): array
    {
        $isSettings = $this->routeIs('admin.settings.update');
        $page = $this->route('page');
        $fields = $isSettings ? config('cms.settings') : config('cms.pages.'.$page?->key.'.fields', []);
        $rules = [];
        foreach ($fields as $key => $field) {
            $rules[$key] = match ($field['type']) {
                'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:4096', 'dimensions:max_width=6000,max_height=6000'],
                'url' => ['nullable', 'string', 'max:2048', new SafeLink],
                'link' => ['required', 'string', 'max:2048', new SafeLink(true)],
                'email' => ['nullable', 'string', 'email', 'max:255'],
                default => [$isSettings && $key !== 'company_name' ? 'nullable' : 'required', 'string', 'max:'.($field['type'] === 'textarea' ? 10000 : 255)],
            };
            if ($field['type'] === 'image') {
                $rules['remove_'.$key] = ['sometimes', 'boolean'];
            }
        }
        if (! $isSettings) {
            foreach (['meta_title', 'meta_description', 'og_title', 'og_description'] as $key) {
                $rules[$key] = ['nullable', 'string', 'max:'.(str_contains($key, 'description') ? 1000 : 255)];
            }
            $rules['canonical_url'] = ['nullable', 'string', 'max:2048', new SafeLink];
            $rules['og_image'] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:4096', 'dimensions:max_width=6000,max_height=6000'];
            $rules['remove_og_image'] = ['sometimes', 'boolean'];
        }

        return $rules;
    }

    public function attributes(): array
    {
        $page = $this->route('page');
        $fields = $this->routeIs('admin.settings.update') ? config('cms.settings') : config('cms.pages.'.$page?->key.'.fields', []);

        return array_merge(parent::attributes(), collect($fields)->mapWithKeys(fn (array $field, string $key) => [$key => $field['label']])->all(), ['canonical_url' => 'Canonical URL', 'meta_title' => 'SEO başlığı', 'meta_description' => 'SEO açıklaması', 'og_title' => 'Paylaşım başlığı', 'og_description' => 'Paylaşım açıklaması', 'og_image' => 'Paylaşım görseli']);
    }

    public function messages(): array
    {
        $messages = parent::messages();
        foreach (['logo', 'favicon', 'social_image', 'hero_image', 'intro_image', 'og_image'] as $key) {
            $messages[$key.'.max'] = ':attribute en fazla 4 MB olabilir.';
            $messages[$key.'.dimensions'] = ':attribute en fazla 6000 × 6000 piksel olabilir.';
        }
        $messages['email'] = 'Geçerli bir e-posta adresi girin.';

        return $messages;
    }
}
