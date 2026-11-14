<?php

use App\Mail\InquiryReceived;
use App\Models\ContactMessage;
use App\Models\Product;
use App\Models\QuoteRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

test('contact notification is sent to the configured administrator', function () {
    config(['inquiries.notification_email' => 'manager@example.test']);
    Mail::fake();

    $this->post('/iletisim', contactData())->assertRedirect();

    Mail::assertSent(InquiryReceived::class, fn (InquiryReceived $mail): bool => $mail->hasTo('manager@example.test') && $mail->inquiry instanceof ContactMessage && $mail->inquiry->exists);
});

test('quote notification is sent after the request has been saved', function () {
    config(['inquiries.notification_email' => 'manager@example.test']);
    Mail::fake();
    $product = Product::factory()->create();

    $this->post(route('quotes.store', $product->slug), quoteData())->assertRedirect();

    Mail::assertSent(InquiryReceived::class, fn (InquiryReceived $mail): bool => $mail->inquiry instanceof QuoteRequest && $mail->inquiry->exists && $mail->inquiry->product_name_snapshot === $product->name);
});

test('unconfigured email delivery never prevents storage or sends mail', function () {
    config(['inquiries.notification_email' => null]);
    Mail::fake();

    $this->post('/iletisim', contactData())->assertRedirect();

    $this->assertDatabaseCount('contact_messages', 1);
    Mail::assertNothingSent();
});

test('contact mail failures preserve the record and log no credentials or customer fields', function () {
    config(['inquiries.notification_email' => 'manager@example.test']);
    Mail::shouldReceive('to')->once()->with('manager@example.test')->andThrow(new RuntimeException('secret SMTP password and customer@example.test'));
    Log::spy();

    $this->post('/iletisim', contactData())->assertRedirect()->assertSessionHas('status', 'Mesajınız alındı. İlginiz için teşekkür ederiz.');

    $this->assertDatabaseCount('contact_messages', 1);
    Log::shouldHaveReceived('warning')->once()->with('Talep bildirimi gönderilemedi.', Mockery::on(fn (array $context): bool => array_keys($context) === ['inquiry_type', 'inquiry_id', 'exception_class'] && $context['inquiry_type'] === 'message' && $context['exception_class'] === RuntimeException::class));
});

test('quote mail failures also preserve submitted requests', function () {
    config(['inquiries.notification_email' => 'manager@example.test']);
    Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('sensitive transport error'));
    Log::spy();
    $product = Product::factory()->create();

    $this->post(route('quotes.store', $product->slug), quoteData())->assertRedirect();

    $this->assertDatabaseCount('quote_requests', 1);
    Log::shouldHaveReceived('warning')->once()->with('Talep bildirimi gönderilemedi.', Mockery::on(fn (array $context): bool => $context['inquiry_type'] === 'quote' && ! array_key_exists('exception', $context)));
});

test('notification content contains only the protected panel link and no personal details', function () {
    $message = ContactMessage::factory()->create(['name' => 'PRIVATE NAME', 'email' => 'private@example.test', 'message' => 'PRIVATE MESSAGE']);
    $mail = new InquiryReceived($message);

    $mail->assertSeeInText(route('admin.messages.show', $message));
    $mail->assertDontSeeInText('PRIVATE NAME');
    $mail->assertDontSeeInText('private@example.test');
    $mail->assertDontSeeInText('PRIVATE MESSAGE');
});
