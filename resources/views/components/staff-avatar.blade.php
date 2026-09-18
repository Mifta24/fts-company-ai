@props(['size' => 40])

{{-- An illustrated mark, not a photo — the AI Staff should always read as AI,
     never as a specific real person. --}}
<span {{ $attributes->merge(['class' => 'staff-avatar']) }} style="width: {{ $size }}px; height: {{ $size }}px" aria-hidden="true">
    <svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <linearGradient id="staff-avatar-bg" x1="0" y1="1" x2="0" y2="0">
                <stop offset="0" stop-color="#0b3f8f" />
                <stop offset="1" stop-color="#1ca0f2" />
            </linearGradient>
        </defs>
        <rect width="48" height="48" rx="24" fill="url(#staff-avatar-bg)" />
        <path d="M12 23a12 12 0 0 1 24 0" fill="none" stroke="#fff" stroke-width="2.4" stroke-linecap="round" opacity=".9" />
        <rect x="9.5" y="21" width="5" height="9" rx="2.5" fill="#fff" />
        <rect x="33.5" y="21" width="5" height="9" rx="2.5" fill="#fff" />
        <rect x="15" y="16" width="18" height="19" rx="8" fill="#fff" />
        <circle class="staff-avatar-eye" cx="20.5" cy="24.5" r="1.9" fill="#101010" />
        <circle class="staff-avatar-eye" cx="27.5" cy="24.5" r="1.9" fill="#101010" />
        <path d="M21 29.3q3 2.2 6 0" fill="none" stroke="#1ca0f2" stroke-width="1.8" stroke-linecap="round" />
        <path d="M36 28v3a5 5 0 0 1-5 5h-3" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" />
        <circle cx="27" cy="36" r="1.8" fill="#fff" />
    </svg>
</span>
