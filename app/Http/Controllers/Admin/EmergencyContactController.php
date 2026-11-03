<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmergencyContact;
use App\Services\EmergencyContactService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmergencyContactController extends Controller
{
    public function index(): View
    {
        return view('admin.settings.emergency-contacts.index', [
            'contacts' => EmergencyContact::query()->ordered()->get(),
        ]);
    }

    public function store(Request $request, EmergencyContactService $contactService): RedirectResponse
    {
        $contactService->create($this->validatedAttributes($request), $request->user());

        return redirect()
            ->route('admin.settings.emergency-contacts.index')
            ->with('success', 'Emergency contact added.');
    }

    public function edit(EmergencyContact $emergencyContact): View
    {
        return view('admin.settings.emergency-contacts.edit', [
            'contact' => $emergencyContact,
        ]);
    }

    public function update(
        Request $request,
        EmergencyContact $emergencyContact,
        EmergencyContactService $contactService
    ): RedirectResponse {
        $contactService->update($emergencyContact, $this->validatedAttributes($request), $request->user());

        return redirect()
            ->route('admin.settings.emergency-contacts.index')
            ->with('success', 'Emergency contact updated.');
    }

    public function destroy(
        Request $request,
        EmergencyContact $emergencyContact,
        EmergencyContactService $contactService
    ): RedirectResponse {
        $contactService->delete($emergencyContact, $request->user());

        return redirect()
            ->route('admin.settings.emergency-contacts.index')
            ->with('success', 'Emergency contact deleted.');
    }

    /**
     * @return array{name: string, number: string, type: string, is_active: bool, sort_order: int}
     */
    private function validatedAttributes(Request $request): array
    {
        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'number' => ['required', 'string', 'max:50'],
            'type' => ['required', Rule::in(EmergencyContact::TYPES)],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ], [
            'name.required' => 'Enter a name for this contact.',
            'number.required' => 'Enter a phone number or hotline.',
            'type.in' => 'Choose a valid emergency contact type.',
            'is_active.boolean' => 'Choose whether this contact is active.',
            'sort_order.integer' => 'The display order must be a whole number.',
            'sort_order.min' => 'The display order cannot be negative.',
        ]);

        $attributes['is_active'] = $request->boolean('is_active');
        $attributes['sort_order'] = (int) $attributes['sort_order'];

        return $attributes;
    }
}
