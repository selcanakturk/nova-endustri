<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('admin creation command stores a hashed password without displaying it', function () {
    $secret = 'Strong-Test-Password42!';

    $this->artisan('admin:create', ['--name' => 'Site Manager', '--email' => 'MANAGER@example.test'])
        ->expectsQuestion('Parola', $secret)->expectsQuestion('Parola tekrar', $secret)
        ->expectsOutputToContain('Yönetici hesabı oluşturuldu.')
        ->doesntExpectOutput($secret)->assertSuccessful();

    $user = User::where('email', 'manager@example.test')->firstOrFail();
    expect($user->is_admin)->toBeTrue();
    expect(Hash::check($secret, $user->password))->toBeTrue();
    expect($user->password)->not->toBe($secret);
});

test('admin creation never promotes or changes an existing account', function () {
    $user = User::factory()->create(['email' => 'existing@example.test']);
    $oldHash = $user->password;

    $this->artisan('admin:create', ['--name' => 'New name', '--email' => $user->email])
        ->expectsOutputToContain('Bu e-posta zaten kullanılıyor.')->assertFailed();

    expect($user->fresh())->is_admin->toBeFalse()->password->toBe($oldHash);
    $this->assertDatabaseCount('users', 1);
});

test('weak or mismatched admin passwords never create accounts', function (string $password, string $confirmation) {
    $this->artisan('admin:create', ['--name' => 'Manager', '--email' => 'manager@example.test'])
        ->expectsQuestion('Parola', $password)->expectsQuestion('Parola tekrar', $confirmation)
        ->expectsOutputToContain('Hesap oluşturulmadı.')->assertFailed();

    $this->assertDatabaseCount('users', 0);
})->with([
    ['weak', 'weak'], ['Strong-Test-Password42!', 'Different-Password42!'],
    [str_repeat('İ', 40).'Ab1!', str_repeat('İ', 40).'Ab1!'],
]);

test('non interactive account creation fails without asking for or exposing a password', function () {
    $this->artisan('admin:create', ['--name' => 'Manager', '--email' => 'manager@example.test', '--no-interaction' => true])
        ->expectsOutputToContain('etkileşimli bir terminalde çalıştırın.')->assertFailed();

    $this->assertDatabaseCount('users', 0);
});
