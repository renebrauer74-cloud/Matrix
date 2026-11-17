<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MediaCollectionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReceiptPdfRequest;
use App\Http\Resources\ReceiptResource;
use App\Models\Receipt;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * PDF und Unterschrift einer Quittung (private Disk). Das PDF baut die App im Browser und lädt es hoch.
 */
class ReceiptPdfController extends Controller
{
    public function store(ReceiptPdfRequest $request, Receipt $receipt): ReceiptResource
    {
        $receipt->addMediaFromRequest('pdf')
            ->usingFileName('quittung-'.$receipt->id.'.pdf')
            ->toMediaCollection(MediaCollectionEnum::ReceiptPdf->value);
        $receipt->touch();

        return ReceiptResource::make($receipt->load(ReceiptResource::RELATIONS));
    }

    public function show(Request $request, Receipt $receipt): StreamedResponse
    {
        $pdf = $receipt->getFirstMedia(MediaCollectionEnum::ReceiptPdf->value);
        abort_if($pdf === null, 404);

        return $pdf->toInlineResponse($request);
    }

    public function signature(Request $request, Receipt $receipt): StreamedResponse
    {
        $signature = $receipt->getFirstMedia(MediaCollectionEnum::ReceiptSignature->value);
        abort_if($signature === null, 404);

        return $signature->toInlineResponse($request);
    }
}
