@props(['title' => 'Danke!', 'again' => null])
<div class="form-sent" role="status">
    <span class="form-sent-icon"><x-icon name="check" /></span>
    <h2>{{ $title }}</h2>
    <p>{{ $slot }}</p>
@if ($again)
    <a class="btn btn-outline" href="{{ $again }}">Weitere Nachricht schreiben</a>
@endif
</div>
