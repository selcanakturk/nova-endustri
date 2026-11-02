<header class="site-header">
    <div class="container header-inner">
        <a href="{{ route('home') }}" class="wordmark" aria-label="Nova Endüstri ana sayfa"><span class="brand-symbol" aria-hidden="true">N<span>↗</span></span><span>NOVA<span class="brand-sub">ENDÜSTRİ</span></span></a>
        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="navigation" hidden>Menü <span aria-hidden="true">☰</span></button>
        <nav id="navigation" aria-label="Ana menü">
            @foreach (['home' => 'Ana Sayfa', 'about' => 'Hakkımızda', 'products' => 'Ürünler', 'services' => 'Hizmetler', 'contact' => 'İletişim'] as $name => $label)
                <a href="{{ route($name) }}" @if(request()->routeIs($name)) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
            <x-button :href="route('contact')" class="nav-cta">Projenizi konuşalım <span aria-hidden="true">↗</span></x-button>
        </nav>
    </div>
</header>
