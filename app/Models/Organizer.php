<?php

namespace App\Models;

use Database\Factories\OrganizerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Externer Veranstalter mit Stammdaten – wird bei weiteren Veranstaltungen wiederverwendet.
 */
#[Fillable(['name', 'contact_person', 'street', 'zip', 'city', 'phone', 'email', 'website'])]
class Organizer extends Model
{
    /** @use HasFactory<OrganizerFactory> */
    use HasFactory;

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
