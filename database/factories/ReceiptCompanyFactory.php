<?php

namespace Database\Factories;

use App\Enums\Receipts\ReceiptTaxEnum;
use App\Models\ReceiptCompany;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReceiptCompany>
 */
class ReceiptCompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'street' => fake()->streetAddress(),
            'zip_city' => fake()->postcode().' '.fake()->city(),
            'tax_number' => '350/5000/'.fake()->numerify('####'),
            'vat_id' => null,
            'contact' => fake()->safeEmail(),
            'is_small_business' => false,
            'default_place' => 'Bochum',
            'default_tax' => ReceiptTaxEnum::Standard,
            'prefix' => strtoupper(fake()->unique()->lexify('??')),
            'counters' => [],
        ];
    }
}
