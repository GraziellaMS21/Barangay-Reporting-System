<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purok;
use App\Models\Report;
use Illuminate\View\View;

class MapController extends Controller
{
    public function index(): View
    {
        $reports = Report::query()->with('purok', 'category')->latest()->get();
        $puroks = Purok::orderBy('name')->get();

        return view('admin.map', compact('reports', 'puroks'));
    }
}
