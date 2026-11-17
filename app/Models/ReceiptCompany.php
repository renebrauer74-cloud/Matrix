<?php

namespace App\Models;

use App\Enums\Receipts\ReceiptTaxEnum;
use Database\Factories\ReceiptCompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Firma, für die Quittungen ausgestellt werden – mit eigenem Nummernkreis (Kürzel + laufende Nummer je Jahr).
 */
#[Fillable([
    'name', 'street', 'zip_city', 'tax_number', 'vat_id', 'contact', 'is_small_business',
    'default_place', 'default_tax', 'prefix', 'counters',
])]
class ReceiptCompany extends Model
{
    /** @use HasFactory<ReceiptCompanyFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_small_business' => 'boolean',
            'default_tax' => ReceiptTaxEnum::class,
            'counters' => 'array',
        ];
    }

    /**
     * @return HasMany<Receipt, $this>
     */
    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }
}
