@props(['name'])
@error($name)
<p class="field-error">{{ $message }}</p>
@enderror
