@php
    use App\Enums\Messages\ContactTopicEnum;

    $shortcuts = [
        ['url' => route('corporate'), 'icon' => 'capacity', 'title' => 'Firmenfeier oder Event planen', 'text' => 'Location mieten – mit Raumvorschlag und Angebot.'],
        ['url' => route('lost-items'), 'icon' => 'search', 'title' => 'Etwas verloren?', 'text' => 'Fundsache melden – wir schauen nach.'],
        ['url' => route('jobs'), 'icon' => 'user', 'title' => 'Bei uns arbeiten', 'text' => 'Theke, Garderobe, Kasse – jetzt bewerben.'],
    ];
@endphp
<x-layouts.site title="Kontakt" metaDescription="Kontakt zum Matrix Bochum: Fragen zu Veranstaltungen, Tickets, Booking oder Presse – schreib uns über das Kontaktformular.">
    <section class="page-hero">
        <div class="container">
            <p class="eyebrow">Kontakt</p>
            <h1 class="page-title">Schreib uns</h1>
            <p class="page-lead">Fragen zu Veranstaltungen, Tickets, Booking oder Presse? Schick uns eine Nachricht – wir melden uns so schnell wie möglich.</p>
        </div>
    </section>

    <section class="section section-flush">
        <div class="container form-layout">
            <div class="form-card" id="formular">
@if (session('sent'))
                <x-forms.sent title="Nachricht angekommen" :again="route('contact')">Danke für deine Nachricht! Wir melden uns per E-Mail bei dir.</x-forms.sent>
@else
                <h2>Kontaktformular</h2>
                <p class="form-intro">Felder mit <span class="req">*</span> brauchen wir, um dir antworten zu können.</p>
                <x-forms.summary />
                <form class="form-grid" method="post" action="{{ route('contact.store') }}" novalidate data-site-form>
                    @csrf
                    <x-forms.person />
                    <x-forms.field name="topic" label="Thema" required>
                        <select id="f-topic" name="topic" required>
                            <option value="">Bitte wählen …</option>
@foreach (ContactTopicEnum::cases() as $topic)
                            <option value="{{ $topic->value }}" @selected(old('topic') === $topic->value)>{{ $topic->label() }}</option>
@endforeach
                        </select>
                    </x-forms.field>
                    <x-forms.field name="subject" label="Betreff" wide>
                        <input id="f-subject" name="subject" type="text" maxlength="200" value="{{ old('subject') }}" placeholder="z. B. Frage zum Konzert am Samstag">
                    </x-forms.field>
                    <x-forms.field name="body" label="Deine Nachricht" required wide>
                        <textarea id="f-body" name="body" rows="7" maxlength="5000" required>{{ old('body') }}</textarea>
                    </x-forms.field>
                    <x-forms.consent />
                    <div class="form-actions span-2">
                        <button class="btn btn-accent btn-lg" type="submit"><x-icon name="mail" /> Nachricht senden</button>
                    </div>
                </form>
@endif
            </div>

            <aside class="form-aside">
                <div class="aside-card">
                    <h2>So erreichst du uns</h2>
                    <address class="aside-address">
                        {{ config('matrix.venue.name') }}<br>
                        {{ config('matrix.venue.street') }}<br>
                        {{ config('matrix.venue.zip') }} {{ config('matrix.venue.city') }}
                    </address>
                    <x-site.contact-links />
                    <a class="footer-link" href="{{ route('directions') }}"><x-icon name="route" /> Anfahrt</a>
                </div>
                <nav class="shortcut-list" aria-label="Weitere Anliegen">
@foreach ($shortcuts as $shortcut)
                    <a class="shortcut" href="{{ $shortcut['url'] }}">
                        <x-icon :name="$shortcut['icon']" />
                        <span><strong>{{ $shortcut['title'] }}</strong><small>{{ $shortcut['text'] }}</small></span>
                        <x-icon name="arrow-right" class="icon shortcut-arrow" />
                    </a>
@endforeach
                </nav>
            </aside>
        </div>
    </section>
</x-layouts.site>
