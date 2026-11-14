<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#112536">
    <title>@yield('title') | Nova Endüstri</title>
    <style>
        * { box-sizing: border-box; } body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; background: #f3f5f6; color: #112536; font: 16px/1.7 system-ui, sans-serif; }
        main { width: 100%; max-width: 660px; padding: clamp(28px, 6vw, 64px); background: white; border: 1px solid #d7e0e5; border-top: 5px solid #b94f23; border-radius: 8px; overflow-wrap: anywhere; }
        .brand { color: #425e70; font-size: 12px; font-weight: 700; letter-spacing: .15em; } .code { font-size: 14px; color: #a14622; margin-top: 36px; font-weight: 700; }
        h1 { font-size: clamp(26px, 5vw, 40px); line-height: 1.15; margin-block: 18px; } p { color: #526776; } nav { display: flex; flex-wrap: wrap; gap: 16px; margin-top: 30px; }
        a { color: #173e59; text-underline-offset: 4px; } .button { padding: 12px 20px; background: #112536; color: white; border-radius: 5px; text-decoration: none; } nav a { align-content: center; }
        a:focus-visible { outline: 3px solid #a94820; outline-offset: 5px; }
    </style>
</head>
<body>
    <main id="main"><span class="brand">NOVA ENDÜSTRİ</span><div class="code">@yield('code')</div><h1>@yield('title')</h1><p>@yield('description')</p><nav aria-label="Devam bağlantıları"><a class="button" href="{{ route('home') }}">Ana sayfaya dön</a>@if(request()->is('admin', 'admin/*'))<a href="{{ route('admin.login') }}">Yönetim girişini aç</a>@else<a href="{{ route('contact') }}">İletişim sayfasını aç</a>@endif</nav></main>
</body>
</html>
