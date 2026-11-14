<x-layout :canonical="$canonical" title="Ürünler" description="Talaşlı imalat, mekanik sistemler ve metal konstrüksiyon için örnek endüstriyel ürün grupları.">
    <x-page-heading eyebrow="Ürünler" title="Her parça, bir çözümün başlangıcı." description="Mekanik bileşenlerden özel üretim gövdelere, endüstriyel ihtiyaçlara yönelik ürün gruplarını keşfedin." />
    <section class="section">
        <div class="container">
            <div class="section-top">
                <h2 class="catalog-title">Ürün kataloğu</h2>
                <p class="catalog-note">Örnek ürünler · Teknik özellikler proje bazında belirlenir.</p>
            </div>
            <nav class="filters" aria-label="Ürün kategorisi">
                <a href="{{ route('products') }}" @if($selectedCategory === null) aria-current="page" @endif>Tüm ürünler</a>
                @foreach($categories as $category)
                    <a href="{{ route('products', ['category' => $category->slug]) }}" @if($selectedCategory?->is($category)) aria-current="page" @endif>{{ $category->name }}</a>
                @endforeach
            </nav>
            <p class="catalog-note">{{ $selectedCategory?->name ?? 'Tüm kategoriler' }} · {{ $products->total() }} ürün grubu</p>
            <div class="grid gap-7 md:grid-cols-2 lg:grid-cols-3">
                @forelse($products as $product)
                    <x-product-card :product="$product" />
                @empty
                    <div class="catalog-empty md:col-span-2 lg:col-span-3">
                        <h3>Henüz görüntülenecek ürün bulunmuyor.</h3>
                        <p>Bu alandaki ürünler yayınlandığında burada listelenecek.</p>
                        <a class="inline-link" href="{{ route('products') }}">Tüm ürünlere dön ↗</a>
                    </div>
                @endforelse
            </div>
            @if($products->hasPages())
                <nav class="catalog-pagination" aria-label="Ürün sayfaları">
                    @if($products->onFirstPage())<span aria-disabled="true">← Önceki</span>@else<a href="{{ $products->previousPageUrl() }}" rel="prev">← Önceki</a>@endif
                    <span>Sayfa {{ $products->currentPage() }} / {{ $products->lastPage() }}</span>
                    @if($products->hasMorePages())<a href="{{ $products->nextPageUrl() }}" rel="next">Sonraki →</a>@else<span aria-disabled="true">Sonraki →</span>@endif
                </nav>
            @endif
            <p class="catalog-disclaimer">Bu katalog, kurgusal Nova Endüstri sitesi için hazırlanmış örnek içeriktir. Görseller özgün ve temsili teknik çizimlerdir; satışa sunulan gerçek ürünleri veya doğrulanmış teknik özellikleri göstermez.</p>
        </div>
    </section>
    <x-cta />
</x-layout>
