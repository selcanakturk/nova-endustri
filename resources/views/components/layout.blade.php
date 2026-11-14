@props(['title' => null, 'description' => null, 'canonical' => null, 'ogImage' => null, 'ogType' => 'website', 'ogTitle' => null, 'ogDescription' => null])
@php
    $title = $title ?: $siteSettings['default_meta_title'];
    $description = $description ?: $siteSettings['default_meta_description'];
    $ogImage = $ogImage ?: (filled($siteSettings['social_image']) ? \App\CatalogImage::url($siteSettings['social_image']) : null);
@endphp
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ $canonical ?? url()->current() }}">
    <meta property="og:title" content="{{ $ogTitle ?: $title }} | {{ $siteSettings['company_name'] }}">
    <meta property="og:description" content="{{ $ogDescription ?: $description }}">
    <meta property="og:url" content="{{ $canonical ?? url()->current() }}">
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:locale" content="tr_TR">
    @if($ogImage)<meta property="og:image" content="{{ $ogImage }}">@endif
    <meta name="theme-color" content="#112536">
    <title>{{ $title }} | {{ $siteSettings['company_name'] }}</title>
    <link rel="icon" href="{{ filled($siteSettings['favicon']) ? \App\CatalogImage::url($siteSettings['favicon']) : asset('favicon.svg') }}">
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
