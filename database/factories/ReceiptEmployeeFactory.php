<?php

namespace Database\Factories;

use App\Models\ReceiptEmployee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReceiptEmployee>
 */
class ReceiptEmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'note' => fake()->randomElement(['Theke', 'Technik', 'Einlass', null]),
        ];
    }
}
