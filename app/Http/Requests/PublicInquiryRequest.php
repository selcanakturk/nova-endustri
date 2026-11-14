<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class PublicInquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    protected function commonRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'], 'email' => ['required', 'string', 'email', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'], 'website' => ['prohibited'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['name' => 'Ad soyad', 'email' => 'E-posta', 'phone' => 'Telefon', 'company' => 'Firma', 'subject' => 'Konu', 'message' => 'Mesaj', 'quantity' => 'Miktar', 'unit' => 'Birim'];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => ':attribute alanı zorunludur.', 'string' => ':attribute metin olmalıdır.',
            'email' => 'Geçerli bir e-posta adresi girin.', 'max' => ':attribute en fazla :max karakter olabilir.',
            'phone.regex' => 'Geçerli bir telefon numarası girin.', 'quantity.numeric' => 'Miktar sayı olmalıdır.',
            'quantity.gt' => 'Miktar sıfırdan büyük olmalıdır.', 'quantity.max' => 'Miktar en fazla 999999999.999 olabilir.',
            'quantity.decimal' => 'Miktar en fazla üç ondalık basamak içerebilir.',
            'website.prohibited' => 'Gönderim doğrulanamadı. Lütfen formu yeniden doldurun.',
        ];
    }
}
