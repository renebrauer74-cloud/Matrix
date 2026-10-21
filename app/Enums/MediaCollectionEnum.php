<?php

namespace App\Enums;

/**
 * Media-Collections – der Wert ist zugleich der Unterordner auf der Disk (siehe CollectionPathGenerator).
 */
enum MediaCollectionEnum: string
{
    case Events = 'events';

    /** Titelbild einer gepflegten Seite (Jobs, Firmen) */
    case PageHero = 'page-hero';

    /** Hintergrundvideo im Kopf einer gepflegten Seite */
    case PageHeroVideo = 'page-hero-video';

    /** Bildergalerie einer gepflegten Seite */
    case PageGallery = 'page-gallery';

    /** Videos einer gepflegten Seite */
    case PageVideos = 'page-videos';

    /** Anhänge aus den Website-Formularen (Lebenslauf, Foto einer Fundsache) – private Disk */
    case MessageAttachments = 'message-attachments';

    /**
     * Collections mit Videos statt Bildern.
     */
    public function holdsVideos(): bool
    {
        return in_array($this, [self::PageHeroVideo, self::PageVideos], true);
    }
}
