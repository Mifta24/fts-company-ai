@php
    $staffName = $company->ai_staff_name;
    $tagline = $company->translated('tagline', $locale);
    $description = $company->translated('description', $locale);
    $quickQuestions = [
        ['label' => $copy['q_about'], 'message' => $copy['q_about_msg']],
        ['label' => $copy['q_services'], 'message' => $copy['q_services_msg']],
        ['label' => $copy['q_ai_website'], 'message' => $copy['q_ai_website_msg']],
        ['label' => $copy['q_projects'], 'message' => $copy['q_projects_msg']],
        ['label' => $copy['q_price'], 'message' => $copy['q_price_msg']],
        ['label' => $copy['q_consult'], 'message' => $copy['q_consult_msg']],
    ];
    $localeNames = ['id' => 'Bahasa Indonesia', 'en' => 'English', 'ja' => '日本語'];
    $industryLabel = fn ($industry) => $copy['industry_'.$industry] ?? ucfirst($industry);
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $company->name }} · {{ $tagline }}</title>
    <meta name="description" content="{{ \Illuminate\Support\Str::limit($description, 155) }}">
    <meta property="og:title" content="{{ $company->name }} · {{ $tagline }}">
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit($description, 155) }}">
    <meta name="theme-color" content="#080e1c">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    @foreach (\App\Models\Company::SUPPORTED_LOCALES as $code)
        <link rel="alternate" hreflang="{{ $code }}" href="{{ url('/?lang='.$code) }}">
    @endforeach
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="site ai-studio antialiased" style="--companion-image: url('{{ asset('images/fts-ai-companion.png') }}')">
    <a href="#ai-staff" class="skip-link">{{ $copy['talk_to_staff'] }}</a>

    <header class="site-header">
        <a href="{{ url('/?lang='.$locale) }}" class="brand">
            <img src="{{ asset('images/logo-fts.webp') }}" alt="" width="32" height="32">
            <span class="studio-brand">FTS<span>AI COMPANY</span></span>
        </a>
        <div class="header-actions">
            <nav aria-label="Language" class="lang-switch">
                @foreach (\App\Models\Company::SUPPORTED_LOCALES as $code)
                    <a href="?lang={{ $code }}" lang="{{ $code }}" aria-label="{{ $localeNames[$code] }}" @if ($code === $locale) aria-current="true" @endif>{{ strtoupper($code) }}</a>
                @endforeach
            </nav>
            <button type="button" class="btn btn-ghost header-cta" data-open-results>{{ $copy['results_button'] }}</button>
        </div>
    </header>

    <div class="site-shell">
        <main class="site-main">
            <section class="hero studio-hero" aria-labelledby="hero-title">
                <div class="hero-orb hero-orb-1"></div>
                <div class="hero-orb hero-orb-2"></div>
                <div class="studio-workspace studio-workspace-solo">
                    <div class="hero-copy hero-copy-compact">
                        <p class="hero-badge"><span class="live-dot"></span>{{ $copy['hero_eyebrow'] }}</p>
                        <h1 id="hero-title">{{ $copy['hero_title'] }} <span class="gradient-text">{{ $copy['hero_title_accent'] }}</span></h1>
                        <p class="company-positioning">{{ $copy['company_positioning'] }}</p>
                    </div>
                    @include('site.staff-panel')
                </div>
                <div class="studio-explore">
                    <span>{{ $copy['hero_markets'] }}</span>
                    <button type="button" class="text-link" data-open-results>{{ $copy['results_button'] }} <span aria-hidden="true">↓</span></button>
                    <span class="studio-language-note">ID / EN / JA</span>
                </div>
            </section>

            {{-- Results: only shown on request, per Mr Yoshi's brief — the chat is the product, this is proof --}}
            <section id="results" class="section" aria-labelledby="results-title" data-results hidden>
                <div class="section-head reveal">
                    <button type="button" class="text-link results-back" data-close-results>← {{ $copy['results_close'] }}</button>
                    <p class="eyebrow">{{ $copy['projects_eyebrow'] }}</p>
                    <h2 id="results-title">{{ $copy['projects_title'] }}</h2>
                    <p>{{ $copy['projects_sub'] }}</p>
                </div>
                @if ($industries->count() > 1)
                    <div class="filter-row" role="group" data-project-filters>
                        <button type="button" class="chip chip-small is-active" data-filter="">{{ $copy['filter_all'] }}</button>
                        @foreach ($industries as $industry)
                            <button type="button" class="chip chip-small" data-filter="{{ $industry }}">{{ $industryLabel($industry) }}</button>
                        @endforeach
                    </div>
                @endif
                <div class="project-grid" data-project-grid>
                    @foreach ($projects as $project)
                        @php $projectName = $project->translated('name', $locale); @endphp
                        <article class="card project-card reveal" data-industry="{{ $project->industry }}">
                            <div class="project-media">
                                @if ($project->image_url)
                                    <img src="{{ $project->image_url }}" alt="{{ $projectName }}" loading="lazy">
                                @else
                                    <span class="project-placeholder" aria-hidden="true">{{ mb_substr($projectName, 0, 1) }}</span>
                                @endif
                                <span class="badge status-{{ $project->status }}">{{ $copy['status_'.$project->status] ?? $project->status }}</span>
                            </div>
                            <div class="project-body">
                                <p class="project-meta">{{ $project->industry ? $industryLabel($project->industry) : $project->service?->translated('name', $locale) }}@if ($project->client_name) · {{ $project->client_name }}@endif</p>
                                <h3>{{ $projectName }}</h3>
                                <p>{{ $project->translated('summary', $locale) }}</p>
                                <div class="project-actions">
                                    <button type="button" class="text-link" data-quick-message="{{ __($copy['ask_project_msg'], ['name' => $projectName]) }}" data-close-results>{{ $copy['ask_about_project'] }} →</button>
                                    @if ($project->live_url)
                                        <a href="{{ $project->live_url }}" target="_blank" rel="noopener" class="text-link muted-link">{{ $copy['visit'] }} ↗</a>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
                @if ($projectCount > $projects->count())
                    <p class="projects-more">
                        {{ __($copy['projects_more'], ['count' => $projectCount]) }}
                        <button type="button" class="text-link" data-quick-message="{{ $copy['q_projects_msg'] }}" data-close-results>{{ $copy['ask_aya'] }} →</button>
                    </p>
                @endif
            </section>

            <footer class="site-footer site-footer-minimal">
                <div class="brand">
                    <img src="{{ asset('images/logo-fts.webp') }}" alt="" width="28" height="28">
                    <span>Fujiyama<small>Technology Solutions</small></span>
                </div>
                <p class="footer-tagline">{{ $tagline }}</p>
                <p class="footer-contact">
                    @if ($company->email)<a href="mailto:{{ $company->email }}">{{ $company->email }}</a>@endif
                    @if ($company->phone)<a href="tel:{{ preg_replace('/\s+/', '', $company->phone) }}">{{ $company->phone }}</a>@endif
                </p>
                <p class="footer-note">© {{ now()->year }} Fujiyama Technology Solutions · {{ $copy['footer_note'] }}</p>
            </footer>
        </main>
    </div>

    <button type="button" class="staff-launcher" data-open-staff aria-controls="ai-staff">
        <x-staff-avatar :size="40" />
        <span>{{ $copy['talk_to_staff'] }}</span>
    </button>

    <noscript><p class="noscript">JavaScript is required for the AI Staff. @if ($company->email) {{ $company->email }} @endif</p></noscript>
</body>
</html>
