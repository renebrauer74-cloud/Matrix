<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-brand">
            <img src="{{ asset('assets/img/logo.png') }}" alt="Matrix Bochum" width="110" height="53" loading="lazy">
            <p>Matrix &amp; Rockpalast Bochum – zwei Clubs, Live- &amp; Eventlocations im historischen Müser-Turm.</p>
            <div class="footer-social">
@if (config('matrix.venue.instagram'))
                <a href="{{ config('matrix.venue.instagram') }}" target="_blank" rel="noopener" aria-label="Instagram"><x-icon name="instagram" /></a>
@endif
@if (config('matrix.venue.facebook'))
                <a href="{{ config('matrix.venue.facebook') }}" target="_blank" rel="noopener" aria-label="Facebook"><x-icon name="facebook" /></a>
@endif
            </div>
        </div>
        <div>
            <h2 class="footer-title">Adresse</h2>
            <address>
                {{ config('matrix.venue.name') }}<br>
                {{ config('matrix.venue.street') }}<br>
                {{ config('matrix.venue.zip') }} {{ config('matrix.venue.city') }}
            </address>
            <a class="footer-link" href="{{ config('matrix.venue.route_url') }}" target="_blank" rel="noopener"><x-icon name="route" /> Route planen</a>
        </div>
        <div>
            <h2 class="footer-title">Kontakt</h2>
            <x-site.contact-links />
            <a class="footer-link" href="{{ config('matrix.venue.tickets_url') }}" target="_blank" rel="noopener"><x-icon name="ticket" /> Ticketshop</a>
        </div>
        <div>
            <h2 class="footer-title">Entdecken</h2>
            <ul class="footer-nav">
                <li><a href="{{ route('events.index') }}">Programm</a></li>
                <li><a href="{{ route('events.index', 'konzerte') }}">Konzerte</a></li>
                <li><a href="{{ route('events.index', 'partys') }}">Partys</a></li>
                <li><a href="{{ route('location') }}">Location</a></li>
                <li><a href="{{ route('corporate') }}">Firmenfeiern &amp; Events</a></li>
                <li><a href="{{ route('jobs') }}">Jobs</a></li>
                <li><a href="{{ route('lost-items') }}">Fundsachen</a></li>
                <li><a href="{{ route('contact') }}">Kontakt</a></li>
            </ul>
        </div>
    </div>
    <div class="container footer-bottom">
        <span>© {{ now()->year }} Matrix Bochum</span>
        <nav aria-label="Rechtliches">
            <a href="{{ route('imprint') }}">Impressum</a>
            <a href="{{ route('privacy') }}">Datenschutz</a>
        </nav>
    </div>
</footer>
