<?php

namespace App;

use App\Mail\InquiryReceived;
use App\Models\ContactMessage;
use App\Models\QuoteRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendInquiryNotification
{
    public function __invoke(ContactMessage|QuoteRequest $inquiry): void
    {
        $recipient = config('inquiries.notification_email');
        if (blank($recipient)) {
            return;
        }

        try {
            Mail::to($recipient)->send(new InquiryReceived($inquiry));
        } catch (Throwable $exception) {
            Log::warning('Talep bildirimi gönderilemedi.', [
                'inquiry_type' => $inquiry instanceof ContactMessage ? 'message' : 'quote',
                'inquiry_id' => $inquiry->id, 'exception_class' => $exception::class,
            ]);
        }
    }
}
