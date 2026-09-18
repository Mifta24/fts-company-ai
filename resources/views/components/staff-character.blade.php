@props(['size' => 220, 'state' => 'idle'])

{{-- Animated AI Staff mascot. The state (idle / listening / thinking /
     talking / happy / handover) is driven from resources/js/ai-staff.js via
     data-state; every animation lives in CSS so the SVG stays one file.
     Deliberately a robot-like character, never a photo — the AI must read as AI. --}}
<span {{ $attributes->merge(['class' => 'staff-character']) }} data-staff-character data-state="{{ $state }}" style="width: {{ $size }}px; height: {{ $size }}px" aria-hidden="true">
    <svg viewBox="0 0 200 220" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <linearGradient id="ch-body" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stop-color="#1ca0f2" />
                <stop offset="1" stop-color="#0b3f8f" />
            </linearGradient>
            <linearGradient id="ch-band" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0" stop-color="#1e3a8a" />
                <stop offset="1" stop-color="#0f2a63" />
            </linearGradient>
        </defs>

        <ellipse class="ch-shadow" cx="100" cy="208" rx="46" ry="7" fill="#020817" opacity=".12" />

        <g class="ch-body-group">
            {{-- arms sit behind the body --}}
            <g class="ch-arm ch-arm-left">
                <path d="M62 138c-14 8-22 26-20 44" fill="none" stroke="url(#ch-body)" stroke-width="16" stroke-linecap="round" />
                <circle cx="42" cy="184" r="10" fill="#1ca0f2" />
            </g>
            <g class="ch-arm ch-arm-right">
                <path d="M138 138c14 8 22 26 20 44" fill="none" stroke="url(#ch-body)" stroke-width="16" stroke-linecap="round" />
                <circle class="ch-hand-right" cx="158" cy="184" r="10" fill="#1ca0f2" />
            </g>

            <rect x="58" y="122" width="84" height="82" rx="30" fill="url(#ch-body)" />
            <path d="M84 124h32l-16 22z" fill="#ffffff" opacity=".92" />
            <path d="M100 146l6 10-6 22-6-22z" fill="#d61f2c" />
            <circle cx="100" cy="184" r="5" fill="#ffffff" opacity=".35" />

            <g class="ch-head">
                <ellipse cx="100" cy="72" rx="52" ry="50" fill="url(#ch-band)" />
                <ellipse cx="100" cy="76" rx="42" ry="38" fill="#ffffff" />

                {{-- headset --}}
                <path d="M48 66c4-30 24-46 52-46s48 16 52 46" fill="none" stroke="#0f2a63" stroke-width="7" stroke-linecap="round" />
                <rect class="ch-ear" x="38" y="60" width="16" height="30" rx="7" fill="#1ca0f2" />
                <rect class="ch-ear" x="146" y="60" width="16" height="30" rx="7" fill="#1ca0f2" />
                <path d="M150 92c0 14-10 24-24 24h-8" fill="none" stroke="#0f2a63" stroke-width="4" stroke-linecap="round" />
                <circle cx="116" cy="116" r="4.5" fill="#1ca0f2" />

                {{-- face --}}
                <g class="ch-eyes">
                    <circle class="ch-eye" cx="84" cy="76" r="6" fill="#020817" />
                    <circle class="ch-eye" cx="116" cy="76" r="6" fill="#020817" />
                    <circle cx="86.5" cy="73.5" r="2" fill="#ffffff" />
                    <circle cx="118.5" cy="73.5" r="2" fill="#ffffff" />
                </g>
                <ellipse cx="74" cy="90" rx="6" ry="3.5" fill="#fca5a5" opacity=".7" />
                <ellipse cx="126" cy="90" rx="6" ry="3.5" fill="#fca5a5" opacity=".7" />
                <path class="ch-mouth" d="M90 94q10 8 20 0" fill="none" stroke="#0f2a63" stroke-width="3.5" stroke-linecap="round" />
                <ellipse class="ch-mouth-open" cx="100" cy="96" rx="7" ry="5" fill="#0f2a63" />
                <path class="ch-mouth-happy" d="M86 92q14 16 28 0z" fill="#0f2a63" />
            </g>
        </g>

        {{-- state props --}}
        <g class="ch-prop ch-dots">
            <circle cx="150" cy="34" r="4" fill="#94a3b8" />
            <circle cx="162" cy="22" r="6" fill="#94a3b8" />
            <ellipse cx="180" cy="8" rx="14" ry="9" fill="#e2e8f0" />
            <circle cx="174" cy="8" r="1.8" fill="#64748b" /><circle cx="180" cy="8" r="1.8" fill="#64748b" /><circle cx="186" cy="8" r="1.8" fill="#64748b" />
        </g>
        <g class="ch-prop ch-check">
            <circle cx="164" cy="30" r="18" fill="#22c55e" />
            <path d="M154 30l7 7 13-14" fill="none" stroke="#ffffff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
        </g>
        <g class="ch-prop ch-phone">
            <circle cx="164" cy="30" r="18" fill="#1ca0f2" />
            <path d="M156 22c2-2 5-1 6 2l1 4-3 3c2 4 5 7 9 9l3-3 4 1c3 1 4 4 2 6l-3 3c-9 1-24-14-23-23z" fill="#ffffff" />
        </g>
        <g class="ch-prop ch-sparks">
            <path d="M158 40l3 7 7 3-7 3-3 7-3-7-7-3 7-3z" fill="#fbbf24" />
            <path d="M40 46l2 5 5 2-5 2-2 5-2-5-5-2 5-2z" fill="#38bdf8" />
        </g>
        <g class="ch-prop ch-ripple">
            <circle cx="46" cy="75" r="20" fill="none" stroke="#1ca0f2" stroke-width="2" />
            <circle cx="154" cy="75" r="20" fill="none" stroke="#1ca0f2" stroke-width="2" />
        </g>
    </svg>
</span>
