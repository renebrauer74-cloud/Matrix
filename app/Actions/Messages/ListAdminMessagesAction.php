<?php

namespace App\Actions\Messages;

use App\Enums\Messages\MessageListFilterEnum;
use App\Enums\Messages\MessageStatusEnum;
use App\Enums\Messages\MessageTypeEnum;
use App\Models\Message;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Posteingang im Admin-Bereich mit Status-Reiter, Art und Suche – neueste zuerst.
 */
class ListAdminMessagesAction
{
    /**
     * @return LengthAwarePaginator<int, Message>
     */
    public function execute(MessageListFilterEnum $filter, ?MessageTypeEnum $type = null, ?string $search = null): LengthAwarePaginator
    {
        $search = trim((string) $search);

        return Message::query()
            ->withCount(['replies', 'media'])
            ->when($filter === MessageListFilterEnum::Open, fn (Builder $query) => $query->open())
            ->when($filter === MessageListFilterEnum::Done, fn (Builder $query) => $query->where('status', MessageStatusEnum::Done))
            ->when($type, fn (Builder $query) => $query->where('type', $type))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(fn (Builder $query) => $query
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('subject', 'like', $like)
                    ->orWhere('body', 'like', $like)
                    ->orWhere('details', 'like', $like));
            })
            ->latest()
            ->latest('id')
            ->paginate(config('matrix.inbox.per_page'))
            ->withQueryString();
    }
}
