{{-- Datenschutz-Häkchen und Spam-Falle („fax“ ist für Menschen unsichtbar) --}}
<div class="hp" aria-hidden="true">
    <label for="f-fax">Fax</label>
    <input id="f-fax" name="fax" type="text" tabindex="-1" autocomplete="off">
</div>
<div @class(['form-field', 'span-2', 'has-error' => $errors->has('privacy')])>
    <label class="form-check">
        <input type="checkbox" name="privacy" value="1" required @checked(old('privacy'))>
        <span>Ich bin einverstanden, dass meine Angaben zur Bearbeitung meiner Anfrage gespeichert werden. Mehr dazu in der <a href="{{ route('privacy') }}" target="_blank">Datenschutzerklärung</a>. <span class="req" aria-hidden="true">*</span></span>
    </label>
    <x-forms.error name="privacy" />
    <x-forms.error name="fax" />
</div>
