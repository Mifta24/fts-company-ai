@props(['size' => 40])

<span {{ $attributes->merge(['class' => 'staff-avatar']) }} style="width: {{ $size }}px; height: {{ $size }}px" aria-hidden="true">
    <img src="{{ asset('images/fts-ai-companion.png') }}" alt="" width="40" height="40" decoding="async" loading="lazy">
</span>
