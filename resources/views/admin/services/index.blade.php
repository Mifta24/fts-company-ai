<x-admin-layout title="Services">
    <x-slot name="actions">
        <a href="{{ route('admin.services.create') }}" class="rounded-lg bg-stone-900 px-3 py-2 text-sm font-medium text-white hover:bg-stone-700">Add service</a>
    </x-slot>

    <p class="mb-4 text-sm text-stone-500">What the AI Staff can recommend. Prices are only ever quoted from here — a "quotation" service never gets a number from the AI.</p>

    <div class="overflow-x-auto rounded-xl border border-stone-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-stone-200 bg-stone-50 text-xs uppercase text-stone-500">
                <tr>
                    <th class="px-4 py-3">Service</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3">Pricing</th>
                    <th class="px-4 py-3">Projects</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($services as $service)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-medium text-stone-900">{{ $service->name }} @if ($service->is_featured)<span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] text-amber-700">Featured</span>@endif</p>
                            <p class="text-xs text-stone-400">{{ $service->slug }}</p>
                        </td>
                        <td class="px-4 py-3 text-stone-500">{{ ucfirst($service->category) }}</td>
                        <td class="px-4 py-3 text-stone-600">
                            @if ($service->hasPublishedPrice())
                                {{ $company->currency }} {{ number_format((float) $service->starting_price, 0, ',', '.') }} <span class="text-xs text-stone-400">{{ $service->price_unit }}</span>
                            @else
                                <span class="text-stone-400">Quotation</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-stone-500">{{ $service->projects_count }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs {{ $service->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-stone-100 text-stone-500' }}">{{ $service->is_active ? 'Active' : 'Hidden' }}</span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <a href="{{ route('admin.services.edit', $service) }}" class="text-stone-600 hover:underline">Edit</a>
                            <form method="POST" action="{{ route('admin.services.destroy', $service) }}" class="inline" onsubmit="return confirm('Delete this service?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="ml-3 text-red-600 hover:underline">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-stone-400">No services yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin-layout>
