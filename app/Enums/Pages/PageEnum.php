<?php

namespace App\Enums\Pages;

use App\Enums\Social\SocialPlatformEnum;

/**
 * Seiten und Website-Bereiche, deren Inhalte im Admin-Bereich gepflegt werden.
 *
 * Der Wert steht in pages.key und ist zugleich der URL-Teil unter /admin/seiten/{page}.
 */
enum PageEnum: string
{
    case Jobs = 'jobs';
    case Corporate = 'firmen';
    case Location = 'location';
    case Social = 'social-media';

    public function label(): string
    {
        return match ($this) {
            self::Jobs => 'Jobs-Seite',
            self::Corporate => 'Firmenseite',
            self::Location => 'Location-Seite',
            self::Social => 'Social Media',
        };
    }

    /**
     * Öffentliche Route der Seite – die Social-Media-Links stehen im Footer jeder Seite.
     */
    public function routeName(): string
    {
        return match ($this) {
            self::Jobs => 'jobs',
            self::Corporate => 'corporate',
            self::Location => 'location',
            self::Social => 'home',
        };
    }

    /**
     * Ausgangstexte, solange im Admin-Bereich nichts anderes gespeichert wurde.
     *
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return match ($this) {
            self::Jobs => [
                'hero_title' => 'Werde Teil der Crew',
                'hero_lead' => 'Theke, Garderobe, Kasse – wir suchen Servicekräfte, die die Nacht lieben und auch um drei Uhr morgens noch lächeln.',
                'intro_title' => 'Arbeiten, wo andere feiern',
                'intro_text' => "Konzerte, Partys, Firmenfeiern: Im Müser-Turm ist an den Wochenenden richtig was los. Damit das läuft, brauchen wir Leute, auf die man sich verlassen kann.\n\nDu hast Lust auf Nachtschichten mit Musik und einem eingespielten Team? Dann bewirb dich auf eine der Stellen – oder schick uns einfach eine Initiativbewerbung.",
                'benefits' => "Flexible Schichten, vor allem freitags und samstags\nEinarbeitung – auch ohne Gastro-Erfahrung\nEin eingespieltes, herzliches Team\nKonzerte und Partys hautnah erleben",
                'apply_text' => 'Füll das Formular aus – wir melden uns in der Regel innerhalb einer Woche bei dir.',
            ],
            self::Corporate => [
                'hero_title' => 'Eure Firmenfeier im Müser-Turm',
                'hero_lead' => 'Weihnachtsfeier, Sommerfest, Produktpräsentation oder Tagung: sechs Räume mit Industriecharme, eigene Ton- und Lichttechnik und ein Team, das Events kann.',
                'intro_title' => 'Eine Location, die man nicht vergisst',
                'intro_text' => "Statt Hotel-Konferenzraum: Feiert in einer ehemaligen Brauerei von 1928 – Station der Route der Industriekultur und seit Jahrzehnten eine der bekanntesten Eventadressen im Ruhrgebiet.\n\nOb 50 oder mehrere hundert Gäste: Wir stellen die passenden Räume zusammen, kümmern uns um Technik, Getränke und Ablauf und sind vom ersten Gespräch bis zum letzten Song eure Ansprechpartner.",
                'highlights' => [
                    ['title' => 'Sechs Räume', 'text' => 'Von der intimen Bar bis zur großen Halle – einzeln oder kombiniert buchbar.'],
                    ['title' => 'Bühne, Ton & Licht', 'text' => 'Professionelle Veranstaltungstechnik ist schon da – auf Wunsch mit Techniker.'],
                    ['title' => 'Getränke & Catering', 'text' => 'Mehrere Theken, Getränkepakete und Catering nach euren Wünschen.'],
                    ['title' => 'Alles aus einer Hand', 'text' => 'Planung, Personal, Einlass und Garderobe – ihr kümmert euch nur ums Feiern.'],
                    ['title' => 'Gut erreichbar', 'text' => 'Mitten im Ruhrgebiet in Bochum-Langendreer – mit Auto und Bahn gut zu erreichen.'],
                    ['title' => 'Mit Geschichte', 'text' => 'Industriekultur im historischen Sudhaus – ein Rahmen, über den eure Gäste noch lange sprechen.'],
                ],
                'occasions' => "Weihnachtsfeier\nSommerfest\nFirmenjubiläum\nProduktpräsentation\nTagung & Konferenz\nTeamevent\nMitarbeiterparty\nAfterwork",
                'cta_title' => 'Lasst uns über euer Event sprechen',
                'cta_text' => 'Erzählt uns kurz, was ihr vorhabt – wir melden uns mit Raumvorschlag und Angebot.',
                'contact_name' => 'Event-Team Matrix',
                'contact_phone' => config('matrix.venue.phone'),
                'contact_email' => config('matrix.venue.email'),
            ],
            self::Location => [
                'hero_title' => 'Matrix & Rockpalast',
                'hero_lead' => 'Unsere beiden Clubs, Live- & Eventlocations im historischen Müser-Turm in Bochum-Langendreer.',
                'history_title' => 'Vom Sudhaus zum Club',
                'history' => [
                    ['year' => '1806', 'text' => 'Johann Wilhelm Müser gründet eine Brauerei, Schankwirtschaft und einen Laden an der Hauptstraße in Langendreer.'],
                    ['year' => '1891', 'text' => 'Daraus entsteht die Bierbrauerei Gebrüder Müser AG – der Ausbau zur Großbrauerei folgt.'],
                    ['year' => '1928', 'text' => 'Letzte Erweiterung: das rund 30 Meter hohe Sudhaus – heute das Herz von Rockpalast und Matrix.'],
                    ['year' => '1976', 'text' => 'Die Schultheiss-Brauerei, seit 1960 Eigentümerin, schließt das traditionsreiche Brauhaus.'],
                    ['year' => '1978', 'text' => 'Am 1. November eröffnet der Rock Palast im 1. Obergeschoss – schnell eine überregionale Adresse für Rock-Fans.'],
                    ['year' => '1996', 'text' => 'Ausbau zur mehrräumigen Veranstaltungslocation, seit 1998 unter dem Namen Matrix.'],
                    ['year' => '2014', 'text' => 'Der Rockpalast wird eigenständige Live- & Eventvenue. Die ehemalige Brauerei wird Station der Route der Industriekultur.'],
                ],
                'cta_title' => 'Location mieten',
                'cta_text' => 'Konzert, Firmenfeier, Hochzeit oder Lesung – sprecht uns an, wir finden den passenden Raum.',
            ],
            self::Social => [
                'social_links' => array_values(array_filter([
                    config('matrix.venue.instagram') ? ['platform' => SocialPlatformEnum::Instagram->value, 'url' => config('matrix.venue.instagram')] : null,
                    config('matrix.venue.facebook') ? ['platform' => SocialPlatformEnum::Facebook->value, 'url' => config('matrix.venue.facebook')] : null,
                ])),
            ],
        };
    }
}
