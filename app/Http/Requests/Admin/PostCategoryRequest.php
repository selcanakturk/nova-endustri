<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class PostCategoryRequest extends CatalogRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('post_categories', 'slug')->ignore($this->route('post_category'))],
            'description' => ['nullable', 'string', 'max:10000'],
            'is_active' => ['required', 'boolean'], 'sort_order' => ['required', 'integer', 'between:0,2147483647'],
        ];
    }
}
