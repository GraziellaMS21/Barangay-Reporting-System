<?php

namespace App\Http\Controllers;

use App\Models\EmergencyContact;
use App\Models\Purok;
use App\Models\Report;
use App\Models\StatusLog;
use App\Services\GeminiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function create(): View
    {
        return view('resident.create', [
            'puroks' => Purok::orderBy('name')->get(),
            'contacts' => EmergencyContact::all(),
        ]);
    }

    public function store(Request $request, GeminiService $geminiService): RedirectResponse
    {
        $contactRequired = config('reports.require_contact', false);
        $data = $request->validate([
            'description' => 'required|string|min:10|max:2000',
            'photos' => ['nullable', 'array'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'lat' => ['nullable', 'required_with:lng', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'required_with:lat', 'numeric', 'between:-180,180'],
            'purok_id' => 'nullable|exists:puroks,id',
            'landmark' => 'nullable|string|max:255',
            'reporter_email' => 'nullable|email|max:255',
            'reporter_name' => ['nullable', 'string', 'max:100'],
            'reporter_contact' => [
                $contactRequired ? 'required' : 'nullable',
                'string',
                'max:20',
                'regex:/^(?:0[ -]*9|\\+[ -]*6[ -]*3[ -]*9)(?:[ -]*\\d){9}$/',
            ],
        ], [
            'reporter_name.string' => 'Please enter a valid name.',
            'reporter_name.max' => 'Name cannot be longer than 100 characters.',
            'reporter_contact.required' => 'Please enter a contact number so barangay staff can reach you.',
            'reporter_contact.string' => 'Please enter a valid contact number.',
            'reporter_contact.max' => 'Contact number cannot be longer than 20 characters.',
            'reporter_contact.regex' => 'Enter a Philippine mobile number, such as 09171234567 or +639171234567. Spaces and dashes are okay.',
            'photos.array' => 'Please select valid photo files.',
            'photos.*.image' => 'Each selected file must be a valid image.',
            'photos.*.mimes' => 'Each photo must be a JPG or PNG image.',
            'photos.*.max' => 'The combined size of all photos must not exceed 5 MB.',
        ]);

        $data['reporter_contact'] = isset($data['reporter_contact'])
            ? str_replace([' ', '-'], '', $data['reporter_contact'])
            : null;

        /** @var list<UploadedFile> $photos */
        $photos = $data['photos'] ?? [];
        $totalPhotoBytes = array_sum(array_map(
            static fn (UploadedFile $photo): int => (int) $photo->getSize(),
            $photos
        ));

        if ($totalPhotoBytes > 5 * 1024 * 1024) {
            return back()
                ->withInput()
                ->withErrors(['photos' => 'The combined size of all photos must not exceed 5 MB.']);
        }

        if (! $request->filled('lat') && ! $request->filled('purok_id') && ! $request->filled('landmark')) {
            return back()->withInput()
                ->withErrors(['location' => 'Please set a pin on the map, choose a purok, or enter a landmark.']);
        }

        $photoPaths = array_map(
            static fn (UploadedFile $photo): string => $photo->store('reports', 'local'),
            $photos
        );

        $report = DB::transaction(function () use ($data, $photoPaths) {
            $report = Report::create([
                'tracking_code' => Report::generateTrackingCode(),
                'description' => $data['description'],
                'photo_path' => $photoPaths[0] ?? null,
                'lat' => $data['lat'] ?? null,
                'lng' => $data['lng'] ?? null,
                'purok_id' => $data['purok_id'] ?? null,
                'landmark' => $data['landmark'] ?? null,
                'reporter_email' => $data['reporter_email'] ?? null,
                'reporter_name' => $data['reporter_name'] ?? null,
                'reporter_contact' => $data['reporter_contact'],
                'status' => 'submitted',
            ]);

            foreach ($photoPaths as $photoPath) {
                $report->photos()->create(['photo_path' => $photoPath]);
            }

            StatusLog::create([
                'report_id' => $report->id,
                'from_status' => null,
                'to_status' => 'submitted',
                'note' => 'Report received.',
            ]);

            return $report;
        });

        $geminiService->analyze($report);

        return redirect()->route('reports.success', $report->tracking_code);
    }

    public function success(string $trackingCode): View
    {
        $report = Report::query()
            ->select(['id', 'tracking_code', 'status', 'created_at'])
            ->where('tracking_code', $trackingCode)
            ->firstOrFail();

        return view('resident.success', compact('report'));
    }
}
