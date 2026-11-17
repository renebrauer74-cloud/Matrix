<?php

namespace App\Models;

use App\Enums\MediaCollectionEnum;
use Database\Factories\ReceiptContactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Wiederkehrender Zahler oder Empfänger einer Quittung – z. B. ein DJ mit Rider oder ein Lieferant.
 */
#[Fillable([
    'name', 'is_dj', 'real_name', 'address', 'phone', 'email', 'technical_rider', 'hospitality_rider', 'notes',
])]
class ReceiptContact extends Model implements HasMedia
{
    /** @use HasFactory<ReceiptContactFactory> */
    use HasFactory, InteractsWithMedia;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_dj' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaCollectionEnum::ContactRider->value)
            ->useDisk(config('matrix.uploads.private_disk'));
    }

    /**
     * @return HasMany<Receipt, $this>
     */
    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }
}
