<?php

namespace App\Models;

use App\Enums\Events\EventCategoryEnum;
use App\Enums\Events\EventStatusEnum;
use App\Enums\Events\ImageFocusEnum;
use App\Enums\Events\TicketStatusEnum;
use App\Enums\MediaCollectionEnum;
use App\Enums\MediaConversionEnum;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Veranstaltung (Konzert, Party oder Sonstiges).
 */
#[Fillable([
    'title', 'slug', 'subtitle', 'category', 'category_label', 'description',
    'starts_at', 'doors_at', 'ends_at', 'rooms', 'organizer_id', 'image_focus',
    'ticket_url', 'social_links', 'price_presale', 'price_box', 'age_rating',
    'ticket_status', 'status', 'publish_at', 'is_featured', 'created_by',
])]
class Event extends Model implements HasMedia
{
    /** @use HasFactory<EventFactory> */
    use HasFactory, InteractsWithMedia;

    /**
     * Ohne Endzeit gilt ein Event so viele Stunden nach Beginn als vorbei.
     */
    public const DEFAULT_DURATION_HOURS = 6;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => EventCategoryEnum::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'rooms' => 'array',
            'social_links' => 'array',
            'image_focus' => ImageFocusEnum::class,
            'ticket_status' => TicketStatusEnum::class,
            'status' => EventStatusEnum::class,
            'publish_at' => 'datetime',
            'is_featured' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaCollectionEnum::Events->value)
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        foreach (MediaConversionEnum::cases() as $conversion) {
            $this->addMediaConversion($conversion->value)
                ->fit(Fit::Max, $conversion->width(), $conversion->width() * 4)
                ->format('webp')
                ->quality(82)
                ->nonQueued();
        }
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Externer Veranstalter – ohne Eintrag veranstaltet die Matrix selbst.
     *
     * @return BelongsTo<Organizer, $this>
     */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    /**
     * Nur Veranstaltungen, die auf der Website stehen – geplante erst ab ihrem Veröffentlichungszeitpunkt.
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', EventStatusEnum::Published)
            ->where(fn (Builder $query) => $query
                ->whereNull('publish_at')
                ->orWhere('publish_at', '<=', now()));
    }

    /**
     * Veröffentlicht, aber der Veröffentlichungszeitpunkt liegt noch in der Zukunft.
     */
    #[Scope]
    protected function scheduled(Builder $query): void
    {
        $query->where('status', EventStatusEnum::Published)->where('publish_at', '>', now());
    }

    /**
     * Veranstaltungen, die noch nicht vorbei sind (ohne Endzeit: 6 Stunden nach Beginn).
     */
    #[Scope]
    protected function upcoming(Builder $query): void
    {
        $query->where(fn (Builder $query) => $query
            ->where('ends_at', '>=', now())
            ->orWhere(fn (Builder $query) => $query
                ->whereNull('ends_at')
                ->where('starts_at', '>=', now()->subHours(self::DEFAULT_DURATION_HOURS))));
    }

    /**
     * Veranstaltungen, die vorbei sind.
     */
    #[Scope]
    protected function past(Builder $query): void
    {
        $query->where(fn (Builder $query) => $query
            ->where('ends_at', '<', now())
            ->orWhere(fn (Builder $query) => $query
                ->whereNull('ends_at')
                ->where('starts_at', '<', now()->subHours(self::DEFAULT_DURATION_HOURS))));
    }

    public function isDraft(): bool
    {
        return $this->status === EventStatusEnum::Draft;
    }

    public function isScheduled(): bool
    {
        return $this->status === EventStatusEnum::Published && $this->publish_at?->isFuture() === true;
    }

    /**
     * Für Besucher sichtbar – weder Entwurf noch erst später geplant.
     */
    public function isOnline(): bool
    {
        return ! $this->isDraft() && ! $this->isScheduled();
    }

    public function isPast(): bool
    {
        return ($this->ends_at ?? $this->starts_at->copy()->addHours(self::DEFAULT_DURATION_HOURS))->isPast();
    }

    public function isCancelled(): bool
    {
        return $this->ticket_status->isCancelled();
    }

    public function canBuyTickets(): bool
    {
        return filled($this->ticket_url) && $this->ticket_status->allowsTicketSale();
    }
}
