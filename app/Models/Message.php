<?php

namespace App\Models;

use App\Enums\MediaCollectionEnum;
use App\Enums\Messages\MessageStatusEnum;
use App\Enums\Messages\MessageTypeEnum;
use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Nachricht aus einem Formular der Website (Kontakt, Fundsache, Bewerbung, Firmenanfrage).
 */
#[Fillable([
    'type', 'status', 'name', 'email', 'phone', 'subject', 'body', 'details', 'admin_note', 'read_at',
])]
class Message extends Model implements HasMedia
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory, InteractsWithMedia;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MessageTypeEnum::class,
            'status' => MessageStatusEnum::class,
            'details' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaCollectionEnum::MessageAttachments->value)
            ->useDisk(config('matrix.uploads.private_disk'));
    }

    /**
     * @return HasMany<MessageReply, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(MessageReply::class)->oldest();
    }

    /**
     * Neue und angefangene Nachrichten.
     */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->where('status', '!=', MessageStatusEnum::Done);
    }

    /**
     * Noch von niemandem geöffnete Nachrichten.
     */
    #[Scope]
    protected function unread(Builder $query): void
    {
        $query->whereNull('read_at');
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function isDone(): bool
    {
        return $this->status === MessageStatusEnum::Done;
    }
}
