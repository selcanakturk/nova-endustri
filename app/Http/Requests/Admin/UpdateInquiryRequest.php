<?php

namespace App\Http\Requests\Admin;

use App\Models\ContactMessage;
use App\Models\QuoteRequest;
use Illuminate\Validation\Rule;

class UpdateInquiryRequest extends CatalogRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $statuses = array_keys($this->routeIs('admin.messages.*') ? ContactMessage::STATUSES : QuoteRequest::STATUSES);

        return ['status' => ['required', Rule::in($statuses)], 'admin_note' => ['nullable', 'string', 'max:10000']];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [...parent::attributes(), 'status' => 'Durum', 'admin_note' => 'Yönetici notu', 'sort' => 'Sıralama'];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [...parent::messages(), 'in' => ':attribute için geçerli bir seçim yapın.'];
    }
}
