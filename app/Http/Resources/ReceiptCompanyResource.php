<?php

namespace App\Http\Resources;

use App\Models\ReceiptCompany;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ReceiptCompany
 */
class ReceiptCompanyResource extends JsonResource
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
            'street' => $this->street,
            'zip_city' => $this->zip_city,
            'tax_number' => $this->tax_number,
            'vat_id' => $this->vat_id,
            'contact' => $this->contact,
            'is_small_business' => $this->is_small_business,
            'default_place' => $this->default_place,
            'default_tax' => $this->default_tax->value,
            'prefix' => $this->prefix,
            'counters' => (object) ($this->counters ?? []),
            'updated_at' => $this->updated_at?->toJSON(),
        ];
    }
}
