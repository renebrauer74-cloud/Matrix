<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Receipts\EnsureReceiptDataUnchangedAction;
use App\Actions\Receipts\SaveReceiptCompanyAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReceiptCompanyRequest;
use App\Http\Resources\ReceiptCompanyResource;
use App\Models\ReceiptCompany;
use Illuminate\Http\Response;

/**
 * Firmen der Quittungs-App (JSON).
 */
class ReceiptCompanyController extends Controller
{
    public function store(ReceiptCompanyRequest $request, SaveReceiptCompanyAction $saveCompany): ReceiptCompanyResource
    {
        return ReceiptCompanyResource::make($saveCompany->execute(null, $request->companyAttributes(), $request->integer('next_sequence') ?: null));
    }

    public function update(
        ReceiptCompanyRequest $request,
        ReceiptCompany $receiptCompany,
        SaveReceiptCompanyAction $saveCompany,
        EnsureReceiptDataUnchangedAction $ensureUnchanged,
    ): ReceiptCompanyResource {
        $ensureUnchanged->execute($receiptCompany, $request->input('if_unchanged_since'));

        return ReceiptCompanyResource::make($saveCompany->execute($receiptCompany, $request->companyAttributes(), $request->integer('next_sequence') ?: null));
    }

    /**
     * Bereits erstellte Quittungen bleiben erhalten – ihre Firmendaten stehen in der Quittung selbst.
     */
    public function destroy(ReceiptCompany $receiptCompany): Response
    {
        abort_if(ReceiptCompany::query()->count() < 2, 422, 'Die letzte Firma kann nicht entfernt werden.');

        $receiptCompany->delete();

        return response()->noContent();
    }
}
