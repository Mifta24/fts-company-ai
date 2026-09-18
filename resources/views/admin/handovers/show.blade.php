<x-admin-layout title="Handover detail">
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_320px]">
        <div class="rounded-xl border border-stone-200 bg-white">
            <div class="border-b border-stone-200 px-4 py-3">
                <p class="font-medium text-stone-900">Conversation</p>
                <p class="text-xs text-stone-500">{{ $handover->conversation->visitor_name ?? 'Visitor' }} · {{ strtoupper($handover->conversation->locale) }}</p>
            </div>
            @include('admin.partials.transcript', ['messages' => $handover->conversation->messages])

            @if ($handover->status === 'open')
                <form method="POST" action="{{ route('admin.handovers.reply', $handover) }}" class="flex items-center gap-2 border-t border-stone-200 p-3">
                    @csrf
                    <input type="text" name="message" required placeholder="Reply to the visitor…" class="flex-1 rounded-lg border border-stone-300 px-3 py-2 text-sm">
                    <button type="submit" class="rounded-lg bg-stone-900 px-4 py-2 text-sm font-medium text-white hover:bg-stone-700">Send</button>
                </form>
            @endif
        </div>

        <div class="space-y-4">
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">{{ ucwords(str_replace('_', ' ', $handover->reason)) }}</p>
                <p class="mt-2 text-sm text-amber-900">{{ $handover->summary }}</p>
            </div>

            @if ($handover->status === 'open')
                <form method="POST" action="{{ route('admin.handovers.resolve', $handover) }}">
                    @csrf
                    <button type="submit" class="w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500">
                        Mark resolved — hand back to AI Staff
                    </button>
                </form>
            @else
                <p class="rounded-lg border border-stone-200 bg-white px-4 py-2 text-center text-sm text-stone-500">Resolved {{ $handover->resolved_at?->diffForHumans() }}</p>
            @endif
        </div>
    </div>
</x-admin-layout>
