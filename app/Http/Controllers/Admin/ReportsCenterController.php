<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportsCenterController extends Controller
{
    public function index()
    {
        return view('stub', ['title' => 'Reports Center', 'owner' => 'WS2']);
    }

    public function show(string $key)
    {
        return view('stub', ['title' => "Report: {$key}", 'owner' => 'WS2']);
    }

    public function export(Request $request, string $key)
    {
        return response('stub-export-content', 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"report_{$key}.csv\"",
        ]);
    }

    public function history()
    {
        return view('stub', ['title' => 'Generated Reports History', 'owner' => 'WS2']);
    }
}
