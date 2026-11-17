<?php

namespace Database\Factories;

use App\Models\ReceiptContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReceiptContact>
 */
class ReceiptContactFactory extends Factory
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
            'is_dj' => false,
            'real_name' => null,
            'address' => fake()->streetAddress()."\n".fake()->postcode().' '.fake()->city(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'technical_rider' => null,
            'hospitality_rider' => null,
            'notes' => null,
        ];
    }

    /**
     * DJ mit Rider.
     */
    public function dj(): static
    {
        return $this->state(fn (): array => [
            'name' => 'DJ '.fake()->firstName(),
            'is_dj' => true,
            'real_name' => fake()->name(),
            'technical_rider' => '2× CDJ-3000, DJM-900NXS2',
            'hospitality_rider' => '2 Wasser, vegetarisches Essen',
        ]);
    }
}
