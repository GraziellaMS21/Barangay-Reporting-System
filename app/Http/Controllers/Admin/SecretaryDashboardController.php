<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class SecretaryDashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.case-actions.placeholder', [
            'title' => 'Secretary case and conciliation dashboard',
            'message' => 'The dashboard is not available yet. Hearing, summons-service, and deadline reminders are not being calculated.',
        ]);
    }
}
