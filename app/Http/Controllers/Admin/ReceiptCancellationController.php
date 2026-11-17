<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Receipts\CancelReceiptAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReceiptReasonRequest;
use App\Http\Resources\ReceiptResource;
use App\Models\Receipt;

/**
 * Quittung stornieren – sie bleibt im Verlauf, die Nummer bleibt vergeben.
 */
class ReceiptCancellationController extends Controller
{
    public function __invoke(ReceiptReasonRequest $request, Receipt $receipt, CancelReceiptAction $cancelReceipt): ReceiptResource
    {
        $cancelReceipt->execute($receipt, $request->string('reason')->toString(), $request->user());

        return ReceiptResource::make($receipt->load(ReceiptResource::RELATIONS));
    }
}
