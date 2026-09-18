<x-admin-layout :title="'Lead '.$lead->reference()">
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[340px_1fr]">
        <div class="space-y-4">
            <x-admin.panel>
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-lg font-semibold text-stone-900">{{ $lead->name }}</p>
                        @if ($lead->organization)<p class="text-sm text-stone-500">{{ $lead->organization }}</p>@endif
                    </div>
                    @include('admin.leads.status-badge', ['status' => $lead->status])
                </div>
                <dl class="mt-4 space-y-2 text-sm">
                    @foreach ([
                        'Request' => ucfirst($lead->type),
                        'Service' => $lead->service?->name,
                        'Email' => $lead->email,
                        'Phone / WA' => $lead->phone,
                        'Business' => $lead->business_type,
                        'Country' => $lead->country,
                        'Prefers' => $lead->preferred_contact,
                        'When' => $lead->preferred_time,
                        'Budget' => $lead->budget_range,
                        'Received' => $lead->created_at->format('d M Y H:i'),
                    ] as $label => $value)
                        @if (filled($value))
                            <div class="flex gap-3"><dt class="w-24 shrink-0 text-stone-400">{{ $label }}</dt><dd class="text-stone-900">{{ $value }}</dd></div>
                        @endif
                    @endforeach
                </dl>
            </x-admin.panel>

            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">Needs (written by the AI Staff)</p>
                <p class="mt-2 whitespace-pre-line text-sm text-amber-900">{{ $lead->needs_summary }}</p>
            </div>

            <form method="POST" action="{{ route('admin.leads.status', $lead) }}" class="flex gap-2">
                @csrf @method('PATCH')
                <select name="status" class="flex-1 rounded-lg border border-stone-300 px-3 py-2 text-sm">
                    @foreach (\App\Models\Lead::STATUSES as $option)
                        <option value="{{ $option }}" @selected($lead->status === $option)>{{ ucfirst($option) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="rounded-lg bg-stone-900 px-4 py-2 text-sm font-medium text-white hover:bg-stone-700">Update</button>
            </form>

            @if ($lead->phone)
                <a href="https://wa.me/{{ preg_replace('/\D/', '', $lead->phone) }}" target="_blank" rel="noopener" class="block rounded-lg bg-emerald-600 px-4 py-2 text-center text-sm font-medium text-white hover:bg-emerald-500">Open WhatsApp chat ↗</a>
            @endif
        </div>

        <div class="rounded-xl border border-stone-200 bg-white">
            <div class="border-b border-stone-200 px-4 py-3">
                <p class="font-medium text-stone-900">Conversation with the AI Staff</p>
            </div>
            @if ($lead->conversation)
                @include('admin.partials.transcript', ['messages' => $lead->conversation->messages])
            @else
                <p class="px-4 py-8 text-center text-sm text-stone-400">No conversation linked.</p>
            @endif
        </div>
    </div>
</x-admin-layout>
