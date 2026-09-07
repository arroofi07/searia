<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($staticUrls as $url)
    <url>
        <loc>{{ $url }}</loc>
        <changefreq>weekly</changefreq>
    </url>
@endforeach
@foreach ($competitions as $competition)
    <url>
        <loc>{{ route('results.index', $competition) }}</loc>
        <lastmod>{{ ($competition->published_at ?? $competition->updated_at)?->toAtomString() }}</lastmod>
        <changefreq>monthly</changefreq>
    </url>
@endforeach
</urlset>
