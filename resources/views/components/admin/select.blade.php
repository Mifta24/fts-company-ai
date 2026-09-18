@props(['name', 'label', 'options' => [], 'value' => null])

<div {{ $attributes->only('class') }}>
    <label class="block text-sm font-medium text-stone-700" for="f-{{ $name }}">{{ $label }}</label>
    <select id="f-{{ $name }}" name="{{ $name }}" class="mt-1 w-full rounded-lg border border-stone-300 px-3 py-2 text-sm">
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) old($name, $value) === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @error($name)<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>
