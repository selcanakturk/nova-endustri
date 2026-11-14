<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafeLink implements ValidationRule
{
    public function __construct(public bool $allowRelative = false) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match('/[\s\\\\\x00-\x1f\x7f]/u', $value)) {
            $fail('Geçerli ve güvenli bir bağlantı girin.');

            return;
        }
        if ($this->allowRelative && preg_match('~\A/(?!/)[a-zA-Z0-9/_.?=&%#\-]*\z~', $value)) {
            return;
        }
        $parts = parse_url($value);
        if (! filter_var($value, FILTER_VALIDATE_URL) || ! in_array(strtolower($parts['scheme'] ?? ''), ['https', 'http'], true) || isset($parts['user']) || isset($parts['pass'])) {
            $fail('HTTP veya HTTPS ile başlayan güvenli bir bağlantı girin.');
        }
    }
}
