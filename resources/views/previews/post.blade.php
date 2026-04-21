<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>[Preview] {{ $title }}</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; max-width: 760px; margin: 2rem auto; padding: 0 1.5rem; color: #1f2937; line-height: 1.7; }
        .preview-banner { background: #fef3c7; border-left: 4px solid #f59e0b; padding: .75rem 1rem; margin-bottom: 2rem; color: #92400e; font-size: .875rem; }
        .preview-banner strong { font-weight: 600; }
        h1 { font-size: 2.25rem; line-height: 1.2; margin: 0 0 1rem; }
        .meta { color: #6b7280; font-size: .875rem; margin-bottom: 2rem; }
        .content { font-size: 1.0625rem; }
        .content h2 { font-size: 1.5rem; margin-top: 2.5rem; }
        .content h3 { font-size: 1.25rem; margin-top: 2rem; }
        .content img { max-width: 100%; height: auto; border-radius: 8px; margin: 1.5rem 0; }
        .content iframe { max-width: 100%; aspect-ratio: 16/9; border: 0; border-radius: 8px; margin: 1.5rem 0; }
        .content blockquote { border-left: 3px solid #d1d5db; padding-left: 1rem; color: #4b5563; font-style: italic; }
        .content table { width: 100%; border-collapse: collapse; margin: 1.5rem 0; }
        .content th, .content td { border: 1px solid #e5e7eb; padding: .5rem .75rem; text-align: left; }
        .content code { background: #f3f4f6; padding: .125rem .375rem; border-radius: 4px; font-size: .875em; }
        .content pre { background: #1f2937; color: #f3f4f6; padding: 1rem; border-radius: 8px; overflow-x: auto; }
        .content pre code { background: transparent; color: inherit; padding: 0; }
    </style>
</head>
<body>
    <div class="preview-banner">
        <strong>Draft preview</strong> — status: <code>{{ $post->status }}</code>. This page is not indexed by search engines. Signed URL expires in 1 hour.
    </div>
    <h1>{{ $title }}</h1>
    <p class="meta">Locale: {{ $locale }} &middot; Reading time: {{ $post->reading_time }} min</p>
    <div class="content">
        {!! $content !!}
    </div>
</body>
</html>
