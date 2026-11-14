<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\QuoteRequest;

beforeEach(function () {
    config(['inquiries.notification_email' => null]);
});

test('contact submissions persist validated customer fields and use post redirect get', function () {
    $this->post('/iletisim', contactData(['status' => 'replied', 'admin_note' => 'Injected note', 'replied_at' => now()->toDateTimeString()]))
        ->assertRedirect(route('contact'))->assertSessionHas('status', 'Mesajınız alındı. İlginiz için teşekkür ederiz.');

    $this->assertDatabaseHas('contact_messages', ['name' => 'Test Customer', 'subject' => 'Üretim sorusu', 'status' => 'new', 'admin_note' => null, 'read_at' => null, 'replied_at' => null]);
    $this->get('/iletisim')->assertOk();
    $this->assertDatabaseCount('contact_messages', 1);
});

test('contact messages allow missing optional contact details', function () {
    $this->post('/iletisim', contactData(['phone' => '', 'company' => '']))->assertRedirect()->assertSessionHasNoErrors();

    $this->assertDatabaseHas('contact_messages', ['phone' => null, 'company' => null]);
});

test('contact validation returns Turkish messages and preserves input', function (array $overrides, string $field, string $error) {
    $this->from('/iletisim')->post('/iletisim', contactData($overrides))
        ->assertRedirect('/iletisim')->assertSessionHasErrors([$field => $error])->assertSessionHasInput('subject', 'Üretim sorusu');
    $this->assertDatabaseCount('contact_messages', 0);
})->with([
    [['name' => ''], 'name', 'Ad soyad alanı zorunludur.'],
    [['name' => str_repeat('x', 151)], 'name', 'Ad soyad en fazla 150 karakter olabilir.'],
    [['email' => 'invalid'], 'email', 'Geçerli bir e-posta adresi girin.'],
    [['message' => ''], 'message', 'Mesaj alanı zorunludur.'],
    [['message' => str_repeat('x', 10001)], 'message', 'Mesaj en fazla 10000 karakter olabilir.'],
    [['company' => str_repeat('x', 256)], 'company', 'Firma en fazla 255 karakter olabilir.'],
    [['phone' => 'invalid-phone'], 'phone', 'Geçerli bir telefon numarası girin.'],
]);

test('contact subject is required and bounded', function () {
    $this->post('/iletisim', contactData(['subject' => '']))->assertSessionHasErrors(['subject' => 'Konu alanı zorunludur.']);
    $this->post('/iletisim', contactData(['subject' => str_repeat('x', 256)]))->assertSessionHasErrors('subject');
    $this->assertDatabaseCount('contact_messages', 0);
});

test('invalid input is safely redisplayed and privacy information is honest', function () {
    $this->followingRedirects()->from('/iletisim')->post('/iletisim', contactData(['name' => ['invalid'], 'message' => '<script>attack()</script>']))
        ->assertOk()->assertSee('<script>attack()</script>')->assertDontSee('<script>attack()</script>', false);
    $this->get('/aydinlatma')->assertOk()->assertSeeText('hukuken onaylanmış bir KVKK aydınlatma metni değildir');
});

test('quote form is product specific and product detail links to it', function () {
    $product = Product::factory()->create();

    $this->get(route('quotes.create', $product->slug))->assertOk()->assertSeeText($product->name)->assertSee('name="_token"', false);
    $this->get(route('products.show', $product->slug))->assertOk()->assertSee('href="'.route('quotes.create', $product->slug).'"', false)->assertSeeText('Teklif İste');
});

test('quote submission takes product snapshot and identity from the server without accepting prices', function () {
    $product = Product::factory()->create(['name' => 'Original product']);
    $other = Product::factory()->create();

    $this->post(route('quotes.store', $product->slug), quoteData(['product_id' => $other->id, 'product_name_snapshot' => 'Fake name', 'status' => 'quoted', 'price' => 1, 'admin_note' => 'Injected']))
        ->assertRedirect(route('quotes.create', $product->slug))->assertSessionHas('status', 'Teklif talebiniz alındı. İlginiz için teşekkür ederiz.');

    $quote = QuoteRequest::firstOrFail();
    expect($quote)->product_id->toBe($product->id)->product_name_snapshot->toBe('Original product')->status->toBe('new')->quantity->toBe('12.500')->admin_note->toBeNull()->quoted_at->toBeNull();
    expect($quote->getAttributes())->not->toHaveKey('price');
    expect($product->quoteRequests->sole()->is($quote))->toBeTrue();
    $this->get(route('quotes.create', $product->slug))->assertOk();
    $this->assertDatabaseCount('quote_requests', 1);
});

