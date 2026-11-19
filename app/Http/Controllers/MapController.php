<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Purok;
use App\Models\Report;
use Illuminate\View\View;

class MapController extends Controller
{
    public function index(): View
    {
        $reports = Report::query()
            ->select(['id', 'tracking_code', 'category_id', 'purok_id', 'status', 'lat', 'lng', 'is_emergency', 'created_at'])
            ->with(['purok:id,name', 'category:id,name,slug'])
            ->where(function ($query) {
                $query->where('is_emergency', false)
                    ->orWhereNull('is_emergency');
            })
            ->where('status', '!=', 'emergency_flagged')
            ->latest()
            ->get();

        $puroks = Purok::orderBy('name')->get(['id', 'name']);
        $categories = Category::where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug']);

        return view('resident.map', compact('reports', 'puroks', 'categories'));
    }
}
