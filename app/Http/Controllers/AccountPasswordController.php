<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AccountPasswordController extends Controller
{
    public function edit()
    {
        return view('stub', ['title' => 'Update Account Password', 'owner' => 'WS2']);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->update(['must_change_password' => false]);
        }

        return redirect()->route('admin.dashboard')->with('success', 'Password updated successfully.');
    }
}
