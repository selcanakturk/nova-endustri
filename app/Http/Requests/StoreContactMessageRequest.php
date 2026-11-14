<?php

namespace App\Http\Requests;

class StoreContactMessageRequest extends PublicInquiryRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [...$this->commonRules(), 'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+() .-]{6,40}$/'], 'subject' => ['required', 'string', 'max:255'], 'message' => ['required', 'string', 'max:10000']];
    }
}
