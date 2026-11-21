<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        return view('stub', ['title' => 'User & Staff Accounts', 'owner' => 'WS2']);
    }

    public function create()
    {
        return view('stub', ['title' => 'Create User Account', 'owner' => 'WS2']);
    }

    public function store(Request $request)
    {
        return redirect()->route('admin.users.index')->with('success', 'User account created.');
    }

    public function edit(User $user)
    {
        return view('stub', ['title' => "Edit User: {$user->name}", 'owner' => 'WS2']);
    }

    public function update(Request $request, User $user)
    {
        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    public function deactivate(User $user)
    {
        return redirect()->route('admin.users.index')->with('success', 'User account status changed.');
    }

    public function resetPassword(User $user)
    {
        return redirect()->route('admin.users.index')->with('success', 'Temporary password generated.');
    }
}
