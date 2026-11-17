<?php

use App\Enums\MediaCollectionEnum;
use App\Enums\Receipts\ReceiptChangeActionEnum;
use App\Enums\Receipts\ReceiptTaxEnum;
use App\Enums\Receipts\ReceiptTypeEnum;
use App\Models\Receipt;
use App\Models\ReceiptCompany;
use App\Models\ReceiptContact;
use App\Models\ReceiptEmployee;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(config('matrix.uploads.private_disk'));
});

/**
 * Unterschrift so, wie die App sie schickt (Bild als data-URL).
 */
function signatureDataUrl(): string
{
    return 'data:image/png;base64,'.base64_encode(UploadedFile::fake()->image('sig.png', 40, 20)->getContent());
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function receiptPayload(ReceiptCompany $company, array $overrides = []): array
{
    return array_merge([
        'receipt_company_id' => $company->id,
        'type' => 'ausgabe',
        'date' => '2026-11-12',
        'amount_cents' => 35000,
        'tax' => '0',
        'partner_name' => 'DJ Nightshift',
        'partner_address' => "Musterstraße 4\n44787 Bochum",
        'purpose' => 'Gage Samstag',
        'payment_method' => 'Bar',
        'place' => 'Bochum',
        'signer_name' => 'Jonas Beispiel',
        'signature' => signatureDataUrl(),
        'signature_width' => 40,
        'signature_height' => 20,
        'remember_contact' => true,
    ], $overrides);
}

it('answers guests of the receipt api with 401 instead of a redirect', function () {
    $this->getJson(route('admin.receipts.api.sync'))->assertUnauthorized();
    $this->postJson(route('admin.receipts.api.receipts.store'), [])->assertUnauthorized();
});

it('delivers the whole state and only a short answer when nothing changed', function () {
    $company = ReceiptCompany::factory()->create(['prefix' => 'MX']);
    ReceiptContact::factory()->dj()->create();
    ReceiptEmployee::factory()->create(['name' => 'Lena Hoffmann']);
    Receipt::factory()->for($company, 'company')->create();

    $state = actingAsAdmin()->getJson(route('admin.receipts.api.sync'))->assertOk()->json();

    expect($state['unchanged'])->toBeFalse()
        ->and($state['companies'])->toHaveCount(1)
        ->and($state['contacts'])->toHaveCount(1)
        ->and($state['employees'][0]['name'])->toBe('Lena Hoffmann')
        ->and($state['receipts'])->toHaveCount(1)
        ->and($state['user']['name'])->not->toBeEmpty();

    $this->getJson(route('admin.receipts.api.sync', ['version' => $state['version']]))
        ->assertOk()
        ->assertJson(['unchanged' => true, 'version' => $state['version']])
        ->assertJsonMissingPath('receipts');

    ReceiptEmployee::factory()->create();

    $this->getJson(route('admin.receipts.api.sync', ['version' => $state['version']]))
        ->assertOk()
        ->assertJson(['unchanged' => false])
        ->assertJsonCount(2, 'employees');
});

it('creates a company with its own number range', function () {
    actingAsAdmin()->postJson(route('admin.receipts.api.companies.store'), [
        'name' => 'Matrix Bochum',
        'street' => 'Hauptstraße 1',
        'zip_city' => '44892 Bochum',
        'default_tax' => '19',
        'default_place' => 'Bochum',
        'prefix' => 'M X!',
        'next_sequence' => 15,
    ])->assertCreated()->assertJsonPath('data.prefix', 'MX');

    $company = ReceiptCompany::query()->sole();

    expect($company->counters)->toBe([(string) now()->year => 14]);
});

it('refuses a prefix that another company already uses', function () {
    ReceiptCompany::factory()->create(['prefix' => 'MX']);

    actingAsAdmin()->postJson(route('admin.receipts.api.companies.store'), [
        'name' => 'Zweite Firma',
        'street' => 'Weg 2',
        'zip_city' => '44892 Bochum',
        'default_tax' => '19',
        'prefix' => 'MX',
    ])->assertUnprocessable()->assertJsonValidationErrors('prefix');
});

it('does not overwrite changes another device made in the meantime', function () {
    $contact = ReceiptContact::factory()->create(['name' => 'Alt']);
    $loaded = $contact->updated_at->toJSON();

    $this->travel(5)->seconds();
    $contact->update(['name' => 'Vom Handy geändert']);

    actingAsAdmin()->putJson(route('admin.receipts.api.contacts.update', $contact), [
        'name' => 'Vom Tablet geändert',
        'if_unchanged_since' => $loaded,
    ])->assertStatus(409);

    expect($contact->fresh()->name)->toBe('Vom Handy geändert');
});

it('issues receipts with consecutive numbers per company and year', function () {
    $company = ReceiptCompany::factory()->create(['prefix' => 'MX', 'counters' => [2026 => 13]]);
    $other = ReceiptCompany::factory()->create(['prefix' => 'T1']);

    actingAsAdmin()->postJson(route('admin.receipts.api.receipts.store'), receiptPayload($company))
        ->assertCreated()
        ->assertJsonPath('data.number', 'MX-2026-0014')
        ->assertJsonPath('data.company.name', $company->name)
        ->assertJsonPath('data.created_by', auth()->user()->name);

    $this->postJson(route('admin.receipts.api.receipts.store'), receiptPayload($company))->assertJsonPath('data.number', 'MX-2026-0015');
    $this->postJson(route('admin.receipts.api.receipts.store'), receiptPayload($other))->assertJsonPath('data.number', 'T1-2026-0001');
    $this->postJson(route('admin.receipts.api.receipts.store'), receiptPayload($company, ['date' => '2027-01-02']))->assertJsonPath('data.number', 'MX-2027-0001');

    $receipt = Receipt::query()->where('number', 'MX-2026-0014')->sole();

    expect($receipt->getFirstMedia(MediaCollectionEnum::ReceiptSignature->value))->not->toBeNull()
        ->and($receipt->changes()->sole()->action)->toBe(ReceiptChangeActionEnum::Created);
});

it('skips numbers that are already taken', function () {
    $company = ReceiptCompany::factory()->create(['prefix' => 'MX']);
    Receipt::factory()->for($company, 'company')->create(['number' => 'MX-2026-0001']);
    Receipt::factory()->for($company, 'company')->create(['number' => 'MX-2026-0002'])->delete();

    actingAsAdmin()->postJson(route('admin.receipts.api.receipts.store'), receiptPayload($company))
        ->assertJsonPath('data.number', 'MX-2026-0003');
});

it('remembers a new payee as contact and updates the address of a known one', function () {
    $company = ReceiptCompany::factory()->create();
    $known = ReceiptContact::factory()->dj()->create(['name' => 'DJ Nina K.', 'address' => 'Alte Straße 1']);

    actingAsAdmin()->postJson(route('admin.receipts.api.receipts.store'), receiptPayload($company))->assertCreated();
    $this->postJson(route('admin.receipts.api.receipts.store'), receiptPayload($company, [
        'receipt_contact_id' => $known->id,
        'partner_name' => 'DJ Nina K.',
        'partner_address' => 'Neue Straße 2',
    ]))->assertJsonPath('data.contact_id', $known->id);

    expect(ReceiptContact::query()->where('name', 'DJ Nightshift')->sole()->address)->toBe("Musterstraße 4\n44787 Bochum")
        ->and($known->fresh()->address)->toBe('Neue Straße 2');
});

it('does not save one-off payees as contacts', function () {
    actingAsAdmin()->postJson(route('admin.receipts.api.receipts.store'), receiptPayload(ReceiptCompany::factory()->create(), [
        'remember_contact' => false,
    ]))->assertCreated()->assertJsonPath('data.contact_id', null);

    expect(ReceiptContact::query()->count())->toBe(0);
});

it('books an advance to the chosen employee without tax', function () {
    $employee = ReceiptEmployee::factory()->create(['name' => 'Lena Hoffmann']);

    actingAsAdmin()->postJson(route('admin.receipts.api.receipts.store'), receiptPayload(ReceiptCompany::factory()->create(), [
        'type' => 'vorschuss',
        'receipt_employee_id' => $employee->id,
        'partner_name' => '',
        'tax' => '19',
        'purpose' => 'Vorschuss auf Lohn November 2026',
    ]))
        ->assertCreated()
        ->assertJsonPath('data.type', ReceiptTypeEnum::Advance->value)
        ->assertJsonPath('data.employee_id', $employee->id)
        ->assertJsonPath('data.partner_name', 'Lena Hoffmann')
        ->assertJsonPath('data.tax', ReceiptTaxEnum::None->value);

    expect(ReceiptContact::query()->count())->toBe(0);
});

it('needs an employee for an advance', function () {
    actingAsAdmin()->postJson(route('admin.receipts.api.receipts.store'), receiptPayload(ReceiptCompany::factory()->create(), [
        'type' => 'vorschuss',
    ]))->assertUnprocessable()->assertJsonValidationErrors('receipt_employee_id');
});

it('needs a signature', function () {
    actingAsAdmin()->postJson(route('admin.receipts.api.receipts.store'), receiptPayload(ReceiptCompany::factory()->create(), [
        'signature' => '',
    ]))->assertUnprocessable()->assertJsonValidationErrors('signature');
});

it('stores the pdf the app built and hands it out again', function () {
    $receipt = Receipt::factory()->create();

    actingAsAdmin()->post(route('admin.receipts.api.receipts.pdf.store', $receipt), [
        'pdf' => UploadedFile::fake()->createWithContent('q.pdf', "%PDF-1.4\n%âãÏÓ\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF"),
    ], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('data.pdf_url', route('admin.receipts.api.receipts.pdf.show', $receipt));

    $this->get(route('admin.receipts.api.receipts.pdf.show', $receipt))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

it('corrects small details, keeps signed details and logs the change', function () {
    $receipt = Receipt::factory()->create(['purpose' => 'Gage', 'amount_cents' => 35000, 'place' => 'Bochum']);

    actingAsAdmin()->putJson(route('admin.receipts.api.receipts.update', $receipt), [
        'purpose' => 'Gage Samstag, Main Floor',
        'place' => 'Bochum',
        'amount_cents' => 1,
        'type' => 'einnahme',
        'if_unchanged_since' => $receipt->updated_at->toJSON(),
    ])->assertOk()->assertJsonPath('data.purpose', 'Gage Samstag, Main Floor');

    $receipt->refresh();
    $change = $receipt->changes()->sole();

    expect($receipt->amount_cents)->toBe(35000)
        ->and($receipt->type)->toBe(ReceiptTypeEnum::Expense)
        ->and($change->action)->toBe(ReceiptChangeActionEnum::Updated)
        ->and($change->summary)->toBe('Verwendungszweck geändert')
        ->and($change->changes)->toBe(['purpose' => ['Gage', 'Gage Samstag, Main Floor']]);
});

it('does not edit a cancelled receipt', function () {
    $receipt = Receipt::factory()->cancelled()->create();

    actingAsAdmin()->putJson(route('admin.receipts.api.receipts.update', $receipt), ['purpose' => 'Neu'])
        ->assertUnprocessable();
});

it('marks a receipt as booked and back', function () {
    $receipt = Receipt::factory()->create();

    actingAsAdmin()->postJson(route('admin.receipts.api.receipts.booking', $receipt), ['booked' => true])
        ->assertOk()->assertJsonPath('data.booked_at', fn (?string $at): bool => $at !== null);
    $this->postJson(route('admin.receipts.api.receipts.booking', $receipt), ['booked' => false])
        ->assertOk()->assertJsonPath('data.booked_at', null);

    expect($receipt->changes()->pluck('action')->all())->toBe([ReceiptChangeActionEnum::Unbooked, ReceiptChangeActionEnum::Booked]);
});

it('deletes an unbooked receipt but keeps it in the database', function () {
    $receipt = Receipt::factory()->create();

    actingAsAdmin()->deleteJson(route('admin.receipts.api.receipts.destroy', $receipt), ['reason' => 'Doppelt angelegt'])
        ->assertNoContent();

    expect(Receipt::query()->find($receipt->id))->toBeNull()
        ->and(Receipt::withTrashed()->find($receipt->id)->changes()->sole()->summary)->toBe('Doppelt angelegt');
});

it('only cancels booked receipts', function () {
    $receipt = Receipt::factory()->booked()->create();

    actingAsAdmin()->deleteJson(route('admin.receipts.api.receipts.destroy', $receipt), ['reason' => 'Falsch'])
        ->assertUnprocessable();

    $this->postJson(route('admin.receipts.api.receipts.cancel', $receipt), ['reason' => 'Einnahme statt Ausgabe gewählt'])
        ->assertOk()
        ->assertJsonPath('data.cancel_reason', 'Einnahme statt Ausgabe gewählt');

    expect($receipt->fresh()->isCancelled())->toBeTrue();

    $this->postJson(route('admin.receipts.api.receipts.cancel', $receipt), ['reason' => 'Nochmal'])->assertUnprocessable();
});

it('links a correction to the cancelled receipt', function () {
    $company = ReceiptCompany::factory()->create(['prefix' => 'MX']);
    $cancelled = Receipt::factory()->for($company, 'company')->cancelled()->create(['number' => 'MX-2026-0013']);

    actingAsAdmin()->postJson(route('admin.receipts.api.receipts.store'), receiptPayload($company, ['corrects_receipt_id' => $cancelled->id]))
        ->assertJsonPath('data.corrects_number', 'MX-2026-0013');

    $state = $this->getJson(route('admin.receipts.api.sync'))->json('receipts');

    expect(collect($state)->firstWhere('number', 'MX-2026-0013')['corrected_by_number'])->toBe('MX-2026-0001');
});

it('manages djs with rider files', function () {
    $response = actingAsAdmin()->postJson(route('admin.receipts.api.contacts.store'), [
        'name' => 'DJ Nightshift',
        'is_dj' => true,
        'real_name' => 'Jonas Beispiel',
        'technical_rider' => '2× CDJ-3000',
        'hospitality_rider' => '2 Wasser',
    ])->assertCreated()->assertJsonPath('data.is_dj', true);

    $contact = ReceiptContact::query()->findOrFail($response->json('data.id'));

    $files = $this->post(route('admin.receipts.api.contacts.files.store', $contact), [
        'file' => UploadedFile::fake()->create('Rider.pdf', 120, 'application/pdf'),
    ], ['Accept' => 'application/json'])->assertOk()->json('data.files');

    expect($files)->toHaveCount(1)->and($files[0]['name'])->toBe('Rider.pdf');

    $this->get($files[0]['url'])->assertOk();

    $this->deleteJson(route('admin.receipts.api.contacts.files.destroy', [$contact, $files[0]['id']]))
        ->assertOk()->assertJsonCount(0, 'data.files');
});

it('refuses rider files that are no pdf or photo', function () {
    $contact = ReceiptContact::factory()->dj()->create();

    actingAsAdmin()->post(route('admin.receipts.api.contacts.files.store', $contact), [
        'file' => UploadedFile::fake()->create('virus.exe', 10),
    ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('file');
});

it('keeps receipts when a contact, employee or company is removed', function () {
    $company = ReceiptCompany::factory()->create();
    ReceiptCompany::factory()->create();
    $contact = ReceiptContact::factory()->create();
    $employee = ReceiptEmployee::factory()->create();
    $receipt = Receipt::factory()->for($company, 'company')->create(['receipt_contact_id' => $contact->id]);
    $advance = Receipt::factory()->create(['receipt_employee_id' => $employee->id, 'type' => ReceiptTypeEnum::Advance]);

    actingAsAdmin()->deleteJson(route('admin.receipts.api.contacts.destroy', $contact))->assertNoContent();
    $this->deleteJson(route('admin.receipts.api.employees.destroy', $employee))->assertNoContent();
    $this->deleteJson(route('admin.receipts.api.companies.destroy', $company))->assertNoContent();

    expect($receipt->fresh()->receipt_contact_id)->toBeNull()
        ->and($receipt->fresh()->receipt_company_id)->toBeNull()
        ->and($advance->fresh()->receipt_employee_id)->toBeNull();
});

it('keeps the last company', function () {
    $company = ReceiptCompany::factory()->create();

    actingAsAdmin()->deleteJson(route('admin.receipts.api.companies.destroy', $company))->assertUnprocessable();
});

it('manages employees', function () {
    $id = actingAsAdmin()->postJson(route('admin.receipts.api.employees.store'), ['name' => 'Tim Becker', 'note' => 'Technik'])
        ->assertCreated()->json('data.id');

    $this->putJson(route('admin.receipts.api.employees.update', $id), ['name' => 'Tim Becker', 'note' => 'Theke'])
        ->assertOk()->assertJsonPath('data.note', 'Theke');
});
