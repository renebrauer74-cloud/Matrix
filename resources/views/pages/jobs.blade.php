@php
    use App\Http\Requests\Site\ApplicationRequest;

    $content = $page->content;
    $benefits = text_lines($content['benefits'] ?? null);
    $position = old('position', $selectedPosition);
@endphp
<x-layouts.site title="Jobs" metaDescription="Jobs im Matrix Bochum: Wir suchen Servicekräfte für Theke, Garderobe und Kasse. Jetzt online bewerben – auch initiativ.">
    <x-site.page-hero-media :page="$page" eyebrow="Jobs" fallback-image="aussen-2.jpg">
        <a class="btn btn-accent btn-lg" href="#bewerben"><x-icon name="arrow-right" /> Jetzt bewerben</a>
@if ($positions->isNotEmpty())
        <a class="btn btn-outline btn-lg" href="#stellen">Offene Stellen</a>
@endif
    </x-site.page-hero-media>

    <section class="section">
        <div class="container split split-text">
            <div class="split-copy">
@if (filled($content['intro_title']))
                <p class="eyebrow">Warum Matrix</p>
                <h2 class="section-title">{{ $content['intro_title'] }}</h2>
@endif
                <div class="prose">{{ format_text($content['intro_text']) }}</div>
            </div>
@if ($benefits !== [])
            <ul class="check-list">
@foreach ($benefits as $benefit)
                <li><x-icon name="check" /> {{ $benefit }}</li>
@endforeach
            </ul>
@endif
        </div>
    </section>

@if ($positions->isNotEmpty())
    <section class="section section-flush" id="stellen">
        <div class="container">
            <div class="section-head">
                <div>
                    <p class="eyebrow">Offene Stellen</p>
                    <h2 class="section-title">Wir suchen dich</h2>
                </div>
            </div>
            <div class="job-grid">
@foreach ($positions as $job)
                <article class="job-card">
                    <header>
@if ($job->employment)
                        <span class="job-tag">{{ $job->employment }}</span>
@endif
                        <h3>{{ $job->title }}</h3>
@if ($job->summary)
                        <p>{{ $job->summary }}</p>
@endif
                    </header>
@if (text_lines($job->description) !== [])
                    <h4>Deine Aufgaben</h4>
                    <ul>
@foreach (text_lines($job->description) as $line)
                        <li>{{ $line }}</li>
@endforeach
                    </ul>
@endif
@if (text_lines($job->requirements) !== [])
                    <h4>Das bringst du mit</h4>
                    <ul>
@foreach (text_lines($job->requirements) as $line)
                        <li>{{ $line }}</li>
@endforeach
                    </ul>
@endif
                    <a class="btn btn-accent job-apply" href="{{ route('jobs', ['stelle' => $job->id]) }}#bewerben">Jetzt bewerben <x-icon name="arrow-right" /></a>
                </article>
@endforeach
                <article class="job-card job-card-open">
                    <header>
                        <span class="job-tag">Initiativ</span>
                        <h3>Nichts Passendes dabei?</h3>
                        <p>Du kannst etwas anderes richtig gut – Technik, Security, Promotion, Deko? Erzähl uns davon.</p>
                    </header>
                    <a class="btn btn-outline job-apply" href="{{ route('jobs', ['stelle' => ApplicationRequest::INITIATIVE]) }}#bewerben">Initiativ bewerben <x-icon name="arrow-right" /></a>
                </article>
            </div>
        </div>
    </section>
@endif

    <section class="section">
        <div class="container form-layout">
            <div class="form-card" id="bewerben">
@if (session('sent'))
                <x-forms.sent title="Bewerbung ist da" :again="route('jobs')">Danke für deine Bewerbung! Wir schauen sie uns an und melden uns bei dir.</x-forms.sent>
@else
                <h2>Bewerbung</h2>
@if (filled($content['apply_text']))
                <p class="form-intro">{{ $content['apply_text'] }}</p>
@endif
                <x-forms.summary />
                <form class="form-grid" method="post" action="{{ route('jobs.store') }}" enctype="multipart/form-data" novalidate data-site-form>
                    @csrf
                    <x-forms.field name="position" label="Stelle" required wide>
                        <select id="f-position" name="position" required>
                            <option value="">Bitte wählen …</option>
@foreach ($positions as $job)
                            <option value="{{ $job->id }}" @selected((string) $position === (string) $job->id)>{{ $job->title }}</option>
@endforeach
                            <option value="{{ ApplicationRequest::INITIATIVE }}" @selected($position === ApplicationRequest::INITIATIVE)>Initiativbewerbung</option>
                        </select>
                    </x-forms.field>
                    <x-forms.person require-phone phone-hint="Am liebsten rufen wir kurz an." />
                    <x-forms.field name="start" label="Ab wann kannst du anfangen?">
                        <input id="f-start" name="start" type="text" maxlength="80" value="{{ old('start') }}" placeholder="z. B. ab sofort, ab 1. Dezember">
                    </x-forms.field>
                    <x-forms.field name="availability" label="Wann hast du Zeit?" required wide hint="Welche Tage, wie oft im Monat, auch unter der Woche?">
                        <input id="f-availability" name="availability" type="text" maxlength="500" required value="{{ old('availability') }}" placeholder="z. B. Freitag und Samstag, 2–3 Schichten im Monat">
                    </x-forms.field>
                    <x-forms.field name="experience" label="Erfahrung" wide>
                        <textarea id="f-experience" name="experience" rows="3" maxlength="2000" placeholder="Hast du schon in Gastro, an einer Kasse oder bei Events gearbeitet? Kein Muss!">{{ old('experience') }}</textarea>
                    </x-forms.field>
                    <x-forms.field name="body" label="Ein paar Worte über dich" wide>
                        <textarea id="f-body" name="body" rows="5" maxlength="5000">{{ old('body') }}</textarea>
                    </x-forms.field>
                    <x-forms.field name="attachment" label="Lebenslauf / Unterlagen (optional)" wide :hint="'PDF, Word, JPG oder PNG · max. '.(config('matrix.uploads.attachment_max_kilobytes') / 1024).' MB'">
                        <input id="f-attachment" class="form-file" name="attachment" type="file" accept=".pdf,.doc,.docx,image/jpeg,image/png">
                    </x-forms.field>
                    <div @class(['form-field', 'span-2', 'has-error' => $errors->has('is_adult')])>
                        <label class="form-check">
                            <input type="checkbox" name="is_adult" value="1" required @checked(old('is_adult'))>
                            <span>Ich bin mindestens 18 Jahre alt. <span class="req" aria-hidden="true">*</span></span>
                        </label>
                        <x-forms.error name="is_adult" />
                    </div>
                    <x-forms.consent />
                    <div class="form-actions span-2">
                        <button class="btn btn-accent btn-lg" type="submit"><x-icon name="arrow-right" /> Bewerbung abschicken</button>
                    </div>
                </form>
@endif
            </div>

            <aside class="form-aside">
                <div class="aside-card">
                    <h2>Lieber per Mail?</h2>
                    <p>Du kannst deine Bewerbung auch direkt an uns schicken:</p>
                    <a class="footer-link" href="mailto:{{ config('matrix.venue.jobs_email') }}?subject=Bewerbung"><x-icon name="mail" /> {{ config('matrix.venue.jobs_email') }}</a>
                </div>
            </aside>
        </div>
    </section>
</x-layouts.site>
