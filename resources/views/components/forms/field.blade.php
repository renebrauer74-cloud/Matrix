@props(['name', 'label', 'for' => null, 'required' => false, 'hint' => null, 'wide' => false])
@php($errorKey = str_replace(['[', ']'], ['.', ''], $name))
<div {{ $attributes->class(['form-field', 'span-2' => $wide, 'has-error' => $errors->has($errorKey)]) }}>
    <label for="{{ $for ?? 'f-'.$name }}">{{ $label }}@if ($required) <span class="req" aria-hidden="true">*</span>@endif</label>
    {{ $slot }}
@if ($hint)
    <p class="form-hint">{{ $hint }}</p>
@endif
    <x-forms.error :name="$errorKey" />
</div>
