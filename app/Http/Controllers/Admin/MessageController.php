<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Messages\CountOpenMessagesAction;
use App\Actions\Messages\ListAdminMessagesAction;
use App\Enums\Messages\MessageListFilterEnum;
use App\Enums\Messages\MessageTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MessageRequest;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MessageController extends Controller
{
    public function index(Request $request, ListAdminMessagesAction $listMessages, CountOpenMessagesAction $countOpenMessages): View
    {
        $filter = $request->enum('ansicht', MessageListFilterEnum::class) ?? MessageListFilterEnum::Open;
        $type = $request->enum('art', MessageTypeEnum::class);
        $search = trim((string) $request->query('q'));

        return view('admin.messages.index', [
            'messages' => $listMessages->execute($filter, $type, $search),
            'counts' => $countOpenMessages->execute(),
            'filter' => $filter,
            'type' => $type,
            'search' => $search,
        ]);
    }

    public function show(Message $message): View
    {
        if ($message->isUnread()) {
            $message->update(['read_at' => now()]);
        }

        // In Views ist $message durch @error belegt – daher $inquiry
        return view('admin.messages.show', [
            'inquiry' => $message->load(['replies.author', 'media']),
        ]);
    }

    public function update(MessageRequest $request, Message $message): RedirectResponse
    {
        $message->update($request->validated());

        return back(fallback: route('admin.messages.show', $message))
            ->with('success', 'Nachricht von '.$message->name.' ist jetzt „'.$message->status->label().'“.');
    }

    public function markUnread(Message $message): RedirectResponse
    {
        $message->update(['read_at' => null]);

        return to_route('admin.messages.index')->with('success', 'Nachricht von '.$message->name.' als ungelesen markiert.');
    }

    public function destroy(Message $message): RedirectResponse
    {
        $message->delete();

        return to_route('admin.messages.index')->with('success', 'Nachricht von '.$message->name.' wurde gelöscht.');
    }

    /**
     * Anhang (Lebenslauf, Foto) von der privaten Disk herunterladen.
     */
    public function attachment(Message $message, int $media): Media
    {
        return $message->media()->findOrFail($media);
    }
}
