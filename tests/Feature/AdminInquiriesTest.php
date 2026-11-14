<?php

use App\Models\ContactMessage;
use App\Models\Product;
use App\Models\QuoteRequest;
use App\Models\User;

dataset('inquiry endpoints', [
    ['GET', '/admin/messages'], ['GET', '/admin/messages/%d'], ['PATCH', '/admin/messages/%d'], ['DELETE', '/admin/messages/%d'], ['GET', '/admin/messages/%d/delete'],
    ['GET', '/admin/quotes'], ['GET', '/admin/quotes/%d'], ['PATCH', '/admin/quotes/%d'], ['DELETE', '/admin/quotes/%d'], ['GET', '/admin/quotes/%d/delete'],
]);

test('guests cannot access personal inquiry data or mutate records', function (string $method, string $path) {
    $message = ContactMessage::factory()->create();
    $quote = QuoteRequest::factory()->create();
    $id = str_contains($path, '/quotes') ? $quote->id : $message->id;

    $this->call($method, sprintf($path, $id), ['status' => 'archived', 'admin_note' => 'unauthorized'])->assertRedirect(route('admin.login'));
    expect($message->fresh()->status)->toBe('new');
    expect($quote->fresh()->status)->toBe('new');
    $this->assertModelExists($message);
    $this->assertModelExists($quote);
})->with('inquiry endpoints');

test('non administrators cannot access personal inquiry data or mutate records', function (string $method, string $path) {
    $message = ContactMessage::factory()->create();
    $quote = QuoteRequest::factory()->create();
    $id = str_contains($path, '/quotes') ? $quote->id : $message->id;

    $this->actingAs(User::factory()->create())->call($method, sprintf($path, $id), ['status' => 'archived', 'admin_note' => 'unauthorized'])->assertForbidden();
    $this->assertModelExists($message);
    $this->assertModelExists($quote);
    expect($message->fresh()->admin_note)->toBeNull();
    expect($quote->fresh()->admin_note)->toBeNull();
})->with('inquiry endpoints');

test('administrators can inspect both inquiry lists details and confirmations', function (string $path) {
    $message = ContactMessage::factory()->create();
    $quote = QuoteRequest::factory()->create();
    $id = str_contains($path, '/quotes') ? $quote->id : $message->id;

    $this->actingAs(User::factory()->admin()->create())->get(sprintf($path, $id))->assertOk();
})->with([
    ['/admin/messages'], ['/admin/messages/%d'], ['/admin/messages/%d/delete'],
    ['/admin/quotes'], ['/admin/quotes/%d'], ['/admin/quotes/%d/delete'],
]);

test('reading a detail page does not silently change its status', function () {
    $message = ContactMessage::factory()->create();

    $this->actingAs(User::factory()->admin()->create())->get(route('admin.messages.show', $message))->assertOk();

    expect($message->fresh())->status->toBe('new')->read_at->toBeNull();
});

test('message status transitions keep read and reply timestamps consistent', function () {
    $this->freezeTime();
    $message = ContactMessage::factory()->create();
    $this->actingAs(User::factory()->admin()->create());

    $this->patch(route('admin.messages.update', $message), ['status' => 'read', 'admin_note' => 'İlk inceleme'])->assertRedirect();
    $readAt = $message->fresh()->read_at->toDateTimeString();
    expect($message->fresh())->status->toBe('read')->admin_note->toBe('İlk inceleme')->replied_at->toBeNull();
    $this->travel(1)->minutes();
    $this->patch(route('admin.messages.update', $message), ['status' => 'replied', 'admin_note' => 'Yanıtlandı'])->assertRedirect();
    $replyAt = $message->fresh()->replied_at->toDateTimeString();
    expect($message->fresh()->read_at->toDateTimeString())->toBe($readAt);
    expect($replyAt)->toBe(now()->toDateTimeString());
    $this->travel(1)->minutes();
    $this->patch(route('admin.messages.update', $message), ['status' => 'replied'])->assertRedirect();
    expect($message->fresh()->replied_at->toDateTimeString())->toBe($replyAt);
    $this->patch(route('admin.messages.update', $message), ['status' => 'archived'])->assertRedirect();
    expect($message->fresh()->replied_at->toDateTimeString())->toBe($replyAt);
    $this->patch(route('admin.messages.update', $message), ['status' => 'read'])->assertRedirect();
    expect($message->fresh()->replied_at)->toBeNull();
    $this->patch(route('admin.messages.update', $message), ['status' => 'new'])->assertRedirect();
    expect($message->fresh())->read_at->toBeNull()->replied_at->toBeNull();
});

