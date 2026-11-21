<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class SecretaryDashboardController extends Controller
{
    public function index()
    {
        return view('stub', ['title' => 'Secretary Case & Conciliation Dashboard', 'owner' => 'WS4']);
    }
}