test('quotes retain product snapshot and survive product deletion', function () {
    $product = Product::factory()->create(['name' => 'Snapshot product']);
    $this->post(route('quotes.store', $product->slug), quoteData())->assertRedirect();
    $product->update(['name' => 'Renamed product']);

    $product->delete();

    $quote = QuoteRequest::firstOrFail();
    expect($quote)->product_id->toBeNull()->product_name_snapshot->toBe('Snapshot product');
    expect($quote->product)->toBeNull();
});

test('inactive products and categories cannot receive quote requests', function (bool $productActive, bool $categoryActive) {
    $product = Product::factory()->for(Category::factory()->create(['is_active' => $categoryActive]))->create(['is_active' => $productActive]);

    $this->get(route('quotes.create', $product->slug))->assertNotFound();
    $this->post(route('quotes.store', $product->slug), quoteData())->assertNotFound();
    $this->assertDatabaseCount('quote_requests', 0);
})->with([[false, true], [true, false], [false, false]]);

test('unknown quote product slugs return 404', function () {
    $this->get('/urunler/not-found/teklif')->assertNotFound();
    $this->post('/urunler/not-found/teklif', quoteData())->assertNotFound();
});

test('quote validation rejects invalid quantities and customer fields', function (array $overrides, string $field) {
    $product = Product::factory()->create();

    $this->post(route('quotes.store', $product->slug), quoteData($overrides))->assertSessionHasErrors($field);
    $this->assertDatabaseCount('quote_requests', 0);
})->with([
    [['name' => ''], 'name'], [['email' => 'bad'], 'email'], [['phone' => ''], 'phone'],
    [['quantity' => 0], 'quantity'], [['quantity' => -1], 'quantity'], [['quantity' => 'invalid'], 'quantity'],
    [['quantity' => '1.0001'], 'quantity'], [['quantity' => '1000000000'], 'quantity'],
    [['unit' => str_repeat('x', 31)], 'unit'], [['message' => str_repeat('x', 10001)], 'message'],
]);

test('quotes allow optional company unit and explanation fields to be empty', function () {
    $product = Product::factory()->create();

    $this->post(route('quotes.store', $product->slug), quoteData(['company' => '', 'unit' => '', 'message' => '']))->assertRedirect()->assertSessionHasNoErrors();
    $this->assertDatabaseHas('quote_requests', ['company' => null, 'unit' => null, 'message' => null]);
});

test('honeypots reject bot submissions on both forms', function () {
    $product = Product::factory()->create();

    $this->post('/iletisim', contactData(['website' => 'https://spam.example']))->assertSessionHasErrors('website');
    $this->post(route('quotes.store', $product->slug), quoteData(['website' => 'spam']))->assertSessionHasErrors('website');
    $this->assertDatabaseCount('contact_messages', 0);
    $this->assertDatabaseCount('quote_requests', 0);
});

test('both public forms share a five submissions per minute limit and keep submitted input', function () {
    $product = Product::factory()->create();
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post('/iletisim', contactData())->assertRedirect();
    }

    $this->post(route('quotes.store', $product->slug), quoteData())->assertStatus(429)->assertSeeText('Biraz sonra tekrar deneyin.')->assertSessionHasInput('name', 'Quote Customer');
    $this->assertDatabaseCount('contact_messages', 5);
    $this->assertDatabaseCount('quote_requests', 0);
});

test('public throttling expires after a minute and still enforces its hourly cap', function () {
    $this->freezeTime();
    for ($batch = 0; $batch < 4; $batch++) {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/iletisim', contactData())->assertRedirect();
        }
        $this->travel(61)->seconds();
    }

    $this->post('/iletisim', contactData())->assertStatus(429);
    $this->assertDatabaseCount('contact_messages', 20);
});

test('CSRF protects both public submissions', function () {
    $product = Product::factory()->create();
    $this->app['env'] = 'local';

    $this->post('/iletisim', contactData())->assertStatus(419);
    $this->post(route('quotes.store', $product->slug), quoteData())->assertStatus(419);
    $this->assertDatabaseCount('contact_messages', 0);
    $this->assertDatabaseCount('quote_requests', 0);
});
