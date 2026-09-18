<x-admin-layout title="Projects">
    <x-slot name="actions">
        <a href="{{ route('admin.projects.create') }}" class="rounded-lg bg-stone-900 px-3 py-2 text-sm font-medium text-white hover:bg-stone-700">Add project</a>
    </x-slot>

    <p class="mb-4 text-sm text-stone-500">The portfolio the AI Staff shows when visitors ask for examples.</p>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($projects as $project)
            <article class="overflow-hidden rounded-xl border border-stone-200 bg-white">
                <div class="aspect-[16/10] bg-stone-100">
                    @if ($project->image_url)
                        <img src="{{ $project->image_url }}" alt="" class="h-full w-full object-cover object-top">
                    @endif
                </div>
                <div class="p-4">
                    <p class="text-xs text-stone-400">{{ $project->service?->name ?? 'No service' }} · {{ str_replace('_', ' ', $project->status) }}</p>
                    <p class="mt-1 font-medium text-stone-900">{{ $project->name }}</p>
                    <p class="mt-1 line-clamp-2 text-sm text-stone-500">{{ $project->summary }}</p>
                    <div class="mt-3 flex items-center justify-between text-sm">
                        <span class="rounded-full px-2 py-0.5 text-xs {{ $project->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-stone-100 text-stone-500' }}">{{ $project->is_active ? 'Active' : 'Hidden' }}</span>
                        <span>
                            <a href="{{ route('admin.projects.edit', $project) }}" class="text-stone-600 hover:underline">Edit</a>
                            <form method="POST" action="{{ route('admin.projects.destroy', $project) }}" class="inline" onsubmit="return confirm('Delete this project?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="ml-3 text-red-600 hover:underline">Delete</button>
                            </form>
                        </span>
                    </div>
                </div>
            </article>
        @empty
            <p class="rounded-xl border border-stone-200 bg-white px-4 py-8 text-center text-stone-400 sm:col-span-3">No projects yet.</p>
        @endforelse
    </div>
</x-admin-layout>
