<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class CategoryRequest extends CatalogRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('categories', 'slug')->ignore($this->route('category'))],
            'description' => ['nullable', 'string', 'max:10000'],
            'sort_order' => ['required', 'integer', 'between:0,2147483647'],
            'is_active' => ['required', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:4096', 'dimensions:max_width=6000,max_height=6000'],
            'remove_image' => ['sometimes', 'boolean'],
        ];
    }
}
