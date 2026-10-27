<?php

namespace App\Actions\Events;

/**
 * Video-ID aus allen üblichen YouTube-Links (watch, youtu.be, shorts, live, embed).
 */
class ExtractYoutubeIdAction
{
    public function execute(?string $url): ?string
    {
        $pattern = '~(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/|v/)|youtu\.be/)([A-Za-z0-9_-]{11})~';

        return $url !== null && preg_match($pattern, $url, $match) ? $match[1] : null;
    }
}
