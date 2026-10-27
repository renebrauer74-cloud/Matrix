<?php

namespace App\Actions\Events;

use App\Models\Genre;
use Illuminate\Support\Str;

/**
 * Gewählte Genres plus neu eingetippte – neue werden angelegt, Doppelte (gleicher Slug) wiederverwendet.
 */
class ResolveGenresAction
{
    /**
     * @param  list<int>  $genreIds  angehakte, bestehende Genres
     * @param  list<string>  $newNames  im Formular neu eingegebene Genres
     * @return list<int>
     */
    public function execute(array $genreIds, array $newNames): array
    {
        foreach ($newNames as $name) {
            $name = normalize_caps(trim((string) preg_replace('/\s+/u', ' ', $name)));
            $slug = Str::slug($name, '-', 'de');

            if ($slug === '') {
                continue;
            }

            $genreIds[] = Genre::query()->firstOrCreate(['slug' => $slug], ['name' => $name])->getKey();
        }

        return array_values(array_unique(array_map('intval', $genreIds)));
    }
}
