<footer class="site-footer">
    <div class="container footer-grid">
        <div><a href="{{ route('home') }}" class="footer-brand">@if($siteSettings['company_name'] === 'Nova Endüstri')NOVA<span> ENDÜSTRİ</span>@else{{ $siteSettings['company_name'] }}@endif</a><p>{{ $siteSettings['footer_description'] }}</p></div>
        <div><h2>Keşfedin</h2><a href="{{ route('about') }}">Hakkımızda</a><a href="{{ route('products') }}">Ürünler</a><a href="{{ route('services') }}">Hizmetler</a><a href="{{ route('blog.index') }}">Blog ve İçgörüler</a><a href="{{ route('news.index') }}">Haberler</a></div>
        <div><h2>Birlikte geliştirelim</h2><p>Bir parçadan bir sisteme,<br>projenizin bir sonraki adımı.</p><a href="{{ route('contact') }}">İletişime geçin ↗</a></div>
    </div>
    <div class="container footer-contact"><p>{{ $siteSettings['short_description'] }}</p>@foreach(['phone' => 'Telefon', 'email' => 'E-posta', 'address' => 'Adres', 'working_hours' => 'Çalışma saatleri'] as $key => $label)@if(filled($siteSettings[$key]))<p><strong>{{ $label }}:</strong> {{ $siteSettings[$key] }}</p>@endif @endforeach @foreach(['maps_url' => 'Haritada göster', 'linkedin' => 'LinkedIn', 'instagram' => 'Instagram', 'facebook' => 'Facebook', 'youtube' => 'YouTube'] as $key => $label)@if(filled($siteSettings[$key]))<a href="{{ $siteSettings[$key] }}" rel="noopener noreferrer">{{ $label }} ↗</a>@endif @endforeach</div>
    <div class="container footer-bottom"><span>© {{ date('Y') }} {{ $siteSettings['company_name'] }}</span><span>Kurgusal firma · Bu web sitesi portföy amaçlı geliştirilmiş bir demo projedir. Firma ve içerikler temsilidir.</span><a href="#main">Yukarı dön ↑</a></div>
</footer>
