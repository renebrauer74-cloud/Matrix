<?php

namespace App\Actions\Receipts;

use App\Enums\Receipts\ReceiptChangeActionEnum;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Markiert eine Quittung als „gebucht“ (liegt in sevDesk bzw. Lexware Office) oder nimmt die Markierung zurück.
 *
 * Gebuchte Quittungen lassen sich nicht mehr löschen, nur noch stornieren.
 */
class MarkReceiptBookedAction
{
    public function __construct(private LogReceiptChangeAction $logChange) {}

    public function execute(Receipt $receipt, bool $booked, User $user): Receipt
    {
        if ($receipt->isBooked() === $booked) {
            return $receipt;
        }

        return DB::transaction(function () use ($receipt, $booked, $user): Receipt {
            $receipt->update(['booked_at' => $booked ? now() : null, 'updated_by' => $user->id]);
            $this->logChange->execute($receipt, $booked ? ReceiptChangeActionEnum::Booked : ReceiptChangeActionEnum::Unbooked, $user);

            return $receipt;
        });
    }
}
