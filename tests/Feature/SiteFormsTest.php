<?php

use App\Enums\MediaCollectionEnum;
use App\Enums\Messages\MessageStatusEnum;
use App\Enums\Messages\MessageTypeEnum;
use App\Models\JobPosition;
use App\Models\Message;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

/**
 * Pflichtfelder, die in allen Formularen gleich sind.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function personFormData(array $overrides = []): array
{
    return array_merge([
        'name' => 'Kim Beispiel',
        'email' => 'kim@example.com',
        'phone' => '0171 1234567',
        'privacy' => '1',
        'fax' => '',
    ], $overrides);
}

it('stores a contact message in the inbox', function () {
    $this->post(route('contact.store'), personFormData([
        'topic' => 'press',
        'subject' => 'Interview-Anfrage',
        'body' => 'Wir würden gern über den Club berichten.',
    ]))->assertRedirect(route('contact').'#formular')->assertSessionHas('sent');

    expect(Message::query()->sole())
        ->type->toBe(MessageTypeEnum::Contact)
        ->status->toBe(MessageStatusEnum::New)
        ->name->toBe('Kim Beispiel')
        ->subject->toBe('Interview-Anfrage')
        ->details->toBe(['topic' => 'press'])
        ->read_at->toBeNull();
});

it('uses the topic as subject when the contact form has none', function () {
    $this->post(route('contact.store'), personFormData(['topic' => 'feedback', 'subject' => '', 'body' => 'Super Abend!']));

    expect(Message::query()->sole()->subject)->toBe('Lob & Kritik');
});

it('shows a thank-you note instead of the form after sending', function () {
    $this->withSession(['sent' => true])
        ->get(route('contact'))
        ->assertSee('Nachricht angekommen')
        ->assertDontSee('name="body"', false);
});

it('validates the contact form and jumps back to it', function () {
    $this->from(route('contact'))
        ->post(route('contact.store'), personFormData(['email' => 'keine-mail', 'privacy' => '', 'topic' => 'gibtsnicht', 'body' => '']))
        ->assertRedirect(route('contact').'#formular')
        ->assertSessionHasErrors(['email', 'privacy', 'topic', 'body']);

    expect(Message::query()->count())->toBe(0);
});

it('rejects submissions that filled the spam trap', function () {
    $this->post(route('contact.store'), personFormData(['fax' => 'https://spam.example', 'topic' => 'general', 'body' => 'Spam']))
        ->assertSessionHasErrors('fax');

    expect(Message::query()->count())->toBe(0);
});

it('stores a lost item report with photo on the private disk', function () {
    $this->post(route('lost-items.store'), personFormData([
        'item' => 'Schwarze Lederjacke',
        'lost_on' => today()->subDay()->format('Y-m-d'),
        'room' => 'Tube',
        'event' => '90er Party',
        'body' => 'Mit Schlüssel in der Innentasche.',
        'attachment' => UploadedFile::fake()->image('jacke.jpg', 800, 600),
    ]))->assertRedirect(route('lost-items').'#formular');

    $message = Message::query()->sole();
    $photo = $message->getFirstMedia(MediaCollectionEnum::MessageAttachments->value);

    expect($message)
        ->type->toBe(MessageTypeEnum::LostItem)
        ->subject->toBe('Schwarze Lederjacke')
        ->details->toMatchArray(['room' => 'Tube', 'event' => '90er Party'])
        ->and($photo->disk)->toBe('local');
    Storage::disk('local')->assertExists($photo->getPathRelativeToRoot());
});

it('does not accept lost items from the future', function () {
    $this->post(route('lost-items.store'), personFormData([
        'item' => 'Schal',
        'lost_on' => today()->addDay()->format('Y-m-d'),
        'body' => 'Rot.',
    ]))->assertSessionHasErrors(['lost_on' => 'Das Datum liegt in der Zukunft.']);
});

it('lists only active job positions on the jobs page', function () {
    JobPosition::factory()->create(['title' => 'Lichttechnik']);
    JobPosition::factory()->inactive()->create(['title' => 'Besetzte Stelle']);

    $this->get(route('jobs'))
        ->assertOk()
        ->assertSee(['Service an der Theke', 'Garderobe', 'Kasse', 'Lichttechnik', 'Initiativbewerbung'])
        ->assertDontSee('Besetzte Stelle');
});

it('preselects the position linked from a job card', function () {
    $position = JobPosition::query()->where('title', 'Kasse')->sole();

    $this->get(route('jobs', ['stelle' => $position->id]))
        ->assertSee('<option value="'.$position->id.'" selected>', false);
});

it('stores an application with cv', function () {
    $position = JobPosition::query()->where('title', 'Garderobe')->sole();

    $this->post(route('jobs.store'), personFormData([
        'position' => $position->id,
        'start' => 'ab sofort',
        'availability' => 'Freitags und samstags',
        'experience' => '2 Jahre Gastro',
        'body' => 'Ich freue mich!',
        'is_adult' => '1',
        'attachment' => UploadedFile::fake()->create('lebenslauf.pdf', 300, 'application/pdf'),
    ]))->assertRedirect(route('jobs').'#bewerben');

    $message = Message::query()->sole();

    expect($message)
        ->type->toBe(MessageTypeEnum::Application)
        ->subject->toBe('Bewerbung: Garderobe')
        ->details->toMatchArray(['position' => 'Garderobe', 'availability' => 'Freitags und samstags', 'is_adult' => true])
        ->and($message->getFirstMedia(MediaCollectionEnum::MessageAttachments->value)->file_name)->toBe('lebenslauf.pdf');
});

it('accepts unsolicited applications', function () {
    $this->post(route('jobs.store'), personFormData([
        'position' => 'initiativ',
        'availability' => 'Sonntags',
        'is_adult' => '1',
    ]))->assertSessionHasNoErrors();

    expect(Message::query()->sole()->details['position'])->toBe('Initiativbewerbung');
});

it('rejects applications for hidden positions, minors and without phone', function () {
    $hidden = JobPosition::factory()->inactive()->create();

    $this->post(route('jobs.store'), personFormData([
        'position' => $hidden->id,
        'phone' => '',
        'availability' => 'immer',
        'is_adult' => '',
    ]))->assertSessionHasErrors(['position', 'phone', 'is_adult']);
});

it('renders the corporate page with the room photos until own pictures exist', function () {
    $this->get(route('corporate'))
        ->assertOk()
        ->assertSee('Eure Firmenfeier im Müser-Turm')
        ->assertSee('Weihnachtsfeier')
        ->assertSee('assets/img/tube.jpg', false);
});

it('stores a corporate inquiry', function () {
    $this->post(route('corporate.store'), personFormData([
        'company' => 'Ruhr AG',
        'event_type' => 'christmas',
        'date' => today()->addMonths(2)->format('Y-m-d'),
        'guests' => '150',
        'room' => 'Tube',
        'body' => 'Mit Buffet und DJ.',
    ]))->assertRedirect(route('corporate').'#anfrage');

    expect(Message::query()->sole())
        ->type->toBe(MessageTypeEnum::CorporateInquiry)
        ->subject->toBe('Weihnachtsfeier · Ruhr AG')
        ->details->toMatchArray(['company' => 'Ruhr AG', 'guests' => 150, 'room' => 'Tube']);
});

it('rate limits the site forms', function () {
    foreach (range(1, 5) as $attempt) {
        $this->post(route('contact.store'), personFormData(['topic' => 'general', 'body' => 'Hallo '.$attempt]));
    }

    $this->post(route('contact.store'), personFormData(['topic' => 'general', 'body' => 'Zu viel']))
        ->assertTooManyRequests();

    expect(Message::query()->count())->toBe(5);
});
