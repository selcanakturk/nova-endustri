@props(['product'])
<article {{ $attributes->class(['product-card']) }} data-category="{{ $product['category'] }}">
    <div class="product-image"><img src="{{ asset('images/'.$product['image']) }}" alt="{{ $product['name'] }} için temsili teknik çizim" width="640" height="440" loading="lazy"><span>TEKNİK İLLÜSTRASYON</span></div>
    <div class="product-body"><p class="eyebrow">{{ $product['category'] }}</p><h3>{{ $product['name'] }}</h3><p>{{ $product['description'] }}</p><a href="{{ route('contact') }}">Bu ürün grubu için görüşelim <span aria-hidden="true">↗</span></a></div>
</article>
