<x-admin.layout :title="$title">
    <div class="admin-page-intro"><p>Gelen {{ $isQuote ? 'teklif taleplerini' : 'iletişim mesajlarını' }} inceleyin ve takip durumunu güncelleyin.</p><span class="admin-subtle">{{ $entries->total() }} kayıt</span></div>
    <form method="GET" action="{{ route('admin.'.$resource.'.index') }}" class="admin-filter-bar">
        <x-admin.field name="q" label="Arama" :value="$filters['q'] ?? ''" maxlength="120" :placeholder="$isQuote ? 'Ad, e-posta, firma veya ürün' : 'Ad, e-posta, firma veya konu'" />
        <x-admin.field name="status" label="Durum" type="select" :value="$filters['status'] ?? ''" :options="['' => 'Tüm durumlar'] + $statuses" />
        <x-admin.field name="sort" label="Tarih sıralaması" type="select" :value="$filters['sort'] ?? 'newest'" :options="['newest' => 'En yeni önce', 'oldest' => 'En eski önce']" />
        <div class="admin-filter-actions"><button type="submit" class="admin-button">Filtrele</button><a class="admin-link" href="{{ route('admin.'.$resource.'.index') }}">Temizle</a></div>
    </form>
    <section class="admin-panel"><div class="admin-table-scroll"><table class="admin-table"><thead><tr><th scope="col">Gönderen</th><th scope="col">{{ $isQuote ? 'Ürün / Miktar' : 'Konu' }}</th><th scope="col">Durum</th><th scope="col">Tarih</th><th scope="col">İşlemler</th></tr></thead><tbody>
        @forelse($entries as $entry)<tr><td><a class="admin-table-name" href="{{ route('admin.'.$resource.'.show', $entry) }}">{{ $entry->name }}</a><small>{{ $entry->email }}</small><small>{{ $entry->company }}</small></td><td>@if($isQuote){{ $entry->product_name_snapshot }}<small>{{ $entry->quantity }} {{ $entry->unit }}</small>@else{{ $entry->subject }}@endif</td><td><x-admin.inquiry-status :status="$entry->status" :statuses="$statuses" /></td><td class="admin-date-cell">{{ $entry->created_at->timezone(config('nova.editorial_timezone'))->format('d.m.Y H:i') }}</td><td><div class="admin-row-actions"><a href="{{ route('admin.'.$resource.'.show', $entry) }}">İncele<span class="sr-only"> {{ $entry->name }}</span></a><a class="admin-danger-link" href="{{ route('admin.'.$resource.'.delete', $entry) }}">Sil<span class="sr-only"> {{ $entry->name }}</span></a></div></td></tr>
        @empty<tr><td colspan="5"><div class="admin-empty"><h3>Henüz görüntülenecek kayıt bulunmuyor.</h3><p>Yeni gönderimler burada listelenecek. Filtreleri de kontrol edebilirsiniz.</p></div></td></tr>@endforelse
    </tbody></table></div><x-admin.pagination :paginator="$entries" /></section>
</x-admin.layout>
