<x-admin.layout title="Yönetici girişi" guest>
    <div class="admin-login-grid">
        <section class="admin-login-brand"><a href="{{ route('home') }}" class="admin-brand"><span class="admin-brand-mark" aria-hidden="true">N↗</span><span>NOVA <small>ENDÜSTRİ</small></span></a><div class="admin-login-message"><p class="admin-eyebrow">NOVA YÖNETİM</p><h1>Güçlü bir üretim.<br>Düzenli bir<br><span>dijital temel.</span></h1><p>Ürünlerinizi ve kategorilerinizi<br>tek bir çalışma alanından yönetin.</p><div class="admin-login-art" aria-hidden="true"><span></span><span></span><span></span></div></div><span class="admin-login-copyright">© {{ date('Y') }} Nova Endüstri</span></section>
        <section class="admin-login-form"><div><span class="admin-login-tag">YÖNETİCİ ERİŞİMİ</span><h2>Tekrar hoş geldiniz.</h2><p>Çalışma alanınıza erişmek için giriş yapın.</p>
            @if(session('status'))<div class="admin-alert admin-alert-success" role="status">{{ session('status') }}</div>@endif
            @if($errors->any())<div class="admin-alert admin-alert-error" role="alert">{{ $errors->first() }}</div>@endif
            <form method="POST" action="{{ route('admin.login.store') }}">@csrf
                <x-admin.field name="email" label="E-posta adresi" type="email" autocomplete="username" placeholder="E-posta adresiniz" maxlength="255" required autofocus />
                <x-admin.field name="password" label="Parola" type="password" autocomplete="current-password" required />
                <button type="submit" class="admin-button admin-button-full">Giriş yap <span aria-hidden="true">→</span></button>
            </form>
            <p class="admin-login-note">Bu alan yalnızca yetkili yöneticiler içindir.</p><a class="admin-link" href="{{ route('home') }}">← Kurumsal siteye dön</a>
        </div></section>
    </div>
</x-admin.layout>
