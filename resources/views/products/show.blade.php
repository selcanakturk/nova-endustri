<x-layout :og-image="$product->image ? $product->image_url : null" :title="$product->meta_title ?: $product->name" :description="$product->meta_description ?: $product->short_description ?? 'Nova Endüstri örnek ürün kataloğu.'">
    <x-page-heading :eyebrow="$product->category->name" :title="$product->name" :description="$product->short_description ?? 'Bu ürün grubunun teknik kapsamını projenizle birlikte değerlendirelim.'" />
    <section class="section">
        <div class="container">
            <a class="inline-link" href="{{ route('products') }}">← Ürünler sayfasına dön</a>
            <div class="product-detail-grid">
                <figure class="product-detail-image">
                    <img src="{{ $product->image_url }}" alt="{{ $product->name }} için temsili teknik çizim" width="640" height="440" fetchpriority="high">
                    <figcaption>Özgün, temsili teknik illüstrasyon · Örnek ürün</figcaption>
                </figure>
                <div>
                    <a class="eyebrow inline-block" href="{{ route('products', ['category' => $product->category->slug]) }}">{{ $product->category->name }} ↗</a>
                    <h2>Ürün hakkında</h2>
                    @if($product->sku)<p class="catalog-note mt-5">Ürün kodu: {{ $product->sku }}</p>@endif
                    <p class="product-description">{{ $product->description ?? 'Detaylı açıklama henüz eklenmedi. Teknik ihtiyaçlarınızı birlikte değerlendirebiliriz.' }}</p>
                    <p class="catalog-note mt-6">Örnek katalog içeriğidir. Malzeme, ölçü ve uygulama gereksinimleri proje bazında değerlendirilir.</p>
                    <x-button :href="route('quotes.create', $product->slug)">Teklif İste <span aria-hidden="true">↗</span></x-button>
                </div>
            </div>
        </div>
    </section>
    <x-cta />
</x-layout>
