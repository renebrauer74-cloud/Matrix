<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Receipts\EnsureReceiptDataUnchangedAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReceiptEmployeeRequest;
use App\Http\Resources\ReceiptEmployeeResource;
use App\Models\ReceiptEmployee;
use Illuminate\Http\Response;

/**
 * Mitarbeiter für Vorschüsse (JSON).
 */
class ReceiptEmployeeController extends Controller
{
    public function store(ReceiptEmployeeRequest $request): ReceiptEmployeeResource
    {
        return ReceiptEmployeeResource::make(ReceiptEmployee::query()->create($request->employeeAttributes()));
    }

    public function update(ReceiptEmployeeRequest $request, ReceiptEmployee $receiptEmployee, EnsureReceiptDataUnchangedAction $ensureUnchanged): ReceiptEmployeeResource
    {
        $ensureUnchanged->execute($receiptEmployee, $request->input('if_unchanged_since'));
        $receiptEmployee->update($request->employeeAttributes());

        return ReceiptEmployeeResource::make($receiptEmployee);
    }

    /**
     * Vorschuss-Quittungen bleiben erhalten – der Name steht in der Quittung selbst.
     */
    public function destroy(ReceiptEmployee $receiptEmployee): Response
    {
        $receiptEmployee->delete();

        return response()->noContent();
    }
}
