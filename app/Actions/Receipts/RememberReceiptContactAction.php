<?php

namespace App\Actions\Receipts;

use App\Models\ReceiptContact;

/**
 * Merkt sich den Zahler bzw. Empfänger einer Quittung als Kontakt – neu oder mit aktualisierter Anschrift.
 */
class RememberReceiptContactAction
{
    public function execute(?int $contactId, string $name, ?string $address): ReceiptContact
    {
        $contact = $contactId !== null
            ? ReceiptContact::query()->findOrFail($contactId)
            : ReceiptContact::query()->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])->first();

        if ($contact === null) {
            return ReceiptContact::query()->create(['name' => trim($name), 'address' => $address]);
        }

        if (filled($address) && $address !== $contact->address) {
            $contact->update(['address' => $address]);
        }

        return $contact;
    }
}
