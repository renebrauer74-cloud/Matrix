<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

function actingAsAdmin(): TestCase
{
    return test()->actingAs(User::factory()->create());
}

/**
 * Minimaler MP4-Kopf, damit die Media Library die Datei als video/mp4 erkennt.
 */
function fakeVideo(string $name = 'clip.mp4'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, "\x00\x00\x00\x18ftypmp42\x00\x00\x00\x00mp42isom".str_repeat("\x00", 512));
}

/**
 * Gültige Formulardaten für eine Veranstaltung.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function eventFormData(array $overrides = []): array
{
    return array_merge([
        'category' => 'konzert',
        'category_label' => 'Metal',
        'title' => 'Steel & Thunder',
        'subtitle' => 'Support: Iron Pulse',
        'description' => 'Laut und schön.',
        'date' => today()->addMonth()->format('Y-m-d'),
        'time' => '20:00',
        'doors' => '19:00',
        'end_time' => '',
        'rooms' => ['Tube'],
        'room_custom' => '',
        'organizer' => 'matrix',
        'image_focus' => 'center',
        'ticket_url' => 'https://tickets.example.com/steel',
        'price_presale' => '24 €',
        'price_box' => '29 €',
        'age_rating' => '',
        'ticket_status' => 'available',
        'status' => 'published',
    ], $overrides);
}
