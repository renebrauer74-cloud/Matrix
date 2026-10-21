<?php

namespace App\Actions\Messages;

use App\Enums\MediaCollectionEnum;
use App\Enums\Messages\MessageStatusEnum;
use App\Enums\Messages\MessageTypeEnum;
use App\Models\Message;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Legt eine Nachricht aus einem Website-Formular im Posteingang ab – samt Anhang.
 */
class ReceiveMessageAction
{
    /**
     * @param  array<string, mixed>  $attributes  Spalten aus SiteFormRequest::messageAttributes()
     */
    public function execute(MessageTypeEnum $type, array $attributes, ?UploadedFile $attachment = null): Message
    {
        return DB::transaction(function () use ($type, $attributes, $attachment): Message {
            $message = Message::query()->create([
                ...$attributes,
                'type' => $type,
                'status' => MessageStatusEnum::New,
            ]);

            if ($attachment !== null) {
                $message->addMedia($attachment)->toMediaCollection(MediaCollectionEnum::MessageAttachments->value);
            }

            return $message;
        });
    }
}
