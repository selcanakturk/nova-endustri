<?php

namespace App\Mail;

use App\Models\ContactMessage;
use App\Models\QuoteRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InquiryReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ContactMessage|QuoteRequest $inquiry) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->inquiry instanceof ContactMessage ? 'Yeni iletişim mesajı | Nova Endüstri' : 'Yeni teklif talebi | Nova Endüstri');
    }

    public function content(): Content
    {
        return new Content(text: 'mail.inquiry-received', with: [
            'label' => $this->inquiry instanceof ContactMessage ? 'İletişim mesajı' : 'Teklif talebi',
            'adminUrl' => route($this->inquiry instanceof ContactMessage ? 'admin.messages.show' : 'admin.quotes.show', $this->inquiry),
        ]);
    }
}
