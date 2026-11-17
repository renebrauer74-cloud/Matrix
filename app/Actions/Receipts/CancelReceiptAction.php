<?php

namespace App\Actions\Receipts;

use App\Enums\Receipts\ReceiptChangeActionEnum;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Storniert eine Quittung: Sie bleibt im Verlauf, ihre Nummer wird nicht neu vergeben.
 */
class CancelReceiptAction
{
    public function __construct(private LogReceiptChangeAction $logChange) {}

    public function execute(Receipt $receipt, string $reason, User $user): Receipt
    {
        abort_if($receipt->isCancelled(), 422, 'Diese Quittung ist bereits storniert.');

        return DB::transaction(function () use ($receipt, $reason, $user): Receipt {
            $receipt->update([
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
                'updated_by' => $user->id,
            ]);
            $this->logChange->execute($receipt, ReceiptChangeActionEnum::Cancelled, $user, $reason);

            return $receipt;
        });
    }
}
