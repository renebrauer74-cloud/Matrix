<?php

namespace App\Http\Resources;

use App\Enums\MediaCollectionEnum;
use App\Models\Receipt;
use App\Models\ReceiptChange;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Receipt
 */
class ReceiptResource extends JsonResource
{
    /**
     * Relationen, die für die Ausgabe geladen sein sollten.
     */
    public const RELATIONS = ['media', 'creator', 'editor', 'corrects', 'correction', 'changes.user'];

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $hasSignature = $this->getFirstMedia(MediaCollectionEnum::ReceiptSignature->value) !== null;
        $hasPdf = $this->getFirstMedia(MediaCollectionEnum::ReceiptPdf->value) !== null;

        return [
            'id' => $this->id,
            'number' => $this->number,
            'company_id' => $this->receipt_company_id,
            'company' => $this->company_snapshot,
            'type' => $this->type->value,
            'date' => $this->date->format('Y-m-d'),
            'amount_cents' => $this->amount_cents,
            'tax' => $this->tax->value,
            'contact_id' => $this->receipt_contact_id,
            'employee_id' => $this->receipt_employee_id,
            'partner_name' => $this->partner_name,
            'partner_address' => $this->partner_address,
            'purpose' => $this->purpose,
            'payment_method' => $this->payment_method->value,
            'place' => $this->place,
            'signer_name' => $this->signer_name,
            'signature' => $hasSignature ? [
                'width' => $this->signature_width,
                'height' => $this->signature_height,
                'url' => route('admin.receipts.api.receipts.signature', $this->resource),
            ] : null,
            'pdf_url' => $hasPdf ? route('admin.receipts.api.receipts.pdf.show', $this->resource) : null,
            'corrects_number' => $this->corrects?->number,
            'corrected_by_number' => $this->correction?->number,
            'booked_at' => $this->booked_at?->toJSON(),
            'cancelled_at' => $this->cancelled_at?->toJSON(),
            'cancel_reason' => $this->cancel_reason,
            'created_by' => $this->creator->name,
            'updated_by' => $this->editor->name,
            'created_at' => $this->created_at?->toJSON(),
            'updated_at' => $this->updated_at?->toJSON(),
            'changes' => $this->changes->map(fn (ReceiptChange $change): array => [
                'action' => $change->action->value,
                'label' => $change->action->label(),
                'summary' => $change->summary,
                'user' => $change->user->name,
                'at' => $change->created_at?->toJSON(),
            ])->values(),
        ];
    }
}