test('quote status transitions set preserve and reset the quoted timestamp correctly', function () {
    $this->freezeTime();
    $quote = QuoteRequest::factory()->create();
    $this->actingAs(User::factory()->admin()->create());

    $this->patch(route('admin.quotes.update', $quote), ['status' => 'reviewing', 'admin_note' => 'İnceleniyor'])->assertRedirect();
    expect($quote->fresh()->quoted_at)->toBeNull();
    $this->patch(route('admin.quotes.update', $quote), ['status' => 'quoted'])->assertRedirect();
    $quotedAt = $quote->fresh()->quoted_at->toDateTimeString();
    expect($quotedAt)->toBe(now()->toDateTimeString());
    $this->travel(1)->minutes();
    $this->patch(route('admin.quotes.update', $quote), ['status' => 'closed'])->assertRedirect();
    expect($quote->fresh()->quoted_at->toDateTimeString())->toBe($quotedAt);
    $this->patch(route('admin.quotes.update', $quote), ['status' => 'cancelled'])->assertRedirect();
    expect($quote->fresh()->quoted_at->toDateTimeString())->toBe($quotedAt);
    $this->patch(route('admin.quotes.update', $quote), ['status' => 'new'])->assertRedirect();
    expect($quote->fresh()->quoted_at)->toBeNull();
});

test('admin inquiry validation rejects unsupported states and excessive notes', function () {
    $message = ContactMessage::factory()->create();
    $quote = QuoteRequest::factory()->create();
    $this->actingAs(User::factory()->admin()->create());

    $this->patch(route('admin.messages.update', $message), ['status' => 'quoted'])->assertSessionHasErrors('status');
    $this->patch(route('admin.quotes.update', $quote), ['status' => 'replied'])->assertSessionHasErrors('status');
    $this->patch(route('admin.quotes.update', $quote), ['status' => 'new', 'admin_note' => str_repeat('x', 10001)])->assertSessionHasErrors('admin_note');
    expect($message->fresh()->status)->toBe('new');
    expect($quote->fresh()->admin_note)->toBeNull();
});

test('administrator updates cannot replace customer data quantity or timestamps from input', function () {
    $quote = QuoteRequest::factory()->create(['name' => 'Original Customer', 'quantity' => 5]);

    $this->actingAs(User::factory()->admin()->create())->patch(route('admin.quotes.update', $quote), ['status' => 'reviewing', 'name' => 'Injected', 'quantity' => 1, 'quoted_at' => now(), 'admin_note' => 'Checked'])->assertRedirect();

    expect($quote->fresh())->name->toBe('Original Customer')->quantity->toBe('5.000')->quoted_at->toBeNull()->admin_note->toBe('Checked');
});

test('inquiries support search status filters and chronological sorting', function () {
    $message = ContactMessage::factory()->create(['name' => 'Matching Customer', 'subject' => 'Unique search', 'created_at' => now()->subDays(2)]);
    $other = ContactMessage::factory()->create(['name' => 'Other Customer', 'subject' => 'Unique search']);
    $other->transitionTo('read', null);
    $quote = QuoteRequest::factory()->create(['product_name_snapshot' => 'Matching Product', 'company' => 'Search Company']);
    QuoteRequest::factory()->create(['product_name_snapshot' => 'Other Product']);
    $this->actingAs(User::factory()->admin()->create());

    $this->get('/admin/messages?q=Unique&status=new')->assertOk()->assertSeeText('Matching Customer')->assertDontSeeText('Other Customer');
    $this->get('/admin/messages?sort=oldest')->assertOk()->assertSeeInOrder(['Matching Customer', 'Other Customer']);
    $this->get('/admin/quotes?q=Search')->assertOk()->assertSeeText('Matching Product')->assertDontSeeText('Other Product');
});

test('message and quote pagination retain their filters', function () {
    ContactMessage::factory()->count(16)->sequence(fn ($sequence): array => ['subject' => 'Message '.$sequence->index, 'created_at' => now()->subMinutes($sequence->index)])->create();
    QuoteRequest::factory()->count(16)->sequence(fn ($sequence): array => ['product_name_snapshot' => 'Quote '.$sequence->index.' item', 'created_at' => now()->subMinutes($sequence->index)])->create();
    $this->actingAs(User::factory()->admin()->create());

    $this->get('/admin/messages?status=new&sort=newest')->assertOk()->assertSeeText('Message 0')->assertDontSeeText('Message 15')->assertSee('status=new&amp;sort=newest&amp;page=2', false);
    $this->get('/admin/quotes?status=new')->assertOk()->assertSeeText('Quote 0 item')->assertDontSeeText('Quote 15 item')->assertSee('status=new&amp;page=2', false);
});

