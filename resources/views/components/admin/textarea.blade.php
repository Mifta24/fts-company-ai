@props(['name', 'label', 'value' => null, 'rows' => 4, 'hint' => null])

@php $key = str_replace(['[', ']'], ['.', ''], $name); @endphp
<div {{ $attributes->only('class') }}>
    <label class="block text-sm font-medium text-stone-700" for="f-{{ $key }}">{{ $label }}</label>
    <textarea id="f-{{ $key }}" name="{{ $name }}" rows="{{ $rows }}" {{ $attributes->except('class') }}
        class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-stone-500 focus:outline-none">{{ old($key, $value) }}</textarea>
    @if ($hint)<p class="mt-1 text-xs text-stone-400">{{ $hint }}</p>@endif
    @error($key)<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>
