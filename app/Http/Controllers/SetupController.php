<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SetupController extends Controller
{
    public function show()
    {
        return view('stub', ['title' => 'Setup Wizard', 'owner' => 'WS1']);
    }

    public function step(string $step)
    {
        return view('stub', ['title' => "Setup Wizard Step: {$step}", 'owner' => 'WS1']);
    }

    public function store(Request $request, string $step)
    {
        return redirect()->route('setup.step', ['step' => $step]);
    }

    public function finish(Request $request)
    {
        return redirect()->route('admin.dashboard');
    }
}
