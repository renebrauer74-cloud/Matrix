<?php

namespace App\Actions\Receipts;

use App\Models\ReceiptCompany;

/**
 * Momentaufnahme der Firmendaten, wie sie auf der Quittung stehen – spätere Änderungen an der Firma ändern alte Belege nicht.
 */
class DescribeReceiptCompanyAction
{
    /**
     * @return array{name: string, street: string, zip_city: string, tax_number: ?string, vat_id: ?string, contact: ?string}
     */
    public function execute(ReceiptCompany $company): array
    {
        return [
            'name' => $company->name,
            'street' => $company->street,
            'zip_city' => $company->zip_city,
            'tax_number' => $company->tax_number,
            'vat_id' => $company->vat_id,
            'contact' => $company->contact,
        ];
    }
}
