@php echo '<?xml version="1.0" encoding="UTF-8"?>'; @endphp
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach($pages as $page)<url><loc>{{ route($page) }}</loc></url>@endforeach
@foreach($products as $product)<url><loc>{{ route('products.show', $product->slug) }}</loc><lastmod>{{ $product->updated_at->toAtomString() }}</lastmod></url>@endforeach
@foreach($posts as $post)<url><loc>{{ $post->public_url }}</loc><lastmod>{{ $post->updated_at->toAtomString() }}</lastmod></url>@endforeach
</urlset>
