@props(['product'])
<article {{ $attributes->class(['product-card']) }}>
    <a href="{{ route('products.show', $product->slug) }}" class="product-image block" aria-label="{{ $product->name }} detayını inceleyin">
        <img src="{{ $product->image_url }}" alt="{{ $product->name }} için temsili teknik çizim" width="640" height="440" loading="lazy">
        <span>TEKNİK İLLÜSTRASYON</span>
    </a>
    <div class="product-body">
        <p class="eyebrow">{{ $product->category->name }}</p>
        <h3>{{ $product->name }}</h3>
        <p>{{ $product->short_description }}</p>
        <a href="{{ route('products.show', $product->slug) }}">Ürünü inceleyin <span class="sr-only">: {{ $product->name }}</span><span aria-hidden="true">↗</span></a>
    </div>
</article>
