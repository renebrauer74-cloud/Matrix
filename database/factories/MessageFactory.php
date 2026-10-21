<?php

namespace Database\Factories;

use App\Enums\Location\RoomEnum;
use App\Enums\Messages\ContactTopicEnum;
use App\Enums\Messages\CorporateEventTypeEnum;
use App\Enums\Messages\MessageStatusEnum;
use App\Enums\Messages\MessageTypeEnum;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => MessageTypeEnum::Contact,
            'status' => MessageStatusEnum::New,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'subject' => fake()->sentence(4),
            'body' => fake()->paragraphs(2, true),
            'details' => ['topic' => fake()->randomElement(ContactTopicEnum::cases())->value],
        ];
    }

    public function lostItem(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => MessageTypeEnum::LostItem,
            'subject' => 'Schwarzer Rucksack',
            'details' => [
                'item' => 'Schwarzer Rucksack',
                'lost_on' => today()->subDays(2)->format('Y-m-d'),
                'room' => RoomEnum::Tube->value,
                'event' => null,
            ],
        ]);
    }

    public function application(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => MessageTypeEnum::Application,
            'subject' => 'Bewerbung: Service an der Theke',
            'details' => [
                'position' => 'Service an der Theke',
                'start' => 'ab sofort',
                'availability' => 'Freitags und samstags',
                'experience' => null,
                'is_adult' => true,
            ],
        ]);
    }

    public function corporateInquiry(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => MessageTypeEnum::CorporateInquiry,
            'subject' => 'Weihnachtsfeier · '.($company = fake()->company()),
            'details' => [
                'company' => $company,
                'event_type' => CorporateEventTypeEnum::ChristmasParty->value,
                'date' => today()->addMonths(2)->format('Y-m-d'),
                'guests' => 120,
                'room' => null,
            ],
        ]);
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => ['read_at' => now()]);
    }

    public function done(): static
    {
        return $this->state(fn (array $attributes) => ['status' => MessageStatusEnum::Done, 'read_at' => now()]);
    }
}
