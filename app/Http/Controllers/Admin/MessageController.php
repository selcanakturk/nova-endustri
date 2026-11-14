<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InquiryFilterRequest;
use App\Http\Requests\Admin\UpdateInquiryRequest;
use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function index(InquiryFilterRequest $request): View
    {
        $filters = $request->validated();
        $query = ContactMessage::query();

        if (filled($filters['q'] ?? null)) {
            $search = '%'.$filters['q'].'%';
            $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhere('company', 'like', $search)
                    ->orWhere('subject', 'like', $search);
            });
        }
        if (filled($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }
        $query->ordered(($filters['sort'] ?? 'newest') === 'oldest' ? 'asc' : 'desc');

        return view('admin.inquiries.index', [
            'entries' => $query->paginate(15)->appends($filters), 'filters' => $filters,
            'statuses' => ContactMessage::STATUSES, 'isQuote' => false,
            'resource' => 'messages', 'title' => 'İletişim mesajları',
        ]);
    }

    public function show(ContactMessage $message): View
    {

        return view('admin.inquiries.show', ['entry' => $message, 'statuses' => ContactMessage::STATUSES, 'isQuote' => false, 'resource' => 'messages']);
    }

    public function update(UpdateInquiryRequest $request, ContactMessage $message): RedirectResponse
    {
        $data = $request->validated();
        $message->transitionTo($data['status'], array_key_exists('admin_note', $data) ? $data['admin_note'] : $message->admin_note);

        return to_route('admin.messages.show', $message)->with('status', 'Kayıt güncellendi.');
    }

    public function confirmDelete(ContactMessage $message): View
    {
        return view('admin.confirm-delete', [
            'entry' => $message, 'entryName' => $message->name, 'type' => 'İletişim mesajı', 'blocked' => false,
            'destroyRoute' => route('admin.messages.destroy', $message), 'cancelRoute' => route('admin.messages.show', $message),
            'deleteDescription' => 'Bu kayıt ve yönetici notları kalıcı olarak silinecek. Devam etmek istediğinizden emin misiniz?',
        ]);
    }

    public function destroy(ContactMessage $message): RedirectResponse
    {
        $message->delete();

        return to_route('admin.messages.index')->with('status', 'Kayıt silindi.');
    }
}
