<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InquiryFilterRequest;
use App\Http\Requests\Admin\UpdateInquiryRequest;
use App\Models\QuoteRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class QuoteController extends Controller
{
    public function index(InquiryFilterRequest $request): View
    {
        $filters = $request->validated();
        $query = QuoteRequest::query();

        if (filled($filters['q'] ?? null)) {
            $search = '%'.$filters['q'].'%';
            $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhere('company', 'like', $search)
                    ->orWhere('product_name_snapshot', 'like', $search);
            });
        }
        if (filled($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }
        $query->ordered(($filters['sort'] ?? 'newest') === 'oldest' ? 'asc' : 'desc');

        return view('admin.inquiries.index', [
            'entries' => $query->paginate(15)->appends($filters), 'filters' => $filters,
            'statuses' => QuoteRequest::STATUSES, 'isQuote' => true,
            'resource' => 'quotes', 'title' => 'Teklif talepleri',
        ]);
    }

    public function show(QuoteRequest $quote): View
    {
        $quote->load('product');

        return view('admin.inquiries.show', ['entry' => $quote, 'statuses' => QuoteRequest::STATUSES, 'isQuote' => true, 'resource' => 'quotes']);
    }

    public function update(UpdateInquiryRequest $request, QuoteRequest $quote): RedirectResponse
    {
        $data = $request->validated();
        $quote->transitionTo($data['status'], array_key_exists('admin_note', $data) ? $data['admin_note'] : $quote->admin_note);

        return to_route('admin.quotes.show', $quote)->with('status', 'Kayıt güncellendi.');
    }

    public function confirmDelete(QuoteRequest $quote): View
    {
        return view('admin.confirm-delete', [
            'entry' => $quote, 'entryName' => $quote->name, 'type' => 'Teklif talebi', 'blocked' => false,
            'destroyRoute' => route('admin.quotes.destroy', $quote), 'cancelRoute' => route('admin.quotes.show', $quote),
            'deleteDescription' => 'Bu kayıt ve yönetici notları kalıcı olarak silinecek. Devam etmek istediğinizden emin misiniz?',
        ]);
    }

    public function destroy(QuoteRequest $quote): RedirectResponse
    {
        $quote->delete();

        return to_route('admin.quotes.index')->with('status', 'Kayıt silindi.');
    }
}
