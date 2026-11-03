<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\View\View;

class MapController extends Controller
{
    public function index(): View
    {
        $reports = Report::query()
            ->select(['id', 'category_id', 'purok_id', 'status', 'lat', 'lng', 'is_emergency'])
            ->with(['purok:id,name', 'category:id,name,slug'])
            ->where(function ($query) {
                $query->where('is_emergency', false)
                    ->orWhereNull('is_emergency');
            })
            ->where('status', '!=', 'emergency_flagged')
            ->latest()
            ->get();

        return view('resident.map', compact('reports'));
    }
}
