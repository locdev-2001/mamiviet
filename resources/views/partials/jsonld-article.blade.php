@props(['article' => null])
@if (is_array($article) && ! empty($article))
<script type="application/ld+json">
{!! json_encode($article, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}
</script>
@endif
