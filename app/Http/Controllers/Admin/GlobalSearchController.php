<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GlobalSearchService;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function search(Request $request, GlobalSearchService $searchService)
    {
        $q = (string) $request->input('q', '');
        $results = $searchService->search($q, $request->user());

        return view('stub', [
            'title' => "Global Search: {$q}",
            'owner' => 'WS1',
            'results' => $results,
        ]);
    }
}
