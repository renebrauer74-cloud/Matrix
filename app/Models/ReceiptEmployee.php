<?php

namespace App\Models;

use Database\Factories\ReceiptEmployeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Mitarbeiter, dem Vorschüsse ausgezahlt werden.
 */
#[Fillable(['name', 'note'])]
class ReceiptEmployee extends Model
{
    /** @use HasFactory<ReceiptEmployeeFactory> */
    use HasFactory;

    /**
     * @return HasMany<Receipt, $this>
     */
    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }
}
