<x-admin-layout title="Company profile">
    <form method="POST" action="{{ route('admin.company.update') }}" class="space-y-6">
        @csrf @method('PUT')

        <x-admin.panel title="Identity">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-admin.input name="name" label="Company name" :value="$company->name" required />
                <x-admin.input name="ai_staff_name" label="AI Staff name" :value="$company->ai_staff_name" hint="Shown to visitors and used in the AI's persona." required />
                <x-admin.select name="public_status" label="Website status" :value="$company->public_status" :options="['published' => 'Published', 'draft' => 'Draft (hidden)']" />
                <x-admin.select name="default_locale" label="Default language" :value="$company->default_locale" :options="['id' => 'Bahasa Indonesia', 'en' => 'English', 'ja' => '日本語']" />
                <x-admin.input name="currency" label="Currency (ISO code)" :value="$company->currency" maxlength="3" required />
            </div>
        </x-admin.panel>

        <x-admin.panel title="Indonesian (default)">
            <div class="space-y-4">
                <x-admin.input name="tagline" label="Tagline" :value="$company->tagline" />
                <x-admin.textarea name="description" label="Short description (also given to the AI Staff)" :value="$company->description" />
            </div>
        </x-admin.panel>

        @foreach (['en' => 'English', 'ja' => 'Japanese'] as $code => $language)
            <x-admin.panel :title="$language">
                <div class="space-y-4">
                    <x-admin.input name="translations[{{ $code }}][tagline]" label="Tagline" :value="$company->translations[$code]['tagline'] ?? ''" />
                    <x-admin.textarea name="translations[{{ $code }}][description]" label="Short description" :value="$company->translations[$code]['description'] ?? ''" />
                </div>
            </x-admin.panel>
        @endforeach

        <x-admin.panel title="Contact (shown in the website footer)">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-admin.input name="email" type="email" label="Email" :value="$company->email" />
                <x-admin.input name="whatsapp" label="WhatsApp number" :value="$company->whatsapp" hint="International format, e.g. 6281234567890" />
                <x-admin.input name="phone" label="Phone" :value="$company->phone" />
                <x-admin.input name="website_url" type="url" label="Website URL" :value="$company->website_url" />
                <x-admin.input name="address" label="Address" :value="$company->address" class="sm:col-span-2" />
                <x-admin.input name="city" label="City" :value="$company->city" />
                <x-admin.input name="country" label="Country" :value="$company->country" />
            </div>
        </x-admin.panel>

        <div class="flex justify-end">
            <button type="submit" class="rounded-lg bg-stone-900 px-4 py-2 text-sm font-medium text-white hover:bg-stone-700">Save profile</button>
        </div>
    </form>
</x-admin-layout>
