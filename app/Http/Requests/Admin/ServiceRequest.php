<?php

namespace App\Http\Requests\Admin;

use App\Models\Service;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ServiceRequest extends CatalogRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('title')) && blank($this->input('slug'))) {
            $this->merge(['slug' => Str::slug($this->input('title'), '-', 'tr')]);
        }
    }

    public function rules(): array
    {
        return ['title' => ['required', 'string', 'max:255'], 'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('services', 'slug')->ignore($this->route('service'))], 'short_description' => ['nullable', 'string', 'max:1000'], 'description' => ['nullable', 'string', 'max:10000'], 'icon' => ['required', Rule::in(array_keys(Service::ICONS))], 'sort_order' => ['required', 'integer', 'between:0,2147483647'], 'is_active' => ['required', 'boolean'], 'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:4096', 'dimensions:max_width=6000,max_height=6000'], 'remove_image' => ['sometimes', 'boolean']];
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), ['title' => 'Hizmet başlığı', 'icon' => 'Simge']);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), ['image.dimensions' => 'Görsel en fazla 6000 × 6000 piksel olabilir.', 'icon.in' => 'Geçerli bir simge seçin.']);
    }
}
