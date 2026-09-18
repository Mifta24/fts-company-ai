@props(['name', 'label', 'value' => null, 'type' => 'text', 'hint' => null])

@php $key = str_replace(['[', ']'], ['.', ''], $name); @endphp
<div {{ $attributes->only('class') }}>
    <label class="block text-sm font-medium text-stone-700" for="f-{{ $key }}">{{ $label }}</label>
    <input id="f-{{ $key }}" type="{{ $type }}" name="{{ $name }}" value="{{ old($key, $value) }}"
        {{ $attributes->except('class') }}
        class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-stone-500 focus:outline-none">
    @if ($hint)<p class="mt-1 text-xs text-stone-400">{{ $hint }}</p>@endif
    @error($key)<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>
