@php
    use App\Enums\Location\RoomEnum;

    $steps = [
        ['title' => 'Melden', 'text' => 'Beschreib uns möglichst genau, was du verloren hast und wann du bei uns warst.'],
        ['title' => 'Wir suchen', 'text' => 'Wir gleichen deine Meldung mit den Fundsachen ab, die nach den Veranstaltungen abgegeben wurden.'],
        ['title' => 'Abholen', 'text' => 'Ist dein Gegenstand dabei, melden wir uns und vereinbaren mit dir die Abholung.'],
    ];
@endphp
<x-layouts.site title="Fundsachen" metaDescription="Etwas im Matrix Bochum verloren? Melde deine Fundsache über das Formular – wir schauen nach und melden uns.">
    <section class="page-hero">
        <div class="container">
            <p class="eyebrow">Fundsachen</p>
            <h1 class="page-title">Etwas verloren?</h1>
            <p class="page-lead">Jacke, Schlüssel, Handy oder Portemonnaie liegen geblieben? Melde es uns – wir schauen nach und melden uns bei dir.</p>
        </div>
    </section>

    <section class="section section-flush">
        <div class="container">
            <ol class="steps">
@foreach ($steps as $step)
                <li><strong>{{ $step['title'] }}</strong><p>{{ $step['text'] }}</p></li>
@endforeach
            </ol>
        </div>
    </section>

    <section class="section section-flush">
        <div class="container form-layout">
            <div class="form-card" id="formular">
@if (session('sent'))
                <x-forms.sent title="Meldung ist da" :again="route('lost-items')">Danke! Wir schauen nach und melden uns bei dir, sobald wir etwas wissen.</x-forms.sent>
@else
                <h2>Fundsache melden</h2>
                <p class="form-intro">Je genauer deine Beschreibung, desto schneller finden wir dein Teil.</p>
                <x-forms.summary />
                <form class="form-grid" method="post" action="{{ route('lost-items.store') }}" enctype="multipart/form-data" novalidate data-site-form>
                    @csrf
                    <x-forms.field name="item" label="Was hast du verloren?" required wide>
                        <input id="f-item" name="item" type="text" maxlength="150" required value="{{ old('item') }}" placeholder="z. B. Schwarze Lederjacke, Schlüsselbund, iPhone">
                    </x-forms.field>
                    <x-forms.field name="lost_on" label="Wann warst du da?" required>
                        <input id="f-lost_on" name="lost_on" type="date" required max="{{ today()->format('Y-m-d') }}" value="{{ old('lost_on') }}">
                    </x-forms.field>
                    <x-forms.field name="room" label="Wo ungefähr?">
                        <select id="f-room" name="room">
                            <option value="">Weiß ich nicht</option>
@foreach (RoomEnum::spaces() as $room)
                            <option value="{{ $room->value }}" @selected(old('room') === $room->value)>{{ $room->value }}</option>
@endforeach
                            <option value="Garderobe" @selected(old('room') === 'Garderobe')>Garderobe</option>
                            <option value="Toiletten" @selected(old('room') === 'Toiletten')>Toiletten</option>
                            <option value="Außenbereich" @selected(old('room') === 'Außenbereich')>Außenbereich / Parkplatz</option>
                        </select>
                    </x-forms.field>
                    <x-forms.field name="event" label="Welche Veranstaltung?" wide>
                        <input id="f-event" name="event" type="text" maxlength="150" value="{{ old('event') }}" placeholder="z. B. 90er Party, Konzert von …">
                    </x-forms.field>
                    <x-forms.field name="body" label="Beschreibung" required wide hint="Farbe, Marke, Größe, Inhalt, Anhänger, Hülle – alles, woran wir es erkennen.">
                        <textarea id="f-body" name="body" rows="5" maxlength="3000" required>{{ old('body') }}</textarea>
                    </x-forms.field>
                    <x-forms.field name="attachment" label="Foto (optional)" wide :hint="'JPG, PNG, WebP oder HEIC · max. '.(config('matrix.uploads.attachment_max_kilobytes') / 1024).' MB'">
                        <input id="f-attachment" class="form-file" name="attachment" type="file" accept="image/jpeg,image/png,image/webp,image/heic">
                    </x-forms.field>
                    <x-forms.person />
                    <x-forms.consent />
                    <div class="form-actions span-2">
                        <button class="btn btn-accent btn-lg" type="submit"><x-icon name="search" /> Fundsache melden</button>
                    </div>
                </form>
@endif
            </div>

            <aside class="form-aside">
                <div class="aside-card">
                    <h2>Gut zu wissen</h2>
                    <p>Bitte melde dich möglichst bald nach deinem Besuch – Fundsachen sammeln sich bei vollen Wochenenden schnell an.</p>
                    <p>Dringend, z. B. Schlüssel oder Ausweis? Ruf uns gern auch an:</p>
                    <x-site.contact-links />
                </div>
            </aside>
        </div>
    </section>
</x-layouts.site>
