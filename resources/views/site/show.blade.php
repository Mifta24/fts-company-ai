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
    $money = fn ($amount) => $company->currency === 'IDR'
        ? 'Rp'.number_format((float) $amount, 0, ',', '.')
        : $company->currency.' '.number_format((float) $amount, 0);
    $localeNames = ['id' => 'Bahasa Indonesia', 'en' => 'English', 'ja' => '日本語'];
    $industryLabel = fn ($industry) => $copy['industry_'.$industry] ?? ucfirst($industry);
    $navItems = [
        'services' => $copy['nav_services'], 'how' => $copy['nav_how'], 'projects' => $copy['nav_projects'],
        'pricing' => $copy['pricing_eyebrow'], 'about' => $copy['nav_about'], 'contact' => $copy['nav_contact'],
    ];
    $stats = [
        [$projectCount, $copy['stat_projects']],
        [$services->count(), $copy['stat_services']],
        [3, $copy['stat_languages']],
        ['24/7', $copy['stat_available']],
    ];
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
        <nav class="site-nav" aria-label="{{ $company->name }}" data-scrollspy>
            @foreach ($navItems as $id => $label)
                <a href="#{{ $id }}" data-nav="{{ $id }}">{{ $label }}</a>
            @endforeach
        </nav>
        <div class="header-actions">
            <nav aria-label="Language" class="lang-switch">
                @foreach (\App\Models\Company::SUPPORTED_LOCALES as $code)
                    <a href="?lang={{ $code }}" lang="{{ $code }}" aria-label="{{ $localeNames[$code] }}" @if ($code === $locale) aria-current="true" @endif>{{ strtoupper($code) }}</a>
                @endforeach
            </nav>
            <button type="button" class="btn btn-primary header-cta" data-open-staff>{{ $copy['talk_to_staff'] }}</button>
        </div>
    </header>

    <div class="site-shell">
        <main class="site-main">
            <section class="hero studio-hero" aria-labelledby="hero-title">
                <div class="hero-orb hero-orb-1"></div>
                <div class="hero-orb hero-orb-2"></div>
                <div class="studio-workspace">
                    <div class="studio-companion">
                        <div class="hero-copy">
                            <p class="hero-badge"><span class="live-dot"></span>{{ $copy['hero_eyebrow'] }}</p>
                            <h1 id="hero-title">{{ $copy['hero_title'] }}<br><span class="gradient-text">{{ $copy['hero_title_accent'] }}</span></h1>
                            <p class="company-positioning">{{ $copy['company_positioning'] }}</p>
                            <p class="hero-intro">{{ __($copy['hero_intro'], ['name' => $staffName]) }}</p>
                        </div>
                        <div class="hero-figure">
                            <div class="companion-orbit" aria-hidden="true"></div>
                            <span class="companion-label">FTS / AI COMPANION</span>
                            <x-staff-character :size="380" class="companion-portrait" />
                            <div class="companion-card">
                                <span class="companion-wave" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></span>
                                <div><strong>{{ $staffName }}</strong><span>{{ $copy['studio_companion'] }}</span></div>
                                <span class="online-dot" aria-hidden="true"></span>
                            </div>
                        </div>
                    </div>
                    @include('site.staff-panel')
                </div>
                <div class="studio-explore">
                    <span>{{ $copy['hero_markets'] }}</span>
                    <a href="#services">{{ $copy['studio_explore'] }} <span aria-hidden="true">↓</span></a>
                    <span class="studio-language-note">ID / EN / JA</span>
                </div>
            </section>
            <dl class="stats-band">
                @foreach ($stats as [$value, $label])
                    <div><dd>{{ $value }}</dd><dt>{{ $label }}</dt></div>
                @endforeach
                <div class="stats-flags"><dd><span aria-hidden="true">🇯🇵 🇮🇩</span> ID · EN · JA</dd><dt>{{ $copy['hero_markets'] }}</dt></div>
            </dl>

            {{-- Services --}}
            <section id="services" class="section" aria-labelledby="services-title">
                <div class="section-head reveal">
                    <p class="eyebrow">{{ $copy['services_eyebrow'] }}</p>
                    <h2 id="services-title">{{ $copy['services_title'] }}</h2>
                    <p>{{ $copy['services_sub'] }}</p>
                </div>
                <div class="service-grid">
                    @foreach ($services as $service)
                        @php $serviceName = $service->translated('name', $locale); @endphp
                        <article @class(['card service-card reveal', 'is-featured' => $service->is_featured])>
                            <div class="service-card-top">
                                <x-service-icon :category="$service->category" />
                                @if ($service->is_featured)
                                    <span class="badge badge-primary">{{ $copy['featured'] }}</span>
                                @endif
                            </div>
                            <h3>{{ $serviceName }}</h3>
                            <p class="service-summary">{{ $service->translated('summary', $locale) }}</p>
                            <ul class="check-list">
                                @foreach (array_slice($service->translatedFeatures($locale), 0, $service->is_featured ? 5 : 3) as $feature)
                                    <li>{{ $feature }}</li>
                                @endforeach
                            </ul>
                            <p class="service-price">
                                @if ($service->hasPublishedPrice())
                                    @if ($copy['from'] !== '')<span class="muted">{{ $copy['from'] }}</span>@endif
                                    <strong>{{ $money($service->starting_price) }}</strong>
                                    <span class="muted">{{ $service->translations[$locale]['price_unit'] ?? $service->price_unit }}</span>
                                @else
                                    <strong>{{ $copy['by_quotation'] }}</strong>
                                @endif
                            </p>
                            <button type="button" class="btn btn-ghost" data-quick-message="{{ __($copy['ask_service_msg'], ['name' => $serviceName]) }}">
                                <x-staff-avatar :size="20" /> {{ $copy['ask_about_service'] }}
                            </button>
                        </article>
                    @endforeach
                </div>
            </section>

            {{-- How the AI Website works — dark band with a live-style demo --}}
            <section id="how" class="section section-dark" aria-labelledby="how-title">
                <div class="how-layout">
                    <div>
                        <div class="section-head section-head-left reveal">
                            <p class="eyebrow">{{ $copy['how_eyebrow'] }}</p>
                            <h2 id="how-title">{{ $copy['how_title'] }}</h2>
                        </div>
                        <ol class="how-grid">
                            @foreach ([1, 2, 3, 4] as $step)
                                <li class="reveal">
                                    <span class="how-number">0{{ $step }}</span>
                                    <h3>{{ $copy["how_{$step}_title"] }}</h3>
                                    <p>{{ $copy["how_{$step}_body"] }}</p>
                                </li>
                            @endforeach
                        </ol>
                        <p class="how-cta">
                            <button type="button" class="btn btn-primary" data-quick-message="{{ $copy['q_ai_website_msg'] }}">{{ $copy['q_ai_website'] }} <span aria-hidden="true">→</span></button>
                        </p>
                    </div>
                    <div class="demo-chat reveal" aria-hidden="true">
                        <div class="demo-chat-head">
                            <x-staff-avatar :size="28" />
                            <span>{{ $staffName }} · {{ $copy['staff_role'] }}</span>
                            <span class="online-dot"></span>
                        </div>
                        <div class="demo-bubble demo-visitor">{{ $copy['demo_visitor'] }}</div>
                        <div class="demo-bubble demo-staff">{{ $copy['demo_staff'] }}</div>
                        <div class="demo-card">
                            <img src="{{ asset('images/projects/hotel-ai-website.png') }}" alt="" loading="lazy">
                            <div>
                                <strong>{{ $copy['demo_card_title'] }}</strong>
                                <span>{{ $copy['demo_card_sub'] }}</span>
                            </div>
                        </div>
                        <div class="demo-typing"><i></i><i></i><i></i></div>
                    </div>
                </div>
            </section>

            {{-- Projects --}}
            <section id="projects" class="section" aria-labelledby="projects-title">
                <div class="section-head reveal">
                    <p class="eyebrow">{{ $copy['projects_eyebrow'] }}</p>
                    <h2 id="projects-title">{{ $copy['projects_title'] }}</h2>
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
                                    <button type="button" class="text-link" data-quick-message="{{ __($copy['ask_project_msg'], ['name' => $projectName]) }}">{{ $copy['ask_about_project'] }} →</button>
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
                        <button type="button" class="text-link" data-quick-message="{{ $copy['q_projects_msg'] }}">{{ $copy['ask_aya'] }} →</button>
                    </p>
                @endif
            </section>

            {{-- Pricing --}}
            @if ($pricedServices->isNotEmpty())
                <section id="pricing" class="section section-tinted section-dots" aria-labelledby="pricing-title">
                    <div class="section-head reveal">
                        <p class="eyebrow">{{ $copy['pricing_eyebrow'] }}</p>
                        <h2 id="pricing-title">{{ $copy['pricing_title'] }}</h2>
                        <p>{{ $copy['pricing_sub'] }}</p>
                    </div>
                    @foreach ($pricedServices as $service)
                        @php
                            $tiers = $service->pricing_tiers;
                            $popularIndex = count($tiers) >= 3 ? 1 : -1;
                            $unit = $service->translations[$locale]['price_unit'] ?? $service->price_unit;
                        @endphp
                        <div class="pricing-block reveal">
                            <div class="pricing-block-head">
                                <h3>{{ $service->translated('name', $locale) }}</h3>
                                <p class="muted">{{ $service->translated('price_note', $locale) }}</p>
                            </div>
                            <div class="tier-cards">
                                @foreach ($tiers as $index => $tier)
                                    <article @class(['card tier-card', 'is-popular' => $index === $popularIndex])>
                                        @if ($index === $popularIndex)<span class="badge badge-primary">{{ $copy['popular'] }}</span>@endif
                                        <p class="tier-card-name">{{ $tier['name'] }}</p>
                                        <p class="tier-card-price"><strong>{{ $money($tier['price']) }}</strong><span class="muted">{{ $tier['unit'] ?? $unit }}</span></p>
                                        <button type="button" class="btn btn-ghost" data-quick-message="{{ __($copy['pricing_ask_msg'], ['name' => $service->translated('name', $locale).' — '.$tier['name']]) }}">{{ $copy['pricing_ask'] }}</button>
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </section>
            @endif

            {{-- About, team, values --}}
            <section id="about" class="section" aria-labelledby="about-title">
                <div class="section-head reveal">
                    <p class="eyebrow">{{ $copy['about_eyebrow'] }}</p>
                    <h2 id="about-title">{{ $tagline }}</h2>
                    <p>{{ $description }}</p>
                </div>

                <div class="about-grid">
                    <div class="about-cards">
                        @foreach ($aboutItems->skip(1) as $item)
                            <article class="card about-card reveal">
                                <h3>{{ $item->translated('title', $locale) }}</h3>
                                <p>{{ $item->translated('body', $locale) }}</p>
                            </article>
                        @endforeach
                    </div>
                    @if ($processItems->isNotEmpty())
                        <div class="reveal">
                            <h3 class="small-heading">{{ $copy['process_title'] }}</h3>
                            <ol class="process-list">
                                @foreach ($processItems as $item)
                                    <li>
                                        <strong>{{ $item->translated('title', $locale) }}</strong>
                                        <span>{{ $item->translated('body', $locale) }}</span>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    @endif
                </div>

                @if ($teamItems->isNotEmpty())
                    <div class="section-head section-head-sub reveal">
                        <p class="eyebrow">{{ $copy['team_eyebrow'] }}</p>
                        <h2>{{ $copy['team_title'] }}</h2>
                    </div>
                    <div class="team-grid">
                        @foreach ($teamItems as $member)
                            @php
                                [$role, $bio] = array_pad(explode("\n", $member->translated('body', $locale), 2), 2, '');
                                $initials = collect(preg_split('/\s+/', $member->title))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('');
                            @endphp
                            <article class="card team-card reveal">
                                <span class="team-avatar" aria-hidden="true">{{ $initials }}</span>
                                <h3>{{ $member->translated('title', $locale) }}</h3>
                                <p class="team-role">{{ $role }}</p>
                                <p class="team-bio">{{ $bio }}</p>
                            </article>
                        @endforeach
                    </div>
                @endif

                @if ($valueItems->isNotEmpty())
                    <div class="values-band reveal">
                        <h3>{{ $copy['values_title'] }}</h3>
                        <ul class="values-grid">
                            @foreach ($valueItems as $index => $value)
                                <li>
                                    <span class="value-icon" aria-hidden="true">{{ ['✦', '◆', '◎', '↗'][$index % 4] }}</span>
                                    <strong>{{ $value->translated('title', $locale) }}</strong>
                                    <span>{{ $value->translated('body', $locale) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </section>

            {{-- FAQ --}}
            @if ($faqItems->isNotEmpty())
                <section class="section section-tinted" aria-labelledby="faq-title">
                    <div class="section-head reveal">
                        <h2 id="faq-title">{{ $copy['faq_title'] }}</h2>
                    </div>
                    <div class="faq-list reveal">
                        @foreach ($faqItems as $item)
                            <details>
                                <summary>{{ $item->translated('title', $locale) }}</summary>
                                <p>{{ $item->translated('body', $locale) }}</p>
                            </details>
                        @endforeach
                    </div>
                    <p class="faq-more muted">{{ $copy['faq_more'] }} <button type="button" class="text-link" data-open-staff>{{ $copy['ask_aya'] }} →</button></p>
                </section>
            @endif

            {{-- Contact CTA --}}
            <section id="contact" class="cta reveal" aria-labelledby="cta-title">
                <div class="hero-orb hero-orb-3"></div>
                <x-staff-character :size="120" class="cta-character" />
                <h2 id="cta-title">{{ $copy['cta_title'] }}</h2>
                <p>{{ __($copy['cta_body'], ['name' => $staffName]) }}</p>
                <div class="hero-actions">
                    <button type="button" class="btn btn-light btn-lg" data-quick-message="{{ $copy['q_consult_msg'] }}">{{ $copy['cta_button'] }} <span aria-hidden="true">→</span></button>
                    @if ($company->whatsappUrl())
                        <a href="{{ $company->whatsappUrl() }}" target="_blank" rel="noopener" class="btn btn-outline btn-lg">WhatsApp</a>
                    @endif
                </div>
                <ul class="cta-links">
                    @if ($company->email)<li><a href="mailto:{{ $company->email }}">{{ $company->email }}</a></li>@endif
                    @if ($company->phone)<li><a href="tel:{{ preg_replace('/\s+/', '', $company->phone) }}">{{ $company->phone }}</a></li>@endif
                    <li>{{ $copy['footer_hours'] }}</li>
                </ul>
            </section>

            <footer class="site-footer">
                <div class="footer-grid">
                    <div class="footer-brand">
                        <div class="brand">
                            <img src="{{ asset('images/logo-fts.webp') }}" alt="" width="36" height="36">
                            <span>Fujiyama<small>Technology Solutions</small></span>
                        </div>
                        <p>{{ $tagline }}</p>
                        <nav aria-label="Language" class="lang-switch">
                            @foreach (\App\Models\Company::SUPPORTED_LOCALES as $code)
                                <a href="?lang={{ $code }}" lang="{{ $code }}" @if ($code === $locale) aria-current="true" @endif>{{ strtoupper($code) }}</a>
                            @endforeach
                        </nav>
                    </div>
                    <div>
                        <h4>{{ $copy['footer_services'] }}</h4>
                        <ul>
                            @foreach ($services as $service)
                                <li><button type="button" data-quick-message="{{ __($copy['ask_service_msg'], ['name' => $service->translated('name', $locale)]) }}">{{ $service->translated('name', $locale) }}</button></li>
                            @endforeach
                        </ul>
                    </div>
                    <div>
                        <h4>{{ $copy['footer_company'] }}</h4>
                        <ul>
                            @foreach ($navItems as $id => $label)
                                <li><a href="#{{ $id }}">{{ $label }}</a></li>
                            @endforeach
                            @if ($company->website_url)<li><a href="{{ $company->website_url }}" target="_blank" rel="noopener">{{ $copy['footer_main_site'] }} ↗</a></li>@endif
                        </ul>
                    </div>
                    <div>
                        <h4>{{ $copy['footer_contact'] }}</h4>
                        <ul>
                            @if ($company->email)<li><a href="mailto:{{ $company->email }}">{{ $company->email }}</a></li>@endif
                            @if ($company->phone)<li>{{ $company->phone }}</li>@endif
                            <li>{{ collect([$company->address, $company->city, $company->country])->filter()->implode(', ') }}</li>
                            <li>{{ $copy['footer_hours'] }}</li>
                        </ul>
                    </div>
                </div>
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
