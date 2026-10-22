<?php

namespace Database\Factories;

use App\Enums\Events\EventCategoryEnum;
use App\Enums\Events\EventStatusEnum;
use App\Enums\Events\ImageFocusEnum;
use App\Enums\Events\TicketStatusEnum;
use App\Enums\Location\RoomEnum;
use App\Enums\MediaCollectionEnum;
use App\Models\Event;
use App\Models\Organizer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = Str::title(fake()->unique()->words(2, true));
        $startsAt = fake()->dateTimeBetween('+1 day', '+2 months')->setTime(20, 0);

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(6)),
            'subtitle' => fake()->optional()->sentence(4),
            'category' => fake()->randomElement(EventCategoryEnum::cases()),
            'category_label' => fake()->optional()->word(),
            'description' => fake()->paragraphs(2, true),
            'starts_at' => $startsAt,
            'rooms' => [fake()->randomElement(RoomEnum::cases())->value],
            'image_focus' => ImageFocusEnum::Center,
            'ticket_url' => 'https://tickets.example.com/'.Str::random(6),
            'ticket_status' => TicketStatusEnum::Available,
            'status' => EventStatusEnum::Published,
            'is_featured' => false,
        ];
    }

    public function category(EventCategoryEnum $category): static
    {
        return $this->state(fn (array $attributes) => ['category' => $category]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => ['status' => EventStatusEnum::Draft]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => ['is_featured' => true]);
    }

    public function external(): static
    {
        return $this->state(fn (array $attributes) => ['organizer_id' => Organizer::factory()]);
    }

    public function past(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->subWeek()->setTime(20, 0),
            'ends_at' => null,
        ]);
    }

    public function withImage(): static
    {
        return $this->afterCreating(function (Event $event): void {
            $event->addMedia(UploadedFile::fake()->image('flyer.jpg', 800, 1000))
                ->toMediaCollection(MediaCollectionEnum::Events->value);
        });
    }
}
