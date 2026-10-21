@inject('describeDetails', 'App\Actions\Messages\DescribeMessageDetailsAction')
@php
    use App\Enums\MediaCollectionEnum;
    use App\Enums\Messages\MessageStatusEnum;

    $details = $describeDetails->execute($inquiry);
    $attachments = $inquiry->getMedia(MediaCollectionEnum::MessageAttachments->value);
    $status = old('status', $inquiry->status->value);
    $mailSubject = rawurlencode('Re: '.($inquiry->subject ?: $inquiry->type->label()));
@endphp
<x-layouts.admin :title="$inquiry->type->label().' von '.$inquiry->name">
    <a class="back-link" href="{{ route('admin.messages.index') }}"><x-icon name="arrow-left" /> Zurück zum Posteingang</a>

    <div class="message-layout">
        <div class="message-main">
            <article class="panel message-card type-{{ $inquiry->type->value }}">
                <header class="message-head">
                    <span class="pill pill-cat"><span class="dot"></span><x-icon :name="$inquiry->type->icon()" /> {{ $inquiry->type->label() }}</span>
                    <h2>{{ $inquiry->subject ?: $inquiry->type->label() }}</h2>
                    <p class="muted">Eingegangen am {{ de_date_short($inquiry->created_at) }} um {{ $inquiry->created_at->format('H:i') }} Uhr</p>
                </header>

                <ul class="sender">
                    <li><x-icon name="user" /> <strong>{{ $inquiry->name }}</strong></li>
                    <li><x-icon name="mail" /> <a href="mailto:{{ $inquiry->email }}?subject={{ $mailSubject }}">{{ $inquiry->email }}</a></li>
@if ($inquiry->phone)
                    <li><x-icon name="phone" /> <a href="tel:{{ phone_link($inquiry->phone) }}">{{ $inquiry->phone }}</a></li>
@endif
                </ul>

@if ($details !== [])
                <dl class="detail-list">
@foreach ($details as $label => $value)
                    <div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
@endforeach
                </dl>
@endif

@if (filled($inquiry->body))
                <div class="message-body">{{ format_text($inquiry->body) }}</div>
@endif

@if ($attachments->isNotEmpty())
                <div class="attachments">
                    <h3 class="panel-title">Anhang</h3>
@foreach ($attachments as $attachment)
                    <a class="attachment" href="{{ route('admin.messages.attachment', [$inquiry, $attachment->id]) }}">
                        <x-icon name="upload" />
                        <span><strong>{{ $attachment->file_name }}</strong><small class="muted">{{ $attachment->human_readable_size }}</small></span>
                    </a>
@endforeach
                </div>
@endif
            </article>

            <section class="panel" id="verlauf">
                <div class="panel-head"><h2>Antworten</h2></div>
@forelse ($inquiry->replies as $reply)
                <div class="reply">
                    <p class="reply-meta"><x-icon name="check" /> {{ $reply->author?->name ?? 'Gelöschter Benutzer' }} · {{ de_date_short($reply->created_at) }}, {{ $reply->created_at->format('H:i') }} Uhr · an {{ $inquiry->email }}</p>
                    <div class="reply-body">{{ format_text($reply->body) }}</div>
                </div>
@empty
                <p class="muted">Noch nicht beantwortet.</p>
@endforelse

                <form class="reply-form" method="post" action="{{ route('admin.messages.replies.store', $inquiry) }}">
                    @csrf
                    <div @class(['field', 'has-error' => $errors->has('body')])>
                        <label for="reply-body">Antwort an {{ $inquiry->name }}</label>
                        <textarea id="reply-body" name="body" rows="7" required placeholder="Deine Antwort …">{{ old('body') }}</textarea>
                        <x-admin.field-error name="body" />
                        <p class="hint">Geht per E-Mail an {{ $inquiry->email }}. „Hallo {{ $inquiry->name }},“, Grußformel und die ursprüngliche Nachricht werden automatisch ergänzt.</p>
                    </div>
                    <button class="btn btn-accent" type="submit"><x-icon name="mail" /> Antwort senden</button>
                </form>
            </section>
        </div>

        <aside class="message-side">
            <form class="panel" method="post" action="{{ route('admin.messages.update', $inquiry) }}">
                @csrf
                @method('PUT')
                <h2 class="panel-title">Bearbeitung</h2>
                <div class="status-picker" role="radiogroup" aria-label="Status">
@foreach (MessageStatusEnum::cases() as $option)
                    <label class="status-{{ $option->value }}">
                        <input type="radio" name="status" value="{{ $option->value }}" @checked($status === $option->value)>
                        <span><x-icon :name="$option->icon()" /> {{ $option->label() }}</span>
                    </label>
@endforeach
                </div>
                <div @class(['field', 'has-error' => $errors->has('admin_note')])>
                    <label for="admin_note">Interne Notiz</label>
                    <textarea id="admin_note" name="admin_note" rows="4" placeholder="Nur für das Team sichtbar, z. B. „Jacke liegt im Büro, Fach 3“">{{ old('admin_note', $inquiry->admin_note) }}</textarea>
                    <x-admin.field-error name="admin_note" />
                </div>
                <button class="btn btn-accent btn-block" type="submit"><x-icon name="check" /> Speichern</button>
            </form>

            <section class="panel">
                <h2 class="panel-title">Aktionen</h2>
                <div class="side-actions">
                    <a class="btn btn-block" href="mailto:{{ $inquiry->email }}?subject={{ $mailSubject }}"><x-icon name="external" /> Im Mailprogramm antworten</a>
                    <form method="post" action="{{ route('admin.messages.unread', $inquiry) }}">
                        @csrf
                        <button class="btn btn-block" type="submit"><x-icon name="eye-off" /> Als ungelesen markieren</button>
                    </form>
                </div>
            </section>

            <form class="danger-zone danger-zone-side" method="post" action="{{ route('admin.messages.destroy', $inquiry) }}" data-confirm="Nachricht von {{ $inquiry->name }} samt Anhängen endgültig löschen?">
                @csrf
                @method('DELETE')
                <div>
                    <strong>Nachricht löschen</strong>
                    <p class="muted">Entfernt Nachricht, Antworten und Anhänge dauerhaft.</p>
                </div>
                <button class="btn btn-danger" type="submit"><x-icon name="trash" /> Löschen</button>
            </form>
        </aside>
    </div>
</x-layouts.admin>
