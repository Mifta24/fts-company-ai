@props(['category' => 'ai'])

{{-- Line icons in the style of the main company site's service cards. --}}
<span {{ $attributes->merge(['class' => 'category-icon category-'.$category]) }} aria-hidden="true">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        @switch($category)
            @case('saas')
                <rect x="3" y="4" width="18" height="14" rx="2" />
                <path d="M8 21h8M12 18v3M7 9h10M7 13h6" />
                @break
            @case('development')
                <path d="M8 6 3 12l5 6M16 6l5 6-5 6M13.5 4l-3 16" />
                @break
            @case('automation')
                <path d="M12 3v3M12 18v3M4.2 7.5l2.6 1.5M17.2 15l2.6 1.5M4.2 16.5l2.6-1.5M17.2 9l2.6-1.5" />
                <circle cx="12" cy="12" r="3.5" />
                @break
            @default
                <path d="M12 2.5 13.7 7l4.8 1.2-4.8 1.3L12 14l-1.7-4.5L5.5 8.2 10.3 7 12 2.5Z" />
                <path d="M6 15.5 6.9 18l2.6.8-2.6.8L6 22l-.9-2.4-2.6-.8 2.6-.8.9-2.5ZM18 13l.8 2.2 2.2.7-2.2.7-.8 2.2-.8-2.2-2.2-.7 2.2-.7.8-2.2Z" />
        @endswitch
    </svg>
</span>
