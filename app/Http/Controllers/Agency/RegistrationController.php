<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RegistrationController extends Controller
{
    public function show()
    {
        return view('stub', ['title' => 'Agency Access Registration', 'owner' => 'WS5']);
    }

    public function store(Request $request)
    {
        return redirect()->route('agency.pending');
    }

    public function pending()
    {
        return view('stub', ['title' => 'Agency Account Pending Review', 'owner' => 'WS5']);
    }
}
