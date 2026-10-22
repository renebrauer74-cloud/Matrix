<?php

namespace App\Actions\Social;

use App\Enums\Social\SocialPlatformEnum;

/**
 * Beschriftung eines Social-Media-Links: der Account aus dem Link (z. B. „@matrixbochum“), sonst die Plattform.
 *
 * So lassen sich mehrere Links derselben Plattform unterscheiden.
 */
class DescribeSocialLinkAction
{
    /**
     * Pfadteile, die kein Account-Name sind (z. B. youtube.com/channel/…, facebook.com/profile.php?id=…).
     */
    private const NON_ACCOUNT_SEGMENTS = ['channel', 'c', 'user', 'watch', 'shorts', 'p', 'reel', 'reels', 'video', 'share', 'pages', 'events', 'groups', 'profile.php'];

    /**
     * @param  array{platform?: string, url?: string}  $link
     */
    public function execute(array $link): string
    {
        $platform = SocialPlatformEnum::tryFrom((string) ($link['platform'] ?? ''));
        $fallback = $platform?->label() ?? 'Link';

        $path = trim((string) parse_url((string) ($link['url'] ?? ''), PHP_URL_PATH), '/');
        $account = ltrim(explode('/', $path)[0], '@');

        if ($account === '' || in_array(strtolower($account), self::NON_ACCOUNT_SEGMENTS, true)) {
            return $fallback;
        }

        return '@'.$account;
    }
}
