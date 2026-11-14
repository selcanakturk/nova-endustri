<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use App\Models\ContactMessage;
use App\SendInquiryNotification;
use Illuminate\Http\RedirectResponse;

class ContactController extends Controller
{
    public function store(StoreContactMessageRequest $request, SendInquiryNotification $notify): RedirectResponse
    {
        $message = ContactMessage::create($request->safe()->except('website'));
        $notify($message);

        return to_route('contact')->with('status', 'Mesajınız alındı. İlginiz için teşekkür ederiz.');
    }
}
