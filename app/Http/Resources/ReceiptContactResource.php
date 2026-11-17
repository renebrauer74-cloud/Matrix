<?php

namespace App\Http\Resources;

use App\Enums\MediaCollectionEnum;
use App\Models\ReceiptContact;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @mixin ReceiptContact
 */
class ReceiptContactResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_dj' => $this->is_dj,
            'real_name' => $this->real_name,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'technical_rider' => $this->technical_rider,
            'hospitality_rider' => $this->hospitality_rider,
            'notes' => $this->notes,
            'files' => $this->getMedia(MediaCollectionEnum::ContactRider->value)->map(fn (Media $media): array => [
                'id' => $media->id,
                'name' => $media->file_name,
                'mime' => $media->mime_type,
                'size' => $media->size,
                'created_at' => $media->created_at?->toJSON(),
                'url' => route('admin.receipts.api.contacts.files.show', [$this->resource, $media->id]),
            ])->values(),
            'updated_at' => $this->updated_at?->toJSON(),
        ];
    }
}
