<x-layout robots="noindex, nofollow" :title="$product->name.' — Teklif talebi'" description="Ürün ve miktar bilgilerinizi paylaşarak Nova Endüstri’ye teklif talebinizi iletin.">
    <x-page-heading eyebrow="Teklif Talebi" title="İhtiyacınızı birlikte netleştirelim." description="Ürün ve miktar bilgilerinizi paylaşın; talebinizi değerlendirmek için ilk adımı atalım." />
    <section class="section"><div class="container contact-grid"><div><a class="inline-link" href="{{ route('products.show', $product->slug) }}">← Ürün detayına dön</a><figure class="inquiry-product"><img src="{{ $product->image_url }}" alt="{{ $product->name }} için temsili görsel" width="640" height="440"><figcaption><p class="eyebrow">{{ $product->category->name }}</p><h2>{{ $product->name }}</h2><p>{{ $product->short_description }}</p></figcaption></figure><p class="catalog-note">Bu form bir talep kaydı oluşturur. Fiyat veya satın alma taahhüdü içermez.</p></div>
        <div class="contact-form"><h2>Teklif isteyin</h2><x-inquiry-feedback />
            <form method="POST" action="{{ route('quotes.store', $product->slug) }}" aria-describedby="inquiry-privacy">@csrf
                <label for="product-name">İlgili ürün</label><input id="product-name" value="{{ $product->name }}" readonly>
                <div class="grid gap-5 sm:grid-cols-2"><x-inquiry-field name="name" label="Ad soyad" autocomplete="name" maxlength="150" required /><x-inquiry-field name="company" label="Firma (isteğe bağlı)" autocomplete="organization" maxlength="255" /><x-inquiry-field name="email" label="E-posta" type="email" autocomplete="email" maxlength="255" required /><x-inquiry-field name="phone" label="Telefon" type="tel" autocomplete="tel" maxlength="40" required /><x-inquiry-field name="quantity" label="Miktar" type="number" min="0.001" max="999999999.999" step="0.001" required /><x-inquiry-field name="unit" label="Birim (isteğe bağlı)" placeholder="Örn. adet, kg, metre" maxlength="30" /></div>
                <x-inquiry-field name="message" label="Açıklama (isteğe bağlı)" type="textarea" rows="5" maxlength="10000" hint="Uygulama alanı, malzeme veya teslim beklentinizi paylaşabilirsiniz." />
                <x-inquiry-notice /><button type="submit" class="button">Teklif talebini gönder <span aria-hidden="true">↗</span></button>
            </form>
        </div></div></section>
</x-layout>
