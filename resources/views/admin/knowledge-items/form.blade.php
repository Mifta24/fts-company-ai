@php $isEdit = $item->exists; @endphp

<x-admin-layout :title="$isEdit ? 'Edit knowledge item' : 'Add knowledge item'">
    <form method="POST" action="{{ $isEdit ? route('admin.knowledge-items.update', $item) : route('admin.knowledge-items.store') }}" class="space-y-6">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <x-admin.panel>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-admin.select name="category" label="Category" :value="$item->category" :options="array_combine(\App\Models\KnowledgeItem::CATEGORIES, array_map('ucfirst', \App\Models\KnowledgeItem::CATEGORIES))" />
                <x-admin.input name="sort_order" type="number" label="Sort order" :value="$item->sort_order" />
                <x-admin.input name="tags" label="Tags (comma-separated, in any language — helps the AI match questions)" :value="implode(', ', $item->tags ?? [])" placeholder="harga, price, 料金" class="sm:col-span-2" />
                <x-admin.checkbox name="is_active" label="Active (the AI can use this entry)" :checked="$item->is_active" class="sm:col-span-2" />
            </div>
            <p class="mt-4 text-xs text-stone-400">"About" and "process" entries also appear on the public website; "faq" entries appear in the FAQ list; the first "contact" entry appears in the contact section.</p>
        </x-admin.panel>

        <x-admin.panel title="Indonesian (default)">
            <div class="space-y-4">
                <x-admin.input name="title" label="Title" :value="$item->title" required />
                <x-admin.textarea name="body" label="Body" :value="$item->body" required />
            </div>
        </x-admin.panel>

        @foreach (['en' => 'English', 'ja' => 'Japanese'] as $code => $language)
            <x-admin.panel :title="$language">
                <div class="space-y-4">
                    <x-admin.input name="translations[{{ $code }}][title]" label="Title" :value="$item->translations[$code]['title'] ?? ''" />
                    <x-admin.textarea name="translations[{{ $code }}][body]" label="Body" :value="$item->translations[$code]['body'] ?? ''" />
                </div>
            </x-admin.panel>
        @endforeach

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.knowledge-items.index') }}" class="rounded-lg border border-stone-300 px-4 py-2 text-sm text-stone-600 hover:bg-stone-50">Cancel</a>
            <button type="submit" class="rounded-lg bg-stone-900 px-4 py-2 text-sm font-medium text-white hover:bg-stone-700">Save entry</button>
        </div>
    </form>
</x-admin-layout>
