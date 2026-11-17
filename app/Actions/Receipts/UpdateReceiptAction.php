<?php

namespace App\Actions\Receipts;

use App\Enums\Receipts\ReceiptChangeActionEnum;
use App\Enums\Receipts\ReceiptPaymentMethodEnum;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Korrigiert Kleinigkeiten an einer Quittung (Tippfehler) und protokolliert jede Änderung.
 *
 * Art, Betrag, Datum, Firma und Empfänger hat der Empfänger unterschrieben – die lassen sich nur per Storno und Neuausstellung ändern.
 */
class UpdateReceiptAction
{
    /**
     * Direkt änderbare Felder mit ihrer Bezeichnung im Protokoll.
     */
    public const EDITABLE = [
        'partner_address' => 'Anschrift',
        'purpose' => 'Verwendungszweck',
        'payment_method' => 'Zahlungsart',
        'place' => 'Ort',
        'signer_name' => 'Name in Druckbuchstaben',
    ];

    public function __construct(private LogReceiptChangeAction $logChange) {}

    /**
     * @param  array<string, mixed>  $data  validierte Felder aus UpdateReceiptRequest
     */
    public function execute(Receipt $receipt, array $data, User $user): Receipt
    {
        abort_if($receipt->isCancelled(), 422, 'Stornierte Quittungen lassen sich nicht mehr bearbeiten.');

        $changes = [];

        foreach (array_keys(self::EDITABLE) as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $old = $receipt->{$field} instanceof ReceiptPaymentMethodEnum ? $receipt->{$field}->value : $receipt->{$field};
            $new = $data[$field] === '' ? null : $data[$field];

            if ($old !== $new) {
                $changes[$field] = [$old, $new];
            }
        }

        if ($changes === []) {
            return $receipt;
        }

        return DB::transaction(function () use ($receipt, $changes, $user): Receipt {
            $receipt->fill(array_map(fn (array $change): mixed => $change[1], $changes));
            $receipt->updated_by = $user->id;
            $receipt->save();

            $summary = implode(', ', array_map(fn (string $field): string => self::EDITABLE[$field], array_keys($changes))).' geändert';
            $this->logChange->execute($receipt, ReceiptChangeActionEnum::Updated, $user, $summary, $changes);

            return $receipt;
        });
    }
}
