<x-admin.layout title="Blog kategorileri">
    <div class="admin-page-intro"><p>Yazılarınızı ürün kategorilerinden bağımsız olarak düzenleyin.</p><a href="{{ route('admin.post-categories.create') }}" class="admin-button">+ Yeni kategori</a></div>
    <section class="admin-panel"><div class="admin-panel-heading"><div><h2>Kategori listesi</h2><p>{{ $categories->total() }} kategori</p></div><span class="admin-subtle">Sıralama: küçükten büyüğe</span></div>
        <div class="admin-table-scroll"><table class="admin-table"><thead><tr><th scope="col">Kategori</th><th scope="col">İçerik sayısı</th><th scope="col">Durum</th><th scope="col">Sıra</th><th scope="col">İşlemler</th></tr></thead><tbody>
            @forelse($categories as $category)<tr><td><div class="admin-product-cell"><div><a class="admin-table-name" href="{{ route('admin.post-categories.edit', $category) }}">{{ $category->name }}</a><small>{{ $category->slug }}</small></div></div></td><td>{{ $category->posts_count }}</td><td><x-admin.status :active="$category->is_active" /></td><td>{{ $category->sort_order }}</td><td><div class="admin-row-actions"><a href="{{ route('admin.post-categories.edit', $category) }}">Düzenle<span class="sr-only"> {{ $category->name }}</span></a><a class="admin-danger-link" href="{{ route('admin.post-categories.delete', $category) }}">Sil<span class="sr-only"> {{ $category->name }}</span></a></div></td></tr>
            @empty<tr><td colspan="5"><div class="admin-empty"><h3>Henüz kategori yok.</h3><p>İçeriklerinizi gruplamak için ilk kategorinizi oluşturun.</p><a class="admin-link" href="{{ route('admin.post-categories.create') }}">Kategori ekle →</a></div></td></tr>@endforelse
        </tbody></table></div><x-admin.pagination :paginator="$categories" />
    </section>
</x-admin.layout>
