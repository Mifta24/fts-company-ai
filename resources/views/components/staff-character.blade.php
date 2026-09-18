@props(['size' => 220, 'state' => 'idle'])

<span {{ $attributes->merge(['class' => 'staff-character anime-character']) }} data-staff-character data-state="{{ $state }}" style="width: {{ $size }}px; height: {{ $size }}px" aria-hidden="true">
    <img src="{{ asset('images/fts-ai-companion.png') }}" alt="" width="1086" height="1448" decoding="async">
</span>
