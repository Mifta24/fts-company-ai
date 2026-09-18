<x-admin-layout title="Dashboard">
    <x-slot name="actions">
        <a href="{{ route('home') }}" target="_blank" class="rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm text-stone-700 hover:bg-stone-50">Open website ↗</a>
    </x-slot>

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        @foreach ([
            ['value' => $stats['conversations_7d'], 'label' => 'Conversations (7 days)', 'alert' => false],
            ['value' => $stats['new_leads'], 'label' => 'New leads', 'alert' => $stats['new_leads'] > 0],
            ['value' => $stats['open_handovers'], 'label' => 'Open handovers', 'alert' => $stats['open_handovers'] > 0],
            ['value' => $stats['knowledge_items'], 'label' => 'Active knowledge items', 'alert' => false],
        ] as $stat)
            <div class="rounded-xl border p-4 {{ $stat['alert'] ? 'border-amber-300 bg-amber-50' : 'border-stone-200 bg-white' }}">
                <p class="text-2xl font-semibold text-stone-900">{{ $stat['value'] }}</p>
                <p class="text-xs text-stone-500">{{ $stat['label'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-stone-200 bg-white">
            <div class="flex items-center justify-between border-b border-stone-200 px-4 py-3">
                <p class="font-medium text-stone-900">Recent leads</p>
                <a href="{{ route('admin.leads.index') }}" class="text-xs text-stone-500 hover:underline">View all</a>
            </div>
            <div class="divide-y divide-stone-100">
                @forelse ($recentLeads as $lead)
                    <a href="{{ route('admin.leads.show', $lead) }}" class="block px-4 py-3 hover:bg-stone-50">
                        <p class="text-sm font-medium text-stone-900">{{ $lead->name }}@if ($lead->organization) · {{ $lead->organization }}@endif</p>
                        <p class="mt-0.5 text-xs text-stone-500">{{ ucfirst($lead->type) }} · {{ $lead->service?->name ?? 'General' }} · {{ $lead->created_at->diffForHumans() }}</p>
                    </a>
                @empty
                    <p class="px-4 py-6 text-center text-sm text-stone-400">No leads yet.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl border border-stone-200 bg-white">
            <div class="flex items-center justify-between border-b border-stone-200 px-4 py-3">
                <p class="font-medium text-stone-900">Open handovers</p>
                <a href="{{ route('admin.handovers.index') }}" class="text-xs text-stone-500 hover:underline">View all</a>
            </div>
            <div class="divide-y divide-stone-100">
                @forelse ($openHandovers as $handover)
                    <a href="{{ route('admin.handovers.show', $handover) }}" class="block px-4 py-3 hover:bg-stone-50">
                        <p class="text-sm font-medium text-stone-900">{{ ucwords(str_replace('_', ' ', $handover->reason)) }}</p>
                        <p class="mt-0.5 line-clamp-1 text-xs text-stone-500">{{ $handover->summary }}</p>
                    </a>
                @empty
                    <p class="px-4 py-6 text-center text-sm text-stone-400">No open handovers.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl border border-stone-200 bg-white lg:col-span-2">
            <div class="border-b border-stone-200 px-4 py-3">
                <p class="font-medium text-stone-900">Latest AI Staff conversations</p>
            </div>
            <div class="divide-y divide-stone-100">
                @forelse ($recentConversations as $conversation)
                    <div class="flex items-center justify-between gap-4 px-4 py-3 text-sm">
                        <p class="text-stone-900">{{ $conversation->visitor_name ?? 'Anonymous visitor' }} <span class="text-xs text-stone-400">· {{ strtoupper($conversation->locale) }} · {{ $conversation->messages_count }} messages</span></p>
                        <p class="text-xs text-stone-500">{{ ucfirst(str_replace('_', ' ', $conversation->status)) }} · {{ $conversation->last_message_at?->diffForHumans() }}</p>
                    </div>
                @empty
                    <p class="px-4 py-6 text-center text-sm text-stone-400">No conversations yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-admin-layout>
