<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GlobalSearchService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GlobalSearchController extends Controller
{
    public function search(Request $request, GlobalSearchService $searchService): View
    {
        $q = (string) $request->input('q', '');
        $results = $searchService->search($q, $request->user());

        $totalFound = count($results['reports'])
            + count($results['referrals'])
            + count($results['cases'])
            + count($results['parties'])
            + count($results['personnel']);

        return view('admin.search', compact('q', 'results', 'totalFound'));
    }
}
