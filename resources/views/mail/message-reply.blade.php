Hallo {!! $inquiry->name !!},

{!! $reply->body !!}

Viele Grüße
{{ $reply->author?->name ? $reply->author->name.' – ' : '' }}{{ config('matrix.venue.name') }}
{{ config('matrix.venue.street') }}, {{ config('matrix.venue.zip') }} {{ config('matrix.venue.city') }}
{{ config('matrix.venue.phone') }} · {{ config('app.url') }}

----------------------------------------
Deine Nachricht vom {{ $inquiry->created_at->format('d.m.Y, H:i') }} Uhr:

{!! $inquiry->body !!}