test('invalid list filters are rejected before query construction', function () {
    $this->actingAs(User::factory()->admin()->create())->get('/admin/messages?sort=invalid&status=quoted&q[]=bad')
        ->assertSessionHasErrors(['sort', 'status', 'q']);
});

test('historical quote details remain available after the product has been deleted', function () {
    $product = Product::factory()->create();
    $quote = QuoteRequest::factory()->for($product)->create(['product_name_snapshot' => 'Historic product']);
    $product->delete();

    $this->actingAs(User::factory()->admin()->create())->get(route('admin.quotes.show', $quote))
        ->assertOk()->assertSeeText('Historic product')->assertSeeText('Ürün silinmiş; talep anındaki ad korunuyor.');
});

test('inquiry details and notes escape markup and never expose notes publicly', function () {
    $message = ContactMessage::factory()->create(['name' => '<script>customer()</script>', 'message' => '<script>message()</script>', 'admin_note' => 'PRIVATE NOTE SENTINEL']);
    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('admin.messages.show', $message))->assertOk()->assertSee('<script>customer()</script>')->assertDontSee('<script>customer()</script>', false)
        ->assertSee('<script>message()</script>')->assertDontSee('<script>message()</script>', false)->assertSeeText('PRIVATE NOTE SENTINEL');
    $this->get('/iletisim')->assertOk()->assertDontSeeText('PRIVATE NOTE SENTINEL');
    $this->get('/')->assertOk()->assertDontSeeText('PRIVATE NOTE SENTINEL');
});

test('dashboard shows genuine new counts and latest incoming records', function () {
    ContactMessage::factory()->create(['name' => 'Latest sender']);
    $read = ContactMessage::factory()->create();
    $read->transitionTo('read', null);
    QuoteRequest::factory()->create(['product_name_snapshot' => 'Latest request']);

    $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertOk()
        ->assertViewHas('inquiryStats', ['messages' => 1, 'quotes' => 1])->assertSeeText('Latest sender')->assertSeeText('Latest request')
        ->assertViewHas('stats', ['total_products' => 0, 'active_products' => 0, 'inactive_products' => 0, 'total_categories' => 0]);
});

test('administrators can delete messages and quotes after opening a confirmation', function () {
    $message = ContactMessage::factory()->create();
    $quote = QuoteRequest::factory()->create();
    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('admin.messages.delete', $message))->assertOk()->assertSeeText('Evet, sil');
    $this->assertModelExists($message);
    $this->delete(route('admin.messages.destroy', $message))->assertRedirect(route('admin.messages.index'));
    $this->assertModelMissing($message);
    $this->delete(route('admin.quotes.destroy', $quote))->assertRedirect(route('admin.quotes.index'));
    $this->assertModelMissing($quote);
});

test('empty inquiry lists show a useful empty state', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->get('/admin/messages')->assertOk()->assertSeeText('Henüz görüntülenecek kayıt bulunmuyor.');
    $this->get('/admin/quotes')->assertOk()->assertSeeText('Henüz görüntülenecek kayıt bulunmuyor.');
});

test('CSRF remains enabled for private inquiry updates and deletes', function () {
    $message = ContactMessage::factory()->create();
    $this->actingAs(User::factory()->admin()->create());
    $this->app['env'] = 'local';

    $this->patch(route('admin.messages.update', $message), ['status' => 'read'])->assertStatus(419);
    $this->delete(route('admin.messages.destroy', $message))->assertStatus(419);
    $this->assertModelExists($message);
});

test('status only updates preserve notes and explicit empty notes clear them', function () {
    $message = ContactMessage::factory()->create(['admin_note' => 'Keep message note']);
    $quote = QuoteRequest::factory()->create(['admin_note' => 'Keep quote note']);
    $this->actingAs(User::factory()->admin()->create());

    $this->patch(route('admin.messages.update', $message), ['status' => 'read'])->assertRedirect();
    $this->patch(route('admin.quotes.update', $quote), ['status' => 'reviewing'])->assertRedirect();
    expect($message->fresh()->admin_note)->toBe('Keep message note');
    expect($quote->fresh()->admin_note)->toBe('Keep quote note');
    $this->patch(route('admin.messages.update', $message), ['status' => 'read', 'admin_note' => ''])->assertRedirect();
    $this->patch(route('admin.quotes.update', $quote), ['status' => 'reviewing', 'admin_note' => ''])->assertRedirect();
    expect($message->fresh()->admin_note)->toBeNull();
    expect($quote->fresh()->admin_note)->toBeNull();
});
