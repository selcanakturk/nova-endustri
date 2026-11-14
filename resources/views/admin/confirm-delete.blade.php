<x-admin.layout :title="$type.' silme onayı'">
    <div class="admin-confirm admin-panel"><span class="admin-confirm-icon" aria-hidden="true">!</span><h2>{{ $entryName ?? $entry->name }}</h2>
        @if($blocked)<p>{{ $type === 'Blog kategorisi' ? 'Bu kategoriye bağlı içerikler var. Önce içerikleri başka bir kategoriye taşıyın. Kategori şu anda silinemez.' : 'Bu kategoriye bağlı ürünler var. Önce ürünleri başka bir kategoriye taşıyın. Kategori şu anda silinemez.' }}</p><a class="admin-button admin-button-secondary" href="{{ $cancelRoute }}">Listeye dön</a>
        @else<p>{{ $deleteDescription ?? 'Bu '.mb_strtolower($type).' kalıcı olarak silinecek ve kurumsal siteden kaldırılacak. Devam etmek istediğinizden emin misiniz?' }}</p><div class="admin-confirm-actions"><a class="admin-button admin-button-secondary" href="{{ $cancelRoute }}">Vazgeç</a><form method="POST" action="{{ $destroyRoute }}">@csrf @method('DELETE')<button class="admin-button admin-button-danger" type="submit">Evet, sil</button></form></div>@endif
    </div>
</x-admin.layout>
