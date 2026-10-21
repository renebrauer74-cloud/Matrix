<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Models\Event;

final class DashboardController
{
    public function index(): void
    {
        view('admin/dashboard', [
            'title'  => 'Dashboard',
            'stats'  => Event::stats(),
            'next'   => array_slice(Event::adminList('upcoming'), 0, 6),
        ], 'layouts/admin');
    }
}
