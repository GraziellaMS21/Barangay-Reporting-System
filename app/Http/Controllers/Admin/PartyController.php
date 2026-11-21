<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Party;
use Illuminate\Http\Request;

class PartyController extends Controller
{
    public function index()
    {
        return view('stub', ['title' => 'Case Parties Directory', 'owner' => 'WS3']);
    }

    public function search(Request $request)
    {
        return response()->json(['parties' => []]);
    }

    public function show(Party $party)
    {
        return view('stub', ['title' => "Party Profile: {$party->full_name}", 'owner' => 'WS3']);
    }

    public function edit(Party $party)
    {
        return view('stub', ['title' => "Edit Party: {$party->full_name}", 'owner' => 'WS3']);
    }

    public function update(Request $request, Party $party)
    {
        return redirect()->route('admin.parties.show', $party)->with('success', 'Party updated.');
    }

    public function mergeCreate(Party $party)
    {
        return view('stub', ['title' => "Merge Party: {$party->full_name}", 'owner' => 'WS3']);
    }

    public function mergeStore(Request $request, Party $party)
    {
        return redirect()->route('admin.parties.show', $party)->with('success', 'Parties merged.');
    }
}
