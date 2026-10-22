@props(['links' => []])
@php
    $rows = (array) old('social_links', $links ?? []);
    $nextIndex = $rows === [] ? 0 : max(array_map('intval', array_keys($rows))) + 1;
@endphp
<div class="social-links" data-social-links data-next-index="{{ $nextIndex }}">
    <div class="social-rows" data-social-rows>
@foreach ($rows as $index => $link)
        <x-admin.social-link-row :index="$index" :link="(array) $link" />
@endforeach
    </div>
    <template data-social-template>
        <x-admin.social-link-row index="__INDEX__" />
    </template>
    <p class="hint social-empty" data-social-empty @if ($rows !== []) hidden @endif>Noch keine Links – mit dem Button unten hinzufügen.</p>
    <button class="btn btn-ghost btn-sm social-add" type="button" data-social-add><x-icon name="plus" /> Link hinzufügen</button>
    <x-admin.field-error name="social_links" />
</div>
