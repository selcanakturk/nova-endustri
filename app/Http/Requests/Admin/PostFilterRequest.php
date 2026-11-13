<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class PostFilterRequest extends CatalogRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'], 'post_category_id' => ['nullable', 'integer', 'exists:post_categories,id'],
            'status' => ['nullable', Rule::in(['draft', 'published'])], 'type' => ['nullable', Rule::in(['article', 'news'])],
        ];
    }
}
