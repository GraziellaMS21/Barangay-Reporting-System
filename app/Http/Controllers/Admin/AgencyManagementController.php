<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgencyRegistration;
use Illuminate\Http\Request;

class AgencyManagementController extends Controller
{
    public function index()
    {
        return view('stub', ['title' => 'Partner Agencies & Access Requests', 'owner' => 'WS5']);
    }

    public function show(AgencyRegistration $registration)
    {
        return view('stub', ['title' => "Agency Registration: {$registration->agency_name_claimed}", 'owner' => 'WS5']);
    }

    public function approve(Request $request, AgencyRegistration $registration)
    {
        return redirect()->route('admin.agencies.index')->with('success', 'Agency registration approved.');
    }

    public function reject(Request $request, AgencyRegistration $registration)
    {
        return redirect()->route('admin.agencies.index')->with('success', 'Agency registration rejected.');
    }

    public function suspend(Request $request, AgencyRegistration $registration)
    {
        return redirect()->route('admin.agencies.index')->with('success', 'Agency account suspended.');
    }

    public function reinstate(Request $request, AgencyRegistration $registration)
    {
        return redirect()->route('admin.agencies.index')->with('success', 'Agency account reinstated.');
    }

    public function document(AgencyRegistration $registration)
    {
        return response('stub-agency-doc', 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="agency_id.pdf"',
        ]);
    }
}
