@props(['index', 'link' => []])
@php
    use App\Enums\Social\SocialPlatformEnum;

    $platform = SocialPlatformEnum::tryFrom((string) ($link['platform'] ?? '')) ?? SocialPlatformEnum::Instagram;
    $hasError = $errors->hasAny(["social_links.$index.platform", "social_links.$index.url"]);
@endphp
<div @class(['social-row', 'has-error' => $hasError]) data-social-row>
    <select name="social_links[{{ $index }}][platform]" aria-label="Plattform" data-social-platform>
@foreach (SocialPlatformEnum::cases() as $option)
        <option value="{{ $option->value }}" data-placeholder="{{ $option->placeholder() }}" @selected($option === $platform)>{{ $option->label() }}</option>
@endforeach
    </select>
    <input name="social_links[{{ $index }}][url]" type="url" inputmode="url" maxlength="500" value="{{ $link['url'] ?? '' }}" placeholder="{{ $platform->placeholder() }}" aria-label="Link" data-social-url>
    <button class="icon-btn icon-btn-danger" type="button" title="Link entfernen" data-social-remove><x-icon name="trash" /><span class="sr-only">Link entfernen</span></button>
    <x-admin.field-error :name="'social_links.'.$index.'.platform'" />
    <x-admin.field-error :name="'social_links.'.$index.'.url'" />
</div>
