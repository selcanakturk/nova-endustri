@props(['service'])
<article class="service-card"><span class="service-number">{{ $service['number'] }} <span aria-hidden="true">↗</span></span><h3>{{ $service['title'] }}</h3><p>{{ $service['description'] }}</p><p class="service-detail">{{ $service['detail'] }}</p></article>
