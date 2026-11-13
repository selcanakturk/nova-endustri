<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'admin:create {--name= : Yönetici adı} {--email= : Yönetici e-posta adresi}';

    protected $description = 'Gizli parola girişiyle yeni bir yönetici hesabı oluşturur';

    public function handle(): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('Güvenli parola girişi için komutu etkileşimli bir terminalde çalıştırın.');

            return self::FAILURE;
        }

        $name = $this->option('name') ?? $this->ask('Ad soyad');
        $email = Str::lower(trim((string) ($this->option('email') ?? $this->ask('E-posta'))));
        $identity = Validator::make(compact('name', 'email'), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ], [
            'required' => 'Ad ve e-posta zorunludur.', 'email' => 'Geçerli bir e-posta girin.',
            'unique' => 'Bu e-posta zaten kullanılıyor. Mevcut hesap değiştirilmedi.',
            'max' => 'Ad ve e-posta en fazla 255 karakter olabilir.',
        ]);

        if ($identity->fails()) {
            $this->error($identity->errors()->first());

            return self::FAILURE;
        }

        $this->info('Parola en az 12 karakter; büyük/küçük harf, rakam ve sembol içermelidir. En fazla 72 bayt olabilir.');
        $password = $this->secret('Parola', false);
        $confirmation = $this->secret('Parola tekrar', false);
        $validator = Validator::make(['password' => $password, 'password_confirmation' => $confirmation], [
            'password' => ['required', 'string', 'max:255', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);

        if ($validator->fails() || strlen((string) $password) > 72) {
            $this->error('Parola gereksinimleri karşılanmadı veya parolalar eşleşmiyor. Hesap oluşturulmadı.');

            return self::FAILURE;
        }

        $user = new User(['name' => $name, 'email' => $email, 'password' => $password]);
        $user->is_admin = true;
        $user->save();

        $this->info('Yönetici hesabı oluşturuldu. /admin/login üzerinden giriş yapabilirsiniz.');

        return self::SUCCESS;
    }
}
