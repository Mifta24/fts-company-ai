@php $isEdit = $project->exists; @endphp

<x-admin-layout :title="$isEdit ? 'Edit project' : 'Add project'">
    <form method="POST" action="{{ $isEdit ? route('admin.projects.update', $project) : route('admin.projects.store') }}" class="space-y-6">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <x-admin.panel title="Basics">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-admin.input name="name" label="Name (Indonesian)" :value="$project->name" required />
                <x-admin.input name="slug" label="Slug" :value="$project->slug" hint="Leave empty to generate from the name." />
                <x-admin.select name="service_id" label="Service" :value="$project->service_id" :options="['' => '— none —'] + $services->pluck('name', 'id')->all()" />
                <x-admin.select name="status" label="Status" :value="$project->status" :options="['live' => 'Live', 'pilot' => 'Pilot', 'demo' => 'Demo', 'in_development' => 'In development']" />
                <x-admin.input name="client_name" label="Client name (optional)" :value="$project->client_name" />
                <x-admin.input name="industry" label="Industry" :value="$project->industry" placeholder="hospitality, restaurant, internal…" />
                <x-admin.input name="image_url" label="Screenshot / image URL" :value="$project->image_url" hint="e.g. /images/projects/hotel-ai.png" />
                <x-admin.input name="live_url" type="url" label="Live URL (optional)" :value="$project->live_url" />
                <x-admin.input name="tech_stack" label="Tech stack (comma-separated)" :value="implode(', ', $project->tech_stack ?? [])" />
                <x-admin.input name="tags" label="Tags (comma-separated, any language — helps the AI find it)" :value="implode(', ', $project->tags ?? [])" placeholder="japan, jepang, 日本, booking" />
                <x-admin.input name="sort_order" type="number" label="Sort order" :value="$project->sort_order" />
                <div class="flex gap-6 sm:col-span-2">
                    <x-admin.checkbox name="is_active" label="Active (visible and usable by the AI)" :checked="$project->is_active" />
                    <x-admin.checkbox name="is_featured" label="Featured" :checked="$project->is_featured" />
                </div>
            </div>
        </x-admin.panel>

        <x-admin.panel title="Indonesian (default)">
            <div class="space-y-4">
                <x-admin.input name="summary" label="One-line summary" :value="$project->summary" required />
                <x-admin.textarea name="description" label="Description" :value="$project->description" required />
                <x-admin.textarea name="highlights" label="Highlights (one per line)" :value="implode(PHP_EOL, $project->highlights ?? [])" />
            </div>
        </x-admin.panel>

        @foreach (['en' => 'English', 'ja' => 'Japanese'] as $code => $language)
            @php $t = $project->translations[$code] ?? []; @endphp
            <x-admin.panel :title="$language">
                <div class="space-y-4">
                    <x-admin.input name="translations[{{ $code }}][name]" label="Name" :value="$t['name'] ?? ''" />
                    <x-admin.input name="translations[{{ $code }}][summary]" label="Summary" :value="$t['summary'] ?? ''" />
                    <x-admin.textarea name="translations[{{ $code }}][description]" label="Description" :value="$t['description'] ?? ''" />
                    <x-admin.textarea name="translations[{{ $code }}][highlights]" label="Highlights (one per line)" :value="implode(PHP_EOL, $t['highlights'] ?? [])" />
                </div>
            </x-admin.panel>
        @endforeach

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.projects.index') }}" class="rounded-lg border border-stone-300 px-4 py-2 text-sm text-stone-600 hover:bg-stone-50">Cancel</a>
            <button type="submit" class="rounded-lg bg-stone-900 px-4 py-2 text-sm font-medium text-white hover:bg-stone-700">Save project</button>
        </div>
    </form>
</x-admin-layout>
