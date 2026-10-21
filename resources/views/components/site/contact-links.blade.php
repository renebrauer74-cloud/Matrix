@if (config('matrix.venue.phone'))
<a class="footer-link" href="tel:{{ phone_link(config('matrix.venue.phone')) }}"><x-icon name="phone" /> {{ config('matrix.venue.phone') }}</a>
@endif
<a class="footer-link" href="mailto:{{ config('matrix.venue.email') }}"><x-icon name="mail" /> {{ config('matrix.venue.email') }}</a>
