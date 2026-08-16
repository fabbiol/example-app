<?php

namespace App\Http\Controllers;

use App\Actions\BuildOperationalDashboard;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(BuildOperationalDashboard $dashboard): Response
    {
        return Inertia::render('dashboard', $dashboard->handle());
    }
}
