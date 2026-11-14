<?php

namespace App\Http\Requests;

class StoreQuoteRequest extends PublicInquiryRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [...$this->commonRules(), 'phone' => ['required', 'string', 'max:40', 'regex:/^(?=(?:\D*\d){6})[0-9+() .-]{6,40}$/'],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:999999999.999', 'decimal:0,3'],
            'unit' => ['nullable', 'string', 'max:30'], 'message' => ['nullable', 'string', 'max:10000']];
    }
}
