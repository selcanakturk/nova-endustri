<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuoteRequest;
use App\Models\Product;
use App\Models\QuoteRequest;
use App\SendInquiryNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class QuoteController extends Controller
{
    public function create(string $slug): View
    {
        return view('quotes.create', ['product' => Product::visible()->with('category')->where('slug', $slug)->firstOrFail()]);
    }

    public function store(StoreQuoteRequest $request, string $slug, SendInquiryNotification $notify): RedirectResponse
    {
        $product = Product::visible()->where('slug', $slug)->firstOrFail();
        $quote = new QuoteRequest($request->safe()->except('website'));
        $quote->product()->associate($product);
        $quote->product_name_snapshot = $product->name;
        $quote->save();
        $notify($quote);

        return to_route('quotes.create', $slug)->with('status', 'Teklif talebiniz alındı. İlginiz için teşekkür ederiz.');
    }
}
