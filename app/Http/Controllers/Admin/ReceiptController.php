<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Receipts\CreateReceiptAction;
use App\Actions\Receipts\DeleteReceiptAction;
use App\Actions\Receipts\EnsureReceiptDataUnchangedAction;
use App\Actions\Receipts\UpdateReceiptAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReceiptReasonRequest;
use App\Http\Requests\Admin\StoreReceiptRequest;
use App\Http\Requests\Admin\UpdateReceiptRequest;
use App\Http\Resources\ReceiptResource;
use App\Models\Receipt;
use Illuminate\Http\Response;

/**
 * Quittungen ausstellen, Kleinigkeiten korrigieren, ungebuchte löschen (JSON).
 */
class ReceiptController extends Controller
{
    public function store(StoreReceiptRequest $request, CreateReceiptAction $createReceipt): ReceiptResource
    {
        $receipt = $createReceipt->execute($request->safe()->except('signature'), $request->string('signature')->toString(), $request->user());

        return ReceiptResource::make($receipt->load(ReceiptResource::RELATIONS));
    }

    public function update(
        UpdateReceiptRequest $request,
        Receipt $receipt,
        UpdateReceiptAction $updateReceipt,
        EnsureReceiptDataUnchangedAction $ensureUnchanged,
    ): ReceiptResource {
        $ensureUnchanged->execute($receipt, $request->input('if_unchanged_since'));
        $updateReceipt->execute($receipt, $request->safe()->except('if_unchanged_since'), $request->user());

        return ReceiptResource::make($receipt->load(ReceiptResource::RELATIONS));
    }

    public function destroy(ReceiptReasonRequest $request, Receipt $receipt, DeleteReceiptAction $deleteReceipt): Response
    {
        $deleteReceipt->execute($receipt, $request->string('reason')->toString(), $request->user());

        return response()->noContent();
    }
}
