@props(['links' => [], 'labelled' => false])
@inject('describeLink', 'App\Actions\Social\DescribeSocialLinkAction')
@php
    use App\Enums\Social\SocialPlatformEnum;

    $items = collect($links ?? [])
        ->map(fn (array $link): array => [...$link, 'platform' => SocialPlatformEnum::tryFrom((string) ($link['platform'] ?? ''))])
        ->filter(fn (array $link): bool => $link['platform'] !== null && filled($link['url'] ?? null));
@endphp
@if ($items->isNotEmpty())
<div {{ $attributes }}>
@foreach ($items as $link)
@if ($labelled)
    <a class="social-link" href="{{ $link['url'] }}" target="_blank" rel="noopener" title="{{ $link['platform']->label() }}"><x-icon :name="$link['platform']->icon()" /> {{ $describeLink->execute(['platform' => $link['platform']->value, 'url' => $link['url']]) }}</a>
@else
    <a href="{{ $link['url'] }}" target="_blank" rel="noopener" aria-label="{{ $link['platform']->label() }}" title="{{ $link['platform']->label() }}"><x-icon :name="$link['platform']->icon()" /></a>
@endif
@endforeach
</div>
@endif
