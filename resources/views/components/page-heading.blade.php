@props(['eyebrow', 'title', 'description'])
<section class="page-heading"><div class="container"><p class="eyebrow">{{ $eyebrow }}</p><h1>{{ $title }}</h1><p class="lead">{{ $description }}</p><div class="breadcrumb"><a href="{{ route('home') }}">Ana Sayfa</a><span aria-hidden="true"> / </span><span>{{ $eyebrow }}</span></div></div></section>
