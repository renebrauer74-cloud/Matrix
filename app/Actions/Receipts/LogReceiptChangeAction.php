<?php

namespace App\Actions\Receipts;

use App\Enums\Receipts\ReceiptChangeActionEnum;
use App\Models\Receipt;
use App\Models\ReceiptChange;
use App\Models\User;

/**
 * Schreibt einen Eintrag ins Änderungsprotokoll einer Quittung.
 */
class LogReceiptChangeAction
{
    /**
     * @param  array<string, array{0: mixed, 1: mixed}>|null  $changes  Feld => [vorher, nachher]
     */
    public function execute(Receipt $receipt, ReceiptChangeActionEnum $action, ?User $user, ?string $summary = null, ?array $changes = null): ReceiptChange
    {
        return ReceiptChange::query()->create([
            'receipt_id' => $receipt->id,
            'user_id' => $user?->id,
            'action' => $action,
            'summary' => $summary,
            'changes' => $changes,
            'created_at' => now(),
        ]);
    }
}
