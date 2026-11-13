@props(['title', 'guest' => false])
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#112536">
    <title>{{ $title }} | Nova Yönetim</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @fonts
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body class="{{ $guest ? 'admin-guest' : 'admin-body' }}">
<a class="admin-skip" href="#admin-main">İçeriğe geç</a>
@if(!$guest)
    <aside class="admin-sidebar" id="admin-sidebar" aria-label="Yönetim navigasyonu">
        <a class="admin-brand" href="{{ route('admin.dashboard') }}"><span class="admin-brand-mark" aria-hidden="true">N↗</span><span>NOVA <small>YÖNETİM PANELİ</small></span></a>
        <span class="admin-nav-heading">ÇALIŞMA ALANI</span>
        <nav aria-label="Yönetim menüsü">
            <a href="{{ route('admin.dashboard') }}" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif><span aria-hidden="true">▦</span> Genel bakış</a>
            <a href="{{ route('admin.products.index') }}" @if(request()->routeIs('admin.products.*')) aria-current="page" @endif><span aria-hidden="true">◇</span> Ürünler</a>
            <a href="{{ route('admin.categories.index') }}" @if(request()->routeIs('admin.categories.*')) aria-current="page" @endif><span aria-hidden="true">▤</span> Kategoriler</a>
            <a href="{{ route('admin.posts.index') }}" @if(request()->routeIs('admin.posts.*')) aria-current="page" @endif><span aria-hidden="true">▧</span> İçerikler</a>
            <a href="{{ route('admin.post-categories.index') }}" @if(request()->routeIs('admin.post-categories.*')) aria-current="page" @endif><span aria-hidden="true">≡</span> Blog kategorileri</a>
        </nav>
        <div class="admin-sidebar-bottom"><span class="admin-nav-heading">NOVA ENDÜSTRİ</span><p>İçeriğinizi yönetin.<br>Üretiminizi görünür kılın.</p><a href="{{ route('home') }}">Kurumsal siteyi aç <span aria-hidden="true">↗</span></a></div>
    </aside>
    <div class="admin-workspace">
        <header class="admin-topbar">
            <button type="button" class="admin-menu-button" aria-controls="admin-sidebar" aria-expanded="false" hidden>Menü <span aria-hidden="true">☰</span></button>
            <span class="admin-topbar-label">NOVA ENDÜSTRİ <span>/</span> Yönetim</span>
            <div class="admin-user"><span class="admin-avatar" aria-hidden="true">{{ mb_substr(auth()->user()->name, 0, 1) }}</span><span><strong>{{ auth()->user()->name }}</strong><small>Yönetici</small></span><form method="POST" action="{{ route('admin.logout') }}">@csrf<button type="submit" class="admin-logout">Çıkış <span aria-hidden="true">↗</span></button></form></div>
        </header>
        <main class="admin-main" id="admin-main" tabindex="-1">
            <div class="admin-page-heading"><p class="admin-eyebrow">İÇERİK YÖNETİMİ</p><h1>{{ $title }}</h1></div>
            @if(session('status'))<div class="admin-alert admin-alert-success" role="status"><span aria-hidden="true">✓</span> {{ session('status') }}</div>@endif
            @if(session('error'))<div class="admin-alert admin-alert-error" role="alert">{{ session('error') }}</div>@endif
            @if($errors->any())<div class="admin-alert admin-alert-error" role="alert"><strong>Bilgileri kontrol edin.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            {{ $slot }}
        </main>
        <footer class="admin-footer"><span>© {{ date('Y') }} Nova Endüstri</span><span>Yönetim çalışma alanı</span></footer>
    </div>
@else
    <main id="admin-main" tabindex="-1">{{ $slot }}</main>
@endif
</body>
</html>
