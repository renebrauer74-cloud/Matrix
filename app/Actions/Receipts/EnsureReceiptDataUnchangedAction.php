<?php

namespace App\Actions\Receipts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Verhindert, dass ein Gerät still überschreibt, was ein anderes Gerät inzwischen geändert hat.
 *
 * Die App schickt beim Speichern mit, welchen Stand (updated_at) sie geladen hatte.
 */
class EnsureReceiptDataUnchangedAction
{
    public function execute(Model $model, ?string $loadedUpdatedAt): void
    {
        if ($loadedUpdatedAt === null || $loadedUpdatedAt === '' || $model->updated_at === null) {
            return;
        }

        if (Carbon::parse($loadedUpdatedAt)->timestamp !== $model->updated_at->timestamp) {
            abort(409, 'Dieser Eintrag wurde inzwischen auf einem anderen Gerät geändert. Der neue Stand ist geladen – bitte die Änderung noch einmal vornehmen.');
        }
    }
}
