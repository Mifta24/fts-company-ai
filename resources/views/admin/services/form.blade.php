@php
    $isEdit = $service->exists;
    $tiersText = collect($service->pricing_tiers ?? [])->map(fn ($tier) => trim($tier['name'].' | '.$tier['price'].' | '.($tier['unit'] ?? ''), ' |'))->implode("\n");
@endphp

<x-admin-layout :title="$isEdit ? 'Edit service' : 'Add service'">
    <form method="POST" action="{{ $isEdit ? route('admin.services.update', $service) : route('admin.services.store') }}" class="space-y-6">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <x-admin.panel title="Basics">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-admin.input name="name" label="Name (Indonesian)" :value="$service->name" required />
                <x-admin.input name="slug" label="Slug" :value="$service->slug" hint="Leave empty to generate from the name." />
                <x-admin.select name="category" label="Category" :value="$service->category" :options="array_combine(\App\Models\Service::CATEGORIES, array_map('ucfirst', \App\Models\Service::CATEGORIES))" />
                <x-admin.input name="sort_order" type="number" label="Sort order" :value="$service->sort_order" />
                <x-admin.input name="image_url" label="Image URL (optional)" :value="$service->image_url" class="sm:col-span-2" />
                <div class="flex gap-6 sm:col-span-2">
                    <x-admin.checkbox name="is_active" label="Active (visible and usable by the AI)" :checked="$service->is_active" />
                    <x-admin.checkbox name="is_featured" label="Featured" :checked="$service->is_featured" />
                </div>
            </div>
        </x-admin.panel>

        <x-admin.panel title="Indonesian (default)">
            <div class="space-y-4">
                <x-admin.input name="summary" label="One-line summary" :value="$service->summary" required />
                <x-admin.textarea name="description" label="Description" :value="$service->description" required />
                <x-admin.textarea name="features" label="Features (one per line)" :value="implode(PHP_EOL, $service->features ?? [])" rows="5" />
                <x-admin.textarea name="ideal_for" label="Ideal for (one per line)" :value="implode(PHP_EOL, $service->ideal_for ?? [])" rows="3" />
            </div>
        </x-admin.panel>

        <x-admin.panel title="Pricing">
            <div class="grid gap-4 sm:grid-cols-3">
                <x-admin.select name="pricing_model" label="Pricing model" :value="$service->pricing_model" :options="['quotation' => 'Quotation (no public price)', 'subscription' => 'Subscription', 'one_time' => 'One-time']" />
                <x-admin.input name="starting_price" type="number" step="1" label="Starting price" :value="$service->starting_price !== null ? (int) $service->starting_price : null" />
                <x-admin.input name="price_unit" label="Price unit" :value="$service->price_unit" placeholder="/ bulan" />
                <x-admin.textarea name="price_note" label="Price note (the AI repeats this)" :value="$service->price_note" rows="2" class="sm:col-span-3" />
                <x-admin.textarea name="pricing_tiers" label="Tiers (optional, one per line: Name | price | unit — whole amounts)" :value="$tiersText" rows="4" class="sm:col-span-3" placeholder="Starter | 49000 | / bulan" />
            </div>
        </x-admin.panel>

        @foreach (['en' => 'English', 'ja' => 'Japanese'] as $code => $language)
            @php $t = $service->translations[$code] ?? []; @endphp
            <x-admin.panel :title="$language">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-admin.input name="translations[{{ $code }}][name]" label="Name" :value="$t['name'] ?? ''" />
                    <x-admin.input name="translations[{{ $code }}][price_unit]" label="Price unit" :value="$t['price_unit'] ?? ''" />
                    <x-admin.input name="translations[{{ $code }}][summary]" label="Summary" :value="$t['summary'] ?? ''" class="sm:col-span-2" />
                    <x-admin.textarea name="translations[{{ $code }}][description]" label="Description" :value="$t['description'] ?? ''" class="sm:col-span-2" />
                    <x-admin.textarea name="translations[{{ $code }}][features]" label="Features (one per line)" :value="implode(PHP_EOL, $t['features'] ?? [])" class="sm:col-span-2" />
                    <x-admin.textarea name="translations[{{ $code }}][price_note]" label="Price note" :value="$t['price_note'] ?? ''" rows="2" class="sm:col-span-2" />
                </div>
            </x-admin.panel>
        @endforeach

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.services.index') }}" class="rounded-lg border border-stone-300 px-4 py-2 text-sm text-stone-600 hover:bg-stone-50">Cancel</a>
            <button type="submit" class="rounded-lg bg-stone-900 px-4 py-2 text-sm font-medium text-white hover:bg-stone-700">Save service</button>
        </div>
    </form>
</x-admin-layout>
