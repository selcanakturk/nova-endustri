@props(['title' => 'Endüstriye güç veren çözümler', 'description' => 'Nova Endüstri: endüstriyel üretim, mekanik bileşenler ve mühendislik çözümleri.', 'canonical' => null, 'ogImage' => null, 'ogType' => 'website'])
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ $canonical ?? url()->current() }}">
    <meta property="og:title" content="{{ $title }} | Nova Endüstri">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical ?? url()->current() }}">
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:locale" content="tr_TR">
    @if($ogImage)<meta property="og:image" content="{{ $ogImage }}">@endif
    <meta name="theme-color" content="#112536">
    <title>{{ $title }} | Nova Endüstri</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a href="#main" class="skip-link">İçeriğe geç</a>
    <x-navbar />
    <main id="main" tabindex="-1">{{ $slot }}</main>
    <x-footer />
</body>
</html>
