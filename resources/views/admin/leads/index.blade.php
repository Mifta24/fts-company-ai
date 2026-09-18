<x-admin-layout title="Leads">
    <div class="mb-4 flex flex-wrap gap-2 text-sm">
        @foreach (['' => 'All'] + array_combine(\App\Models\Lead::STATUSES, array_map('ucfirst', \App\Models\Lead::STATUSES)) as $value => $label)
            <a href="{{ route('admin.leads.index', $value ? ['status' => $value] : []) }}"
                class="rounded-full px-3 py-1 {{ ($status ?? '') === $value ? 'bg-stone-900 text-white' : 'border border-stone-300 bg-white text-stone-600' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="overflow-x-auto rounded-xl border border-stone-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-stone-200 bg-stone-50 text-xs uppercase text-stone-500">
                <tr>
                    <th class="px-4 py-3">Ref</th>
                    <th class="px-4 py-3">Prospect</th>
                    <th class="px-4 py-3">Request</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Received</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($leads as $lead)
                    <tr class="cursor-pointer hover:bg-stone-50" onclick="window.location='{{ route('admin.leads.show', $lead) }}'">
                        <td class="px-4 py-3"><a href="{{ route('admin.leads.show', $lead) }}" class="font-mono text-xs text-stone-600 hover:underline">{{ $lead->reference() }}</a></td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-stone-900">{{ $lead->name }}</p>
                            <p class="text-xs text-stone-500">{{ collect([$lead->organization, $lead->email ?? $lead->phone])->filter()->implode(' · ') }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p>{{ ucfirst($lead->type) }}</p>
                            <p class="text-xs text-stone-500">{{ $lead->service?->name ?? 'General' }}</p>
                        </td>
                        <td class="px-4 py-3">@include('admin.leads.status-badge', ['status' => $lead->status])</td>
                        <td class="px-4 py-3 text-xs text-stone-500">{{ $lead->created_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-stone-400">No leads found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $leads->links() }}</div>
</x-admin-layout>
