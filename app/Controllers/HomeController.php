<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Event;

final class HomeController
{
    public function index(): void
    {
        view('site/home', [
            'slides'   => Event::heroSlides(6),
            'events'   => Event::upcoming(null, 24),
            'counts'   => Event::upcomingCounts(),
        ]);
    }
}
