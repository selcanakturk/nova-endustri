@props(['post', 'featured' => false])
<article @class(['post-card', 'post-card-featured' => $featured])>
    <a href="{{ $post->public_url }}" class="post-cover" aria-label="{{ $post->title }} yazısını okuyun"><img src="{{ $post->image_url }}" alt="{{ $post->title }} için temsili kapak görseli" width="960" height="600" loading="lazy"><span>{{ $post->type === 'news' ? 'HABER' : 'İÇGÖRÜ' }}</span></a>
    <div class="post-card-body"><div class="post-meta"><span>{{ $post->category->name }}</span><time datetime="{{ $post->published_at->toIso8601String() }}">{{ $post->published_at->timezone(config('nova.editorial_timezone'))->format('d.m.Y') }}</time></div>@if($featured)<p class="eyebrow">ÖNE ÇIKAN İÇERİK</p>@endif<h2><a href="{{ $post->public_url }}">{{ $post->title }}</a></h2><p>{{ $post->excerpt }}</p><a class="inline-link" href="{{ $post->public_url }}">Yazıyı okuyun <span class="sr-only">: {{ $post->title }}</span> ↗</a></div>
</article>
