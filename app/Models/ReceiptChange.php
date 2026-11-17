<?php

namespace App\Models;

use App\Enums\Receipts\ReceiptChangeActionEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eintrag im Änderungsprotokoll einer Quittung – wer hat wann was gemacht.
 */
#[Fillable(['receipt_id', 'user_id', 'action', 'summary', 'changes', 'created_at'])]
class ReceiptChange extends Model
{
    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => ReceiptChangeActionEnum::class,
            'changes' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Receipt, $this>
     */
    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault(['name' => 'Unbekannt']);
    }
}
