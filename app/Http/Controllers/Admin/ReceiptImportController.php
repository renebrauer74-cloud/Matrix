<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Receipts\ImportDeviceCompaniesAction;
use App\Actions\Receipts\ImportDeviceReceiptAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportReceiptCompaniesRequest;
use App\Http\Requests\Admin\ImportReceiptRequest;
use Illuminate\Http\JsonResponse;

/**
 * Einmaliger Umzug: Firmen und Quittungen, die die alte App-Version nur auf einem Gerät gespeichert hat.
 */
class ReceiptImportController extends Controller
{
    public function companies(ImportReceiptCompaniesRequest $request, ImportDeviceCompaniesAction $importCompanies): JsonResponse
    {
        return response()->json(['companies' => (object) $importCompanies->execute($request->validated('companies'))]);
    }

    public function receipt(ImportReceiptRequest $request, ImportDeviceReceiptAction $importReceipt): JsonResponse
    {
        $receipt = $importReceipt->execute(
            $request->safe()->except(['pdf', 'signature']),
            $request->file('pdf'),
            $request->input('signature'),
            $request->user(),
        );

        return response()->json(['imported' => $receipt !== null, 'number' => $request->input('number')]);
    }
}
