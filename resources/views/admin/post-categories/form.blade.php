<x-admin.layout :title="$category->exists ? 'Blog kategorisini düzenle' : 'Yeni blog kategorisi'">
    <div class="admin-page-intro"><a class="admin-link" href="{{ route('admin.post-categories.index') }}">← Kategori listesine dön</a><span class="admin-subtle">* işaretli alanlar zorunludur</span></div>
    <form method="POST" action="{{ $category->exists ? route('admin.post-categories.update', $category) : route('admin.post-categories.store') }}" enctype="multipart/form-data" class="admin-editor">
        @csrf @if($category->exists) @method('PUT') @endif
        <div class="admin-panel admin-form-panel"><h2>Kategori bilgileri</h2><p class="admin-panel-description">Kategorinin adını ve katalogdaki yerini belirleyin.</p>
            <x-admin.field name="name" label="Kategori adı" :value="$category->name" maxlength="255" required />
            <x-admin.field name="slug" label="Slug" :value="$category->slug" maxlength="255" hint="Boş bırakırsanız addan üretilir. Küçük harf, rakam ve kısa çizgi kullanın." />
            <x-admin.field name="description" label="Açıklama" type="textarea" :value="$category->description" rows="6" maxlength="10000" />
        </div>
        <aside class="admin-editor-side"><section class="admin-panel admin-form-panel"><h2>Yayın ayarları</h2><label class="admin-check"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active))> Kategori aktif</label><p class="admin-field-hint">Pasif kategorilere bağlı yazılar ve haberler sitede gizlenir.</p><x-admin.field name="sort_order" label="Görüntülenme sırası" type="number" :value="$category->sort_order" min="0" max="2147483647" required hint="Küçük değerler önce gösterilir." /></section></aside>
        <div class="admin-save-bar"><a class="admin-button admin-button-secondary" href="{{ route('admin.post-categories.index') }}">Vazgeç</a><button type="submit" class="admin-button">{{ $category->exists ? 'Değişiklikleri kaydet' : 'Kategoriyi oluştur' }} <span aria-hidden="true">→</span></button></div>
    </form>
</x-admin.layout>
