<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

abstract class CatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name')) && blank($this->input('slug'))) {
            $this->merge(['slug' => Str::slug($this->input('name'), '-', 'tr')]);
        }
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'Ad', 'slug' => 'Slug', 'description' => 'Açıklama',
            'short_description' => 'Kısa açıklama', 'image' => 'Görsel',
            'is_active' => 'Aktiflik', 'is_featured' => 'Öne çıkan ürün',
            'sort_order' => 'Sıralama', 'category_id' => 'Kategori', 'sku' => 'SKU',
            'remove_image' => 'Görseli kaldır', 'q' => 'Arama', 'status' => 'Durum',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => ':attribute alanı zorunludur.', 'string' => ':attribute metin olmalıdır.',
            'max' => ':attribute en fazla :max karakter olabilir.',
            'image.max' => 'Görsel en fazla 4 MB olabilir.',
            'unique' => ':attribute zaten kullanılıyor.', 'exists' => 'Geçerli bir kategori seçin.',
            'integer' => ':attribute tam sayı olmalıdır.',
            'between' => ':attribute :min ile :max arasında olmalıdır.',
            'boolean' => ':attribute için geçerli bir seçim yapın.',
            'regex' => 'Slug yalnızca küçük harf, rakam ve aralarda kısa çizgi içerebilir.',
            'image' => 'Geçerli bir görsel dosyası yükleyin.',
            'mimes' => 'Yalnızca JPG, PNG veya WebP görselleri yükleyebilirsiniz.',
            'extensions' => 'Dosya uzantısı JPG, JPEG, PNG veya WebP olmalıdır.',
            'in' => 'Geçerli bir durum filtresi seçin.',
            'uploaded' => 'Görsel yüklenemedi. Dosya boyutunu kontrol edip tekrar deneyin.',
        ];
    }
}
