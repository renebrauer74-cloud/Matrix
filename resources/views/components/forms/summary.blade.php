{{-- Hinweis oberhalb des Formulars, wenn die Prüfung fehlgeschlagen ist --}}
@if ($errors->any())
<p class="notice form-notice" role="alert"><x-icon name="alert" /> Bitte prüf die markierten Felder.</p>
@endif
