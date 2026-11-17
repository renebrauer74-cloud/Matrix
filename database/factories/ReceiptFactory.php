<?php

namespace Database\Factories;

use App\Enums\Receipts\ReceiptPaymentMethodEnum;
use App\Enums\Receipts\ReceiptTaxEnum;
use App\Enums\Receipts\ReceiptTypeEnum;
use App\Models\Receipt;
use App\Models\ReceiptCompany;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Receipt>
 */
class ReceiptFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'receipt_company_id' => ReceiptCompany::factory(),
            'number' => 'T-'.now()->year.'-'.fake()->unique()->numerify('####'),
            'type' => ReceiptTypeEnum::Expense,
            'date' => today(),
            'amount_cents' => fake()->numberBetween(500, 50000),
            'tax' => ReceiptTaxEnum::None,
            'partner_name' => fake()->name(),
            'partner_address' => fake()->streetAddress(),
            'purpose' => 'Gage Samstag',
            'payment_method' => ReceiptPaymentMethodEnum::Cash,
            'place' => 'Bochum',
            'signer_name' => null,
            'company_snapshot' => ['name' => 'Matrix Bochum', 'street' => 'Hauptstraße 1', 'zip_city' => '44892 Bochum'],
        ];
    }

    /**
     * In der Buchhaltung angekommen.
     */
    public function booked(): static
    {
        return $this->state(fn (): array => ['booked_at' => now()]);
    }

    /**
     * Storniert.
     */
    public function cancelled(): static
    {
        return $this->state(fn (): array => ['cancelled_at' => now(), 'cancel_reason' => 'Vertippt']);
    }
}
