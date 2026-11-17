<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MediaCollectionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReceiptContactFileRequest;
use App\Http\Resources\ReceiptContactResource;
use App\Models\ReceiptContact;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Rider-Dateien eines DJs bzw. Kontakts (PDF, Fotos) auf der privaten Disk.
 */
class ReceiptContactFileController extends Controller
{
    public function store(ReceiptContactFileRequest $request, ReceiptContact $receiptContact): ReceiptContactResource
    {
        $receiptContact->addMediaFromRequest('file')->toMediaCollection(MediaCollectionEnum::ContactRider->value);
        $receiptContact->touch();

        return ReceiptContactResource::make($receiptContact->load('media'));
    }

    public function show(Request $request, ReceiptContact $receiptContact, int $media): StreamedResponse
    {
        return $this->findFile($receiptContact, $media)->toInlineResponse($request);
    }

    public function destroy(ReceiptContact $receiptContact, int $media): ReceiptContactResource
    {
        $this->findFile($receiptContact, $media)->delete();
        $receiptContact->touch();

        return ReceiptContactResource::make($receiptContact->load('media'));
    }

    private function findFile(ReceiptContact $contact, int $media): Media
    {
        return $contact->media()->where('collection_name', MediaCollectionEnum::ContactRider->value)->findOrFail($media);
    }
}
