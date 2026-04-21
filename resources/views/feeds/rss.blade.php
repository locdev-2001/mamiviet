@php
    $cdata = static fn (string $s): string => '<![CDATA[' . str_replace(']]>', ']]]]><![CDATA[>', $s) . ']]>';
@endphp
{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/">
<channel>
    <title>{!! $cdata($channel_title) !!}</title>
    <link>{{ $channel_link }}</link>
    <description>{!! $cdata($channel_description !== '' ? $channel_description : $company_name) !!}</description>
    <language>{{ $locale === 'en' ? 'en-US' : 'de-DE' }}</language>
    <atom:link href="{{ $feed_self }}" rel="self" type="application/rss+xml" />
    <lastBuildDate>{{ now()->toRfc2822String() }}</lastBuildDate>
    <generator>{{ $company_name }}</generator>
@foreach ($items as $item)
    <item>
        <title>{!! $cdata((string) $item['title']) !!}</title>
        <link>{{ $item['url'] }}</link>
        <guid isPermaLink="true">{{ $item['url'] }}</guid>
        <pubDate>{{ $item['published_at_rfc2822'] ?? now()->toRfc2822String() }}</pubDate>
        <author>{!! $cdata((string) $item['author_name']) !!}</author>
@if (! empty($item['excerpt']))
        <description>{!! $cdata((string) $item['excerpt']) !!}</description>
@endif
@if (! empty($item['cover']['hero']) && ! empty($item['cover']['mime_type']))
        <enclosure url="{{ $item['cover']['hero'] }}" type="{{ $item['cover']['mime_type'] }}"@if (! empty($item['cover']['size'])) length="{{ $item['cover']['size'] }}"@endif />
@endif
    </item>
@endforeach
</channel>
</rss>
