<x-admin.layout title="Ürünler">
    <div class="admin-page-intro"><p>Katalog içeriğini, yayın durumunu ve öne çıkan ürünleri yönetin.</p><a href="{{ route('admin.products.create') }}" class="admin-button">+ Yeni ürün</a></div>
    <form method="GET" action="{{ route('admin.products.index') }}" class="admin-filter-bar">
        <x-admin.field name="q" label="Ürün ara" :value="$filters['q'] ?? ''" placeholder="Ürün adı, SKU veya slug" maxlength="120" />
        <x-admin.field name="category_id" label="Kategori" type="select" :value="$filters['category_id'] ?? ''" :options="['' => 'Tüm kategoriler'] + $categories->mapWithKeys(fn ($category) => [$category->id => $category->name.($category->is_active ? '' : ' (Pasif)')])->all()" />
        <x-admin.field name="status" label="Durum" type="select" :value="$filters['status'] ?? ''" :options="['' => 'Tüm durumlar', 'active' => 'Aktif', 'inactive' => 'Pasif']" />
        <div class="admin-filter-actions"><button type="submit" class="admin-button">Filtrele</button><a class="admin-link" href="{{ route('admin.products.index') }}">Temizle</a></div>
    </form>
    <section class="admin-panel"><div class="admin-panel-heading"><div><h2>Ürün listesi</h2><p>{{ $products->total() }} ürün bulundu</p></div><span class="admin-subtle">Sıralama: küçükten büyüğe</span></div>
        <div class="admin-table-scroll"><table class="admin-table"><thead><tr><th scope="col">Ürün</th><th scope="col">Kategori</th><th scope="col">Durum</th><th scope="col">Öne çıkan</th><th scope="col">Sıra</th><th scope="col">İşlemler</th></tr></thead><tbody>
            @forelse($products as $product)<tr><td><div class="admin-product-cell"><img src="{{ $product->image_url }}" alt="" width="48" height="48"><div><a class="admin-table-name" href="{{ route('admin.products.edit', $product) }}">{{ $product->name }}</a><small>{{ $product->sku ?? 'SKU belirtilmedi' }}</small></div></div></td><td>{{ $product->category->name }}@if(!$product->category->is_active)<small class="admin-text-warning">Kategori pasif</small>@endif</td><td><x-admin.status :active="$product->is_active" /></td><td><span class="admin-featured">{{ $product->is_featured ? '★ Evet' : '—' }}</span></td><td>{{ $product->sort_order }}</td><td><div class="admin-row-actions"><a href="{{ route('admin.products.edit', $product) }}">Düzenle<span class="sr-only"> {{ $product->name }}</span></a><a class="admin-danger-link" href="{{ route('admin.products.delete', $product) }}">Sil<span class="sr-only"> {{ $product->name }}</span></a></div></td></tr>
            @empty<tr><td colspan="6"><div class="admin-empty"><h3>Görüntülenecek ürün bulunamadı.</h3><p>Filtreleri değiştirebilir veya yeni bir ürün ekleyebilirsiniz.</p><a class="admin-link" href="{{ route('admin.products.create') }}">Ürün ekle →</a></div></td></tr>@endforelse
        </tbody></table></div><x-admin.pagination :paginator="$products" />
    </section>
</x-admin.layout>
