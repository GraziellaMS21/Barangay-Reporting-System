<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\Referral;
use App\Models\ReferralUpdate;
use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Referral tracking. Agencies have no accounts: staff log every update here.
 */
class ReferralController extends Controller
{
    public function __construct(private readonly ReferralService $referralService) {}

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['all', ...array_keys(Referral::STATUSES)])],
            'office_id' => ['nullable', 'integer', Rule::exists('offices', 'id')],
        ]);

        $statusFilter = $validated['status'] ?? 'all';
        $officeFilter = $validated['office_id'] ?? null;

        $query = Referral::query()
            ->with(['report:id,tracking_code,status', 'office:id,name'])
            ->latest('id');

        if ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        if ($officeFilter !== null) {
            $query->where('office_id', (int) $officeFilter);
        }

        $referrals = $query->paginate(15)->withQueryString();

        $offices = Office::query()
            ->where(fn ($officeQuery) => $officeQuery
                ->where('is_external', true)
                ->orWhereHas('referrals'))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.referrals.index', compact(
            'referrals',
            'offices',
            'statusFilter',
            'officeFilter'
        ));
    }

    /** Printable referral letter (Print / Save as PDF). */
    public function letter(Referral $referral): View
    {
        $referral->load(['report.purok', 'report.category', 'office']);

        return view('admin.referrals.letter', [
            'referral' => $referral,
            'report' => $referral->report,
            'office' => $referral->office,
        ]);
    }

    public function markSent(Request $request, Referral $referral): RedirectResponse
    {
        abort_unless($referral->isOpen(), 409, 'This referral is already closed.');

        $validated = $request->validate([
            'sent_at' => ['nullable', 'date'],
            'sent_via' => ['nullable', Rule::in(array_keys(Referral::SENT_VIA))],
        ]);

        $reviewer = $request->user();
        abort_unless($reviewer instanceof User, 403);

        $referral = $this->referralService->markSent(
            $referral,
            $reviewer,
            $validated['sent_at'] ?? null,
            $validated['sent_via'] ?? null
        );

        return back()->with('success', 'Referral '.$referral->reference_no.' marked as sent. Follow-up scheduled for '
            .$referral->follow_up_at?->format('M d, Y').'.');
    }

    public function storeUpdate(Request $request, Referral $referral): RedirectResponse
    {
        abort_unless($referral->isOpen(), 409, 'This referral is already closed.');

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:2000'],
            'channel' => ['required', Rule::in(array_keys(Referral::CHANNELS))],
            'occurred_at' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(Referral::UPDATE_TARGET_STATUSES)],
            'agency_reference_no' => ['nullable', 'string', 'max:100'],
            'visible_to_resident' => ['sometimes', 'boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ], [
            'note.required' => 'Describe what the agency reported.',
            'channel.required' => 'Choose how the update was received.',
        ]);

        $reviewer = $request->user();
        abort_unless($reviewer instanceof User, 403);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('referral_updates', 'public');
        }

        $update = $this->referralService->logUpdate(
            $referral,
            $reviewer,
            $validated['note'],
            $validated['channel'],
            $validated['occurred_at'] ?? null,
            $photoPath,
            $validated['agency_reference_no'] ?? null,
            $validated['status'] ?? null,
            (bool) ($validated['visible_to_resident'] ?? true)
        );

        return back()->with('success', 'Referral update logged for '.$referral->reference_no
            .($update->visible_to_resident ? ' and shared with the resident.' : ' (internal only).'));
    }

    public function close(Request $request, Referral $referral): RedirectResponse
    {
        abort_unless($referral->isOpen(), 409, 'This referral is already closed.');

        $validated = $request->validate([
            'outcome' => ['required', Rule::in(array_keys(Referral::OUTCOMES))],
            'note' => ['required', 'string', 'max:2000'],
        ], [
            'outcome.required' => 'Choose the referral outcome.',
            'note.required' => 'Add a short note explaining the outcome.',
        ]);

        $reviewer = $request->user();
        abort_unless($reviewer instanceof User, 403);

        $referral = $this->referralService->close($referral, $reviewer, $validated['outcome'], $validated['note']);

        return $referral->report->status === 'resolved'
            ? back()->with('success', 'Referral closed as resolved. The report is now resolved.')
            : back()->with('success', 'Referral closed ('.$validated['outcome']
                .'). The report stays open; choose the next action in Route and assign.');
    }

    public function updatePhoto(Referral $referral, ReferralUpdate $update): StreamedResponse
    {
        abort_unless($update->referral_id === $referral->id && $update->photo_path, 404);

        if (Storage::disk('public')->exists($update->photo_path)) {
            return Storage::disk('public')->response($update->photo_path);
        }

        abort_unless(Storage::disk('local')->exists($update->photo_path), 404);

        return Storage::disk('local')->response($update->photo_path);
    }

    public function destroy(Referral $referral): RedirectResponse
    {
        abort_unless($referral->isOpen(), 409);

        $referral->delete();

        return back()->with('success', 'Referral deleted.');
    }
}
