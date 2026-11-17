<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Receipts\MarkReceiptBookedAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReceiptBookingRequest;
use App\Http\Resources\ReceiptResource;
use App\Models\Receipt;

/**
 * Markierung „gebucht“ (Beleg liegt in sevDesk bzw. Lexware Office) setzen oder entfernen.
 */
class ReceiptBookingController extends Controller
{
    public function __invoke(ReceiptBookingRequest $request, Receipt $receipt, MarkReceiptBookedAction $markBooked): ReceiptResource
    {
        $markBooked->execute($receipt, $request->boolean('booked'), $request->user());

        return ReceiptResource::make($receipt->load(ReceiptResource::RELATIONS));
    }
}
