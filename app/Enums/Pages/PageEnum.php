<?php

namespace App\Enums\Pages;

/**
 * Seiten, deren Texte, Bilder und Videos im Admin-Bereich gepflegt werden.
 *
 * Der Wert steht in pages.key und ist zugleich der URL-Teil unter /admin/seiten/{page}.
 */
enum PageEnum: string
{
    case Jobs = 'jobs';
    case Corporate = 'firmen';

    public function label(): string
    {
        return match ($this) {
            self::Jobs => 'Jobs-Seite',
            self::Corporate => 'Firmenseite',
        };
    }

    /**
     * Öffentliche Route der Seite.
     */
    public function routeName(): string
    {
        return match ($this) {
            self::Jobs => 'jobs',
            self::Corporate => 'corporate',
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
        };
    }
}
