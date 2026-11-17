<?php

namespace App\Actions\Receipts;

use App\Enums\Receipts\ReceiptChangeActionEnum;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Löscht eine noch nicht gebuchte Quittung aus dem Verlauf.
 *
 * Der Datensatz bleibt (soft deleted) samt Protokoll erhalten, damit nachvollziehbar ist, wer sie wann gelöscht hat
 * und die Nummer nicht noch einmal vergeben wird.
 */
class DeleteReceiptAction
{
    public function __construct(private LogReceiptChangeAction $logChange) {}

    public function execute(Receipt $receipt, string $reason, User $user): void
    {
        abort_if($receipt->isBooked(), 422, 'Gebuchte Quittungen lassen sich nicht löschen, nur stornieren.');

        DB::transaction(function () use ($receipt, $reason, $user): void {
            $this->logChange->execute($receipt, ReceiptChangeActionEnum::Deleted, $user, $reason);
            $receipt->update(['updated_by' => $user->id]);
            $receipt->delete();
        });
    }
}
