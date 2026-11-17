<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Receipts\EnsureReceiptDataUnchangedAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReceiptContactRequest;
use App\Http\Resources\ReceiptContactResource;
use App\Models\ReceiptContact;
use Illuminate\Http\Response;

/**
 * Kontakte und DJs der Quittungs-App (JSON).
 */
class ReceiptContactController extends Controller
{
    public function store(ReceiptContactRequest $request): ReceiptContactResource
    {
        return ReceiptContactResource::make(ReceiptContact::query()->create($request->contactAttributes()));
    }

    public function update(ReceiptContactRequest $request, ReceiptContact $receiptContact, EnsureReceiptDataUnchangedAction $ensureUnchanged): ReceiptContactResource
    {
        $ensureUnchanged->execute($receiptContact, $request->input('if_unchanged_since'));
        $receiptContact->update($request->contactAttributes());

        return ReceiptContactResource::make($receiptContact);
    }

    /**
     * Quittungen dieses Kontakts bleiben erhalten – Name und Anschrift stehen in der Quittung selbst.
     */
    public function destroy(ReceiptContact $receiptContact): Response
    {
        $receiptContact->delete();

        return response()->noContent();
    }
}
