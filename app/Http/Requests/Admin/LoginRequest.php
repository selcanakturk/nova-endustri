<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array{email: list<string>, password: list<string>} */
    public function rules(): array
    {
        return ['email' => ['required', 'string', 'email', 'max:255'], 'password' => ['required', 'string', 'max:1024']];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => Str::lower($this->input('email'))]);
        }
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'email.required' => 'E-posta adresinizi girin.',
            'email.email' => 'Geçerli bir e-posta adresi girin.',
            'email.string' => 'Geçerli bir e-posta adresi girin.',
            'email.max' => 'E-posta en fazla 255 karakter olabilir.',
            'password.required' => 'Parolanızı girin.',
            'password.string' => 'Geçerli bir parola girin.',
            'password.max' => 'Parola en fazla 1024 karakter olabilir.',
        ];
    }

    public function authenticate(): void
    {
        $identityKey = 'admin-login:'.hash('sha256', $this->string('email').'|'.$this->ip());
        $ipKey = 'admin-login-ip:'.hash('sha256', (string) $this->ip());

        foreach ([$identityKey => 5, $ipKey => 30] as $key => $limit) {
            if (RateLimiter::tooManyAttempts($key, $limit)) {
                throw ValidationException::withMessages([
                    'email' => 'Çok fazla giriş denemesi. '.RateLimiter::availableIn($key).' saniye sonra tekrar deneyin.',
                ]);
            }
        }

        if (! Auth::guard('web')->attempt([...$this->only('email', 'password'), 'is_admin' => true])) {
            RateLimiter::hit($identityKey, 60);
            RateLimiter::hit($ipKey, 60);

            throw ValidationException::withMessages(['email' => 'E-posta veya parola hatalı.']);
        }

        RateLimiter::clear($identityKey);
    }
}
