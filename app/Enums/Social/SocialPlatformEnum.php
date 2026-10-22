<?php

namespace App\Enums\Social;

/**
 * Social-Media-Plattformen für Links bei Veranstaltungen und im Footer der Website.
 */
enum SocialPlatformEnum: string
{
    case Instagram = 'instagram';
    case Facebook = 'facebook';
    case TikTok = 'tiktok';
    case YouTube = 'youtube';

    public function label(): string
    {
        return match ($this) {
            self::Instagram => 'Instagram',
            self::Facebook => 'Facebook',
            self::TikTok => 'TikTok',
            self::YouTube => 'YouTube',
        };
    }

    /**
     * Symbol aus public/assets/img/icons.svg.
     */
    public function icon(): string
    {
        return $this->value;
    }

    /**
     * Beispiel-Link als Platzhalter im Formular.
     */
    public function placeholder(): string
    {
        return match ($this) {
            self::Instagram => 'https://www.instagram.com/…',
            self::Facebook => 'https://www.facebook.com/…',
            self::TikTok => 'https://www.tiktok.com/@…',
            self::YouTube => 'https://www.youtube.com/@…',
        };
    }
}
