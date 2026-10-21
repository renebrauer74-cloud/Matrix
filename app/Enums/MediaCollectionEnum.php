<?php

namespace App\Enums;

/**
 * Media-Collections – der Wert ist zugleich der Unterordner auf der Disk (siehe CollectionPathGenerator).
 */
enum MediaCollectionEnum: string
{
    case Events = 'events';
}
