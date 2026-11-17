<?php

namespace App\Models;

use App\Enums\MediaCollectionEnum;
use App\Enums\Receipts\ReceiptPaymentMethodEnum;
use App\Enums\Receipts\ReceiptTaxEnum;
use App\Enums\Receipts\ReceiptTypeEnum;
use Database\Factories\ReceiptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Ausgestellte Quittung. Gelöschte Quittungen bleiben (soft deleted) in der Datenbank, damit ihre Nummer belegt bleibt.
 */
#[Fillable([
    'receipt_company_id', 'number', 'type', 'date', 'amount_cents', 'tax', 'receipt_contact_id', 'receipt_employee_id',
    'partner_name', 'partner_address', 'purpose', 'payment_method', 'place', 'signer_name', 'company_snapshot',
    'signature_width', 'signature_height', 'corrects_receipt_id', 'booked_at', 'cancelled_at', 'cancel_reason',
    'created_by', 'updated_by', 'created_at',
])]
class Receipt extends Model implements HasMedia
{
    /** @use HasFactory<ReceiptFactory> */
    use HasFactory, InteractsWithMedia, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ReceiptTypeEnum::class,
            'tax' => ReceiptTaxEnum::class,
            'payment_method' => ReceiptPaymentMethodEnum::class,
            'date' => 'date',
            'amount_cents' => 'integer',
            'company_snapshot' => 'array',
            'booked_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaCollectionEnum::ReceiptPdf->value)
            ->useDisk(config('matrix.uploads.private_disk'))
            ->singleFile();

        $this->addMediaCollection(MediaCollectionEnum::ReceiptSignature->value)
            ->useDisk(config('matrix.uploads.private_disk'))
            ->singleFile();
    }

    /**
     * @return BelongsTo<ReceiptCompany, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(ReceiptCompany::class, 'receipt_company_id');
    }

    /**
     * @return BelongsTo<ReceiptContact, $this>
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(ReceiptContact::class, 'receipt_contact_id');
    }

    /**
     * @return BelongsTo<ReceiptEmployee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(ReceiptEmployee::class, 'receipt_employee_id');
    }

    /**
     * Die Quittung, die mit dieser korrigiert wurde.
     *
     * @return BelongsTo<Receipt, $this>
     */
    public function corrects(): BelongsTo
    {
        return $this->belongsTo(Receipt::class, 'corrects_receipt_id')->withTrashed();
    }

    /**
     * Die korrigierte Neuausstellung dieser (stornierten) Quittung.
     *
     * @return HasOne<Receipt, $this>
     */
    public function correction(): HasOne
    {
        return $this->hasOne(Receipt::class, 'corrects_receipt_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withDefault(['name' => 'Unbekannt']);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by')->withDefault(['name' => 'Unbekannt']);
    }

    /**
     * @return HasMany<ReceiptChange, $this>
     */
    public function changes(): HasMany
    {
        return $this->hasMany(ReceiptChange::class)->latest('id');
    }

    public function isBooked(): bool
    {
        return $this->booked_at !== null;
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }
}
