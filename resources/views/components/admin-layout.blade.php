@php $currentCompany = auth()->user()?->currentCompany(); @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin' }} — {{ $currentCompany?->name ?? config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    @fonts
    @vite(['resources/css/app.css'])
</head>
<body class="bg-stone-50 text-stone-900 antialiased">
    <div class="flex min-h-screen flex-col md:flex-row">
        <aside class="shrink-0 border-b border-stone-200 bg-white md:w-60 md:border-b-0 md:border-r">
            <div class="flex items-center gap-3 border-b border-stone-200 px-4 py-4">
                <img src="{{ asset('images/logo-fts.webp') }}" alt="" class="h-8 w-8 rounded">
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-stone-900">{{ $currentCompany?->name }}</p>
                    <p class="text-xs text-stone-500">AI Website admin</p>
                </div>
            </div>
            <nav class="flex gap-1 overflow-x-auto px-2 py-3 text-sm md:block md:space-y-1 md:py-4">
                @php
                    $navItems = [
                        ['route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'label' => 'Dashboard'],
                        ['route' => 'admin.leads.index', 'match' => 'admin.leads.*', 'label' => 'Leads'],
                        ['route' => 'admin.handovers.index', 'match' => 'admin.handovers.*', 'label' => 'Handovers'],
                        ['route' => 'admin.services.index', 'match' => 'admin.services.*', 'label' => 'Services'],
                        ['route' => 'admin.projects.index', 'match' => 'admin.projects.*', 'label' => 'Projects'],
                        ['route' => 'admin.knowledge-items.index', 'match' => 'admin.knowledge-items.*', 'label' => 'Knowledge base'],
                        ['route' => 'admin.company.edit', 'match' => 'admin.company.*', 'label' => 'Company profile'],
                    ];
                @endphp
                @foreach ($navItems as $item)
                    <a href="{{ route($item['route']) }}"
                        class="block whitespace-nowrap rounded-lg px-3 py-2 {{ request()->routeIs($item['match']) ? 'bg-stone-900 text-white' : 'text-stone-600 hover:bg-stone-100' }}">{{ $item['label'] }}</a>
                @endforeach
            </nav>
            <div class="hidden border-t border-stone-200 p-2 md:block">
                <a href="{{ route('home') }}" target="_blank" class="block rounded-lg px-3 py-2 text-sm text-stone-500 hover:bg-stone-100">View website ↗</a>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-sm text-stone-500 hover:bg-stone-100">Log out</button>
                </form>
            </div>
        </aside>

        <main class="min-w-0 flex-1">
            <div class="mx-auto max-w-5xl px-4 py-8 md:px-6">
                @if (session('status'))
                    <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">Please fix the highlighted fields.</div>
                @endif

                <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                    <h1 class="text-xl font-semibold text-stone-900">{{ $title ?? 'Dashboard' }}</h1>
                    {{ $actions ?? '' }}
                </div>

                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>
