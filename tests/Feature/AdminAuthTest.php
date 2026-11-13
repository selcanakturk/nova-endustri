<?php

use App\Models\Product;
use App\Models\User;

dataset('protected admin routes', [
    ['GET', '/admin'], ['GET', '/admin/products'], ['GET', '/admin/products/create'],
    ['GET', '/admin/products/%d/edit'], ['GET', '/admin/products/%d/delete'],
    ['POST', '/admin/products'], ['PUT', '/admin/products/%d'], ['DELETE', '/admin/products/%d'],
    ['GET', '/admin/categories'], ['GET', '/admin/categories/create'], ['GET', '/admin/categories/%d/edit'],
    ['GET', '/admin/categories/%d/delete'], ['POST', '/admin/categories'], ['PUT', '/admin/categories/%d'], ['DELETE', '/admin/categories/%d'],
]);

test('guests are redirected away from every protected admin endpoint', function (string $method, string $path) {
    $product = Product::factory()->create();
    $id = str_contains($path, 'categories') ? $product->category_id : $product->id;

    $this->call($method, sprintf($path, $id))->assertRedirect(route('admin.login'));
    $this->assertDatabaseCount('products', 1);
    $this->assertDatabaseCount('categories', 1);
})->with('protected admin routes');

test('non administrators receive 403 on every protected admin endpoint', function (string $method, string $path) {
    $user = User::factory()->create();
    $product = Product::factory()->create();
    $id = str_contains($path, 'categories') ? $product->category_id : $product->id;

    $this->actingAs($user)->call($method, sprintf($path, $id))->assertForbidden();
    $this->assertDatabaseCount('products', 1);
    $this->assertDatabaseCount('categories', 1);
})->with('protected admin routes');

test('login page is public and includes a CSRF token', function () {
    $this->get('/admin/login')->assertOk()->assertSeeText('Tekrar hoş geldiniz.')
        ->assertSee('name="_token"', false)->assertSee('type="password"', false);
});

test('administrators can log in and regenerate the session while keeping the intended URL', function () {
    $admin = User::factory()->admin()->create(['email' => 'admin@example.test']);
    $this->withSession(['url.intended' => route('admin.products.index'), 'marker' => 'retained']);
    $oldSessionId = session()->getId();

    $this->post('/admin/login', ['email' => 'ADMIN@example.test', 'password' => 'password'])
        ->assertRedirect(route('admin.products.index'))->assertSessionHas('marker', 'retained');

    $this->assertAuthenticatedAs($admin);
    expect(session()->getId())->not->toBe($oldSessionId);
});

test('wrong credentials never authenticate or flash the password', function (string $email, string $password) {
    User::factory()->admin()->create(['email' => 'admin@example.test']);

    $this->from('/admin/login')->post('/admin/login', compact('email', 'password'))
        ->assertRedirect('/admin/login')->assertSessionHasErrors(['email' => 'E-posta veya parola hatalı.'])
        ->assertSessionMissing('_old_input.password');
    $this->assertGuest();
})->with([
    ['admin@example.test', 'wrong-password'], ['unknown@example.test', 'password'],
]);

test('non administrators cannot authenticate through the admin login form', function () {
    User::factory()->create(['email' => 'member@example.test']);

    $this->post('/admin/login', ['email' => 'member@example.test', 'password' => 'password'])
        ->assertSessionHasErrors(['email' => 'E-posta veya parola hatalı.']);
    $this->assertGuest();
});

test('login validates required credentials in Turkish', function () {
    $this->post('/admin/login')->assertSessionHasErrors([
        'email' => 'E-posta adresinizi girin.', 'password' => 'Parolanızı girin.',
    ]);
});

test('login throttles repeated failures and allows login after the cooldown', function () {
    $admin = User::factory()->admin()->create(['email' => 'admin@example.test']);
    $this->freezeTime();
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post('/admin/login', ['email' => $admin->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
    }

    $this->post('/admin/login', ['email' => $admin->email, 'password' => 'password'])
        ->assertSessionHasErrors(['email' => 'Çok fazla giriş denemesi. 60 saniye sonra tekrar deneyin.']);
    $this->assertGuest();

    $this->travel(61)->seconds();
    $this->post('/admin/login', ['email' => $admin->email, 'password' => 'password'])->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticatedAs($admin);
});

test('rotating email addresses does not bypass the IP login limit', function () {
    $admin = User::factory()->admin()->create();
    $this->freezeTime();
    for ($attempt = 0; $attempt < 30; $attempt++) {
        $this->post('/admin/login', ['email' => 'attempt-'.$attempt.'@example.test', 'password' => 'wrong']);
    }

    $this->post('/admin/login', ['email' => $admin->email, 'password' => 'password'])
        ->assertSessionHasErrors(['email' => 'Çok fazla giriş denemesi. 60 saniye sonra tekrar deneyin.']);
    $this->assertGuest();
});

test('logout invalidates the session and regenerates the CSRF token', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->withSession(['marker' => 'private-session']);
    $oldToken = session()->token();

    $this->post('/admin/logout')->assertRedirect(route('admin.login'))->assertSessionMissing('marker');

    $this->assertGuest();
    expect(session()->token())->not->toBe($oldToken);
});

test('a revoked administrator loses access to protected pages', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);
    $admin->is_admin = false;
    $admin->save();

    $this->get('/admin')->assertForbidden();
});

test('admin pages prevent private response caching', function () {
    $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
});

test('public registration and GET logout routes are unavailable', function () {
    $this->get('/register')->assertNotFound();
    $this->get('/admin/register')->assertNotFound();
    $this->get('/admin/logout')->assertStatus(405);
});

test('CSRF protection rejects login and admin mutations without a valid token', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create();
    $this->app['env'] = 'local';

    $this->post('/admin/login', ['email' => $admin->email, 'password' => 'password'])->assertStatus(419);
    $this->actingAs($admin)->delete(route('admin.products.destroy', $product))->assertStatus(419);
    $this->assertModelExists($product);
});

test('normal mass assignment cannot grant administrator privileges', function () {
    $user = User::create(['name' => 'Member', 'email' => 'member@example.test', 'password' => 'test-password', 'is_admin' => true]);

    expect($user->fresh()->is_admin)->toBeFalse();
});
