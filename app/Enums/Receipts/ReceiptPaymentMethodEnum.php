<?php

namespace App\Enums\Receipts;

enum ReceiptPaymentMethodEnum: string
{
    case Cash = 'Bar';
    case Card = 'EC-Karte';
    case Transfer = 'Überweisung';

    public function label(): string
    {
        return $this->value;
    }
}
