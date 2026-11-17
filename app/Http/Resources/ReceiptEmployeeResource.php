<?php

namespace App\Http\Resources;

use App\Models\ReceiptEmployee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ReceiptEmployee
 */
class ReceiptEmployeeResource extends JsonResource
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
            'note' => $this->note,
            'updated_at' => $this->updated_at?->toJSON(),
        ];
    }
}
