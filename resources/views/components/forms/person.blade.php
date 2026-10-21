@props(['requirePhone' => false, 'phoneHint' => null])
<x-forms.field name="name" label="Name" required>
    <input id="f-name" name="name" type="text" maxlength="120" required autocomplete="name" value="{{ old('name') }}">
</x-forms.field>
<x-forms.field name="email" label="E-Mail" required>
    <input id="f-email" name="email" type="email" maxlength="190" required autocomplete="email" inputmode="email" value="{{ old('email') }}">
</x-forms.field>
<x-forms.field name="phone" label="Telefon" :required="$requirePhone" :hint="$phoneHint">
    <input id="f-phone" name="phone" type="tel" maxlength="60" autocomplete="tel" inputmode="tel" value="{{ old('phone') }}" @if ($requirePhone) required @endif>
</x-forms.field>
