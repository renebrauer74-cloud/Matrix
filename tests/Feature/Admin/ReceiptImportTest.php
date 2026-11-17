<?php

use App\Enums\MediaCollectionEnum;
use App\Enums\Receipts\ReceiptChangeActionEnum;
use App\Models\Receipt;
use App\Models\ReceiptCompany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake(config('matrix.uploads.private_disk'));
});

/**
 * Firma, wie die alte App-Version sie im Browser gespeichert hat.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function deviceCompany(array $overrides = []): array
{
    return array_merge([
        'id' => 'b3c1-geraet',
        'firma' => 'Matrix Bochum',
        'strasse' => 'Hauptstraße 1',
        'plzOrt' => '44892 Bochum',
        'steuernr' => '350/5000/1234',
        'ustid' => '',
        'kontakt' => '',
        'kleinunternehmer' => false,
        'ort' => 'Bochum',
        'standardUst' => '19',
        'praefix' => 'MX1',
        'zaehler' => ['2026' => 9],
    ], $overrides);
}

/**
 * Quittung, wie die App sie aus dem Gerätespeicher hochlädt.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function deviceReceipt(array $overrides = []): array
{
    return array_merge([
        'number' => 'MX1-2026-0009',
        'type' => 'einnahme',
        'date' => '2026-11-10',
        'amount_cents' => 12000,
        'tax' => '19',
        'partner_name' => 'Getränke Kühn',
        'purpose' => 'Leergut',
        'payment_method' => 'Bar',
        'place' => 'Bochum',
        'company' => ['firma' => 'Matrix Bochum', 'strasse' => 'Hauptstraße 1', 'plzOrt' => '44892 Bochum'],
        'created_at' => '2026-11-10T21:15:00.000Z',
    ], $overrides);
}

it('moves the companies of a device into the shared account', function () {
    $existing = ReceiptCompany::factory()->create(['prefix' => 'T1', 'counters' => [2026 => 20]]);

    $map = actingAsAdmin()->postJson(route('admin.receipts.api.import.companies'), [
        'companies' => [
            deviceCompany(),
            deviceCompany(['id' => 'zweite', 'firma' => 'Tochter', 'praefix' => 't1', 'zaehler' => ['2026' => 25]]),
        ],
    ])->assertOk()->json('companies');

    $created = ReceiptCompany::query()->where('prefix', 'MX1')->sole();

    expect($map)->toBe(['b3c1-geraet' => $created->id, 'zweite' => $existing->id])
        ->and($created->tax_number)->toBe('350/5000/1234')
        ->and($created->counters)->toBe(['2026' => 9])
        ->and($existing->fresh()->counters)->toBe(['2026' => 25]);
});

it('moves a receipt of a device with pdf and signature', function () {
    $company = ReceiptCompany::factory()->create(['prefix' => 'MX1', 'counters' => [2026 => 3]]);

    actingAsAdmin()->post(route('admin.receipts.api.import.receipts'), [
        ...deviceReceipt(['receipt_company_id' => $company->id]),
        'signature' => 'data:image/png;base64,'.base64_encode(UploadedFile::fake()->image('sig.png', 40, 20)->getContent()),
        'pdf' => UploadedFile::fake()->createWithContent('q.pdf', "%PDF-1.4\n%%EOF"),
    ], ['Accept' => 'application/json'])->assertOk()->assertJson(['imported' => true]);

    $receipt = Receipt::query()->sole();

    expect($receipt->number)->toBe('MX1-2026-0009')
        ->and($receipt->company_snapshot['name'])->toBe('Matrix Bochum')
        ->and($receipt->created_at->toJSON())->toBe('2026-11-10T21:15:00.000000Z')
        ->and($receipt->getFirstMedia(MediaCollectionEnum::ReceiptPdf->value))->not->toBeNull()
        ->and($receipt->getFirstMedia(MediaCollectionEnum::ReceiptSignature->value))->not->toBeNull()
        ->and($receipt->changes()->sole()->action)->toBe(ReceiptChangeActionEnum::Imported)
        ->and($company->fresh()->counters)->toBe(['2026' => 9]);
});

it('skips receipts that are already in the account', function () {
    Receipt::factory()->create(['number' => 'MX1-2026-0009']);

    actingAsAdmin()->postJson(route('admin.receipts.api.import.receipts'), deviceReceipt())
        ->assertOk()->assertJson(['imported' => false]);

    expect(Receipt::query()->count())->toBe(1);
});
