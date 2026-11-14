<?php

namespace App\Http\Requests\Admin;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PostRequest extends CatalogRequest
{
    protected function prepareForValidation(): void
    {
        if (blank($this->input('slug')) && is_string($this->input('title'))) {
            $this->merge(['slug' => $this->route('post')?->slug ?? Str::slug($this->input('title'), '-', 'tr')]);
        }
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('posts', 'slug')->ignore($this->route('post'))],
            'post_category_id' => ['required', 'integer', 'exists:post_categories,id'],
            'excerpt' => ['required', 'string', 'max:1000'], 'content' => ['required', 'string', 'max:100000'],
            'type' => ['required', Rule::in(['article', 'news'])], 'status' => ['required', Rule::in(['draft', 'published'])],
            'published_at' => ['nullable', 'required_if:status,published', 'date_format:Y-m-d\TH:i'],
            'is_featured' => ['required', 'boolean'], 'sort_order' => ['required', 'integer', 'between:0,2147483647'],
            'meta_title' => ['nullable', 'string', 'max:70'], 'meta_description' => ['nullable', 'string', 'max:160'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:4096', 'dimensions:max_width=6000,max_height=6000'],
            'remove_image' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [...parent::attributes(), 'title' => 'Başlık', 'post_category_id' => 'Blog kategorisi', 'excerpt' => 'Kısa açıklama', 'content' => 'İçerik', 'type' => 'İçerik türü', 'status' => 'Yayın durumu', 'published_at' => 'Yayın tarihi', 'cover_image' => 'Kapak görseli', 'meta_title' => 'SEO başlığı', 'meta_description' => 'SEO açıklaması'];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [...parent::messages(), 'in' => ':attribute için geçerli bir seçim yapın.', 'cover_image.max' => 'Kapak görseli en fazla 4 MB olabilir.', 'required_if' => 'Yayınlamak için yayın tarihi belirtin.', 'date_format' => 'Geçerli bir yayın tarihi ve saati girin.'];
    }
}
