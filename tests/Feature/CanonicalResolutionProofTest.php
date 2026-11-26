<?php

namespace Tests\Feature;

use App\Events\ReportStatusChanged;
use App\Events\ResolutionProposed;
use App\Models\Category;
use App\Models\Office;
use App\Models\Permission;
use App\Models\Purok;
use App\Models\Referral;
use App\Models\Report;
use App\Models\ReportProofPhoto;
use App\Models\User;
use App\Services\ReferralService;
use App\Services\ReportStatusService;
use App\Support\ReportStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

class CanonicalResolutionProofTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private Category $category;

    private Purok $purok;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = User::factory()->create(['role' => 'staff']);
        $this->staff->permissions()->sync([
            Permission::firstOrCreate(['slug' => 'reports.view'], ['label' => 'View Resident Reports Queue', 'group' => 'reports'])->id,
            Permission::firstOrCreate(['slug' => 'referrals.manage'], ['label' => 'Manage External Referrals', 'group' => 'referrals'])->id,
        ]);

        $this->purok = Purok::factory()->create(['name' => 'Purok 1']);
        $this->category = Category::create([
            'name' => 'Drainage',
            'slug' => 'drainage',
            'is_active' => true,
        ]);
    }

    public function test_proposing_resolution_without_new_or_existing_proof_photos_is_rejected(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $report = Report::factory()->create([
            'status' => ReportStatus::IN_PROGRESS,
            'purok_id' => $this->purok->id,
            'category_id' => $this->category->id,
        ]);

        $this->actingAs($this->staff)
            ->post(route('admin.reports.resolve', $report), [
                'note' => 'Completed work without attaching proof.',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('proof_photos');

        $this->assertSame(ReportStatus::IN_PROGRESS, $report->fresh()->status);
        $this->assertDatabaseCount('resolution_proofs', 0);
        $this->assertDatabaseCount('report_proof_photos', 0);
    }

    public function test_legacy_photo_alone_does_not_satisfy_canonical_multi_photo_requirement(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $report = Report::factory()->create([
            'status' => ReportStatus::IN_PROGRESS,
        ]);

        $this->actingAs($this->staff)
            ->post(route('admin.reports.resolve', $report), [
                'note' => 'Legacy single photo only.',
                'photo' => UploadedFile::fake()->image('legacy.jpg'),
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('proof_photos');

        $this->assertSame(ReportStatus::IN_PROGRESS, $report->fresh()->status);
        $this->assertDatabaseCount('resolution_proofs', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_existing_proof_photo_allows_proposal_and_other_report_proof_does_not_count(): void
    {
        Storage::fake('public');

        $otherReport = Report::factory()->create(['status' => ReportStatus::IN_PROGRESS]);
        ReportProofPhoto::create([
            'report_id' => $otherReport->id,
            'path' => 'proof_photos/other.jpg',
            'uploaded_by' => $this->staff->id,
        ]);

        $report = Report::factory()->create(['status' => ReportStatus::IN_PROGRESS]);

        // Proof belonging to another report does not satisfy this report
        $this->actingAs($this->staff)
            ->post(route('admin.reports.resolve', $report), [
                'note' => 'Trying without own proof.',
            ])
            ->assertSessionHasErrors('proof_photos');

        // Attaching own proof photo allows proposal without another upload
        ReportProofPhoto::create([
            'report_id' => $report->id,
            'path' => 'proof_photos/existing.jpg',
            'uploaded_by' => $this->staff->id,
        ]);

        $this->actingAs($this->staff)
            ->post(route('admin.reports.resolve', $report), [
                'note' => 'Work verified with pre-uploaded proof.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(ReportStatus::AWAITING_CONFIRMATION, $report->fresh()->status);
        $this->assertDatabaseCount('resolution_proofs', 1);
    }

    public function test_invalid_proof_upload_is_rejected_without_creating_records(): void
    {
        Storage::fake('public');

        $report = Report::factory()->create(['status' => ReportStatus::IN_PROGRESS]);

        $this->actingAs($this->staff)
            ->post(route('admin.reports.resolve', $report), [
                'note' => 'Invalid file upload.',
                'proof_photos' => [UploadedFile::fake()->create('document.pdf', 100)],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('proof_photos.0');

        $this->assertSame(ReportStatus::IN_PROGRESS, $report->fresh()->status);
        $this->assertDatabaseCount('resolution_proofs', 0);
        $this->assertDatabaseCount('report_proof_photos', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_emergency_report_also_requires_proof_photo_before_awaiting_confirmation(): void
    {
        Storage::fake('public');

        $emergencyReport = Report::factory()->create([
            'status' => ReportStatus::IN_PROGRESS,
            'is_emergency' => true,
        ]);

        $this->actingAs($this->staff)
            ->post(route('admin.reports.resolve', $emergencyReport), [
                'note' => 'Emergency perimeter cleared.',
            ])
            ->assertSessionHasErrors('proof_photos');

        $this->assertSame(ReportStatus::IN_PROGRESS, $emergencyReport->fresh()->status);

        $this->actingAs($this->staff)
            ->post(route('admin.reports.resolve', $emergencyReport), [
                'note' => 'Emergency perimeter cleared with photo.',
                'proof_photos' => [UploadedFile::fake()->image('emergency-proof.jpg')],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(ReportStatus::AWAITING_CONFIRMATION, $emergencyReport->fresh()->status);
        $this->assertDatabaseCount('report_proof_photos', 1);
    }

    public function test_failed_status_transition_rolls_back_records_and_cleans_up_newly_stored_files(): void
    {
        Event::fake([ResolutionProposed::class, ReportStatusChanged::class]);
        Storage::fake('public');
        Storage::fake('local');

        // Pre-existing proof belonging to this report (e.g., from earlier upload)
        $existingPath = UploadedFile::fake()->image('preexisting.jpg')->store('proof_photos', 'public');
        $report = Report::factory()->create([
            // 'submitted' cannot transition directly to 'awaiting_confirmation'
            'status' => ReportStatus::SUBMITTED,
        ]);
        $existingProof = ReportProofPhoto::create([
            'report_id' => $report->id,
            'path' => $existingPath,
            'uploaded_by' => $this->staff->id,
        ]);

        $this->actingAs($this->staff)
            ->post(route('admin.reports.resolve', $report), [
                'note' => 'Attempting invalid transition with new files.',
                'photo' => UploadedFile::fake()->image('new-legacy.jpg'),
                'proof_photos' => [UploadedFile::fake()->image('new-proof.jpg')],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('status');

        $this->assertSame(ReportStatus::SUBMITTED, $report->fresh()->status);
        $this->assertDatabaseCount('resolution_proofs', 0);
        // Only the pre-existing proof record and file remain
        $this->assertDatabaseCount('report_proof_photos', 1);
        $this->assertDatabaseHas('report_proof_photos', ['id' => $existingProof->id]);
        Storage::disk('public')->assertExists($existingPath);
        $this->assertCount(1, Storage::disk('public')->allFiles('proof_photos'));
        $this->assertSame([], Storage::disk('local')->allFiles('resolution_proofs'));
        Event::assertNotDispatched(ResolutionProposed::class);
    }

    public function test_state_machine_blocks_direct_resolution_bypasses_and_allows_canonical_paths(): void
    {
        $statusService = app(ReportStatusService::class);

        $disallowed = [
            [ReportStatus::ROUTED, ReportStatus::AWAITING_CONFIRMATION],
            [ReportStatus::ROUTED, ReportStatus::RESOLVED],
            [ReportStatus::IN_PROGRESS, ReportStatus::RESOLVED],
            [ReportStatus::NEEDS_INFO, ReportStatus::AWAITING_CONFIRMATION],
            [ReportStatus::NEEDS_INFO, ReportStatus::RESOLVED],
            [ReportStatus::REFERRED, ReportStatus::RESOLVED],
        ];

        foreach ($disallowed as [$from, $to]) {
            $this->assertFalse($statusService->canTransition($from, $to), "Expected {$from} -> {$to} to be blocked.");

            $report = Report::factory()->create(['status' => $from]);
            try {
                $statusService->transition($report, $to, 'Attempted bypass', $this->staff);
                $this->fail("Expected InvalidArgumentException for {$from} -> {$to}.");
            } catch (InvalidArgumentException) {
                $this->assertSame($from, $report->fresh()->status);
            }
        }

        // Controller updateStatus returns validation error (not 500) on disallowed bypass
        $inProgressReport = Report::factory()->create([
            'status' => ReportStatus::IN_PROGRESS,
            'category_id' => $this->category->id,
        ]);

        $this->actingAs($this->staff)
            ->patch(route('admin.reports.status', $inProgressReport), [
                'status' => ReportStatus::RESOLVED,
                'note' => 'Direct resolve bypass attempt.',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('status');

        $this->assertSame(ReportStatus::IN_PROGRESS, $inProgressReport->fresh()->status);

        // Canonical transitions remain valid
        $this->assertTrue($statusService->canTransition(ReportStatus::IN_PROGRESS, ReportStatus::AWAITING_CONFIRMATION));
        $this->assertTrue($statusService->canTransition(ReportStatus::AWAITING_CONFIRMATION, ReportStatus::RESOLVED));
    }

    public function test_external_referral_completion_moves_report_to_in_progress_requiring_proof_and_confirmation(): void
    {
        Storage::fake('public');

        $externalOffice = Office::create([
            'name' => 'City Engineering',
            'type' => 'city',
            'is_internal' => false,
            'is_active' => true,
        ]);

        $report = Report::factory()->create([
            'status' => ReportStatus::REFERRED,
            'office_id' => $externalOffice->id,
            'category_id' => $this->category->id,
            'purok_id' => $this->purok->id,
        ]);

        $referral = Referral::create([
            'report_id' => $report->id,
            'office_id' => $externalOffice->id,
            'referral_text' => 'Please clear drainage obstruction.',
            'status' => 'sent',
            'sent_at' => now()->subDay(),
        ]);

        app(ReferralService::class)->close($referral, $this->staff, 'resolved', 'City crew cleared the culvert.');

        $referral->refresh();
        $report->refresh();

        $this->assertSame('closed', $referral->status);
        $this->assertSame('resolved', $referral->outcome);
        $this->assertNotNull($referral->closed_at);
        // Report is NOT directly resolved or awaiting_confirmation
        $this->assertSame(ReportStatus::IN_PROGRESS, $report->status);
        $this->assertNull($report->resolved_at);

        // Staff proposes resolution with proof
        $this->actingAs($this->staff)
            ->post(route('admin.reports.resolve', $report), [
                'note' => 'Verified city engineering repair on site.',
                'proof_photos' => [UploadedFile::fake()->image('verified.jpg')],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(ReportStatus::AWAITING_CONFIRMATION, $report->fresh()->status);

        // Resident confirms resolution
        $this->post(route('track.confirm', $report->tracking_code), [
            'rating' => 5,
            'comment' => 'Confirmed fixed.',
        ])->assertRedirect();

        $report->refresh();
        $this->assertSame(ReportStatus::RESOLVED, $report->status);
        $this->assertNotNull($report->resolved_at);
        $this->assertNotNull($report->resolution_confirmed_at);
    }

    public function test_bulk_action_awaiting_confirmation_enforces_proof_per_report(): void
    {
        Storage::fake('public');

        // Report 1: in_progress with no proof
        $withoutProof = Report::factory()->create([
            'status' => ReportStatus::IN_PROGRESS,
            'category_id' => $this->category->id,
        ]);

        // Report 2: legacy ResolutionProof only (no report_proof_photos)
        $legacyOnly = Report::factory()->create([
            'status' => ReportStatus::IN_PROGRESS,
            'category_id' => $this->category->id,
        ]);
        $legacyOnly->resolutionProofs()->create([
            'user_id' => $this->staff->id,
            'note' => 'Legacy proof record only',
            'photo_path' => 'resolution_proofs/legacy.jpg',
        ]);

        // Report 3: has canonical ReportProofPhoto
        $withProof = Report::factory()->create([
            'status' => ReportStatus::IN_PROGRESS,
            'category_id' => $this->category->id,
        ]);
        ReportProofPhoto::create([
            'report_id' => $withProof->id,
            'path' => 'proof_photos/canonical.jpg',
            'uploaded_by' => $this->staff->id,
        ]);

        // 1. Bulk action on withoutProof alone is skipped
        $this->actingAs($this->staff)
            ->post(route('admin.reports.bulk-action'), [
                'action' => 'status',
                'status' => ReportStatus::AWAITING_CONFIRMATION,
                'report_ids' => [$withoutProof->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success', fn (string $msg) => str_contains($msg, '0 report(s)') && str_contains($msg, '1 skipped'));

        $this->assertSame(ReportStatus::IN_PROGRESS, $withoutProof->fresh()->status);

        // 2. Bulk action on legacyOnly alone is also skipped (legacy alone does not satisfy canonical proof)
        $this->actingAs($this->staff)
            ->post(route('admin.reports.bulk-action'), [
                'action' => 'status',
                'status' => ReportStatus::AWAITING_CONFIRMATION,
                'report_ids' => [$legacyOnly->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success', fn (string $msg) => str_contains($msg, '0 report(s)') && str_contains($msg, '1 skipped'));

        $this->assertSame(ReportStatus::IN_PROGRESS, $legacyOnly->fresh()->status);

        // 3. Bulk action on withProof alone succeeds
        $this->actingAs($this->staff)
            ->post(route('admin.reports.bulk-action'), [
                'action' => 'status',
                'status' => ReportStatus::AWAITING_CONFIRMATION,
                'report_ids' => [$withProof->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success', fn (string $msg) => str_contains($msg, 'Updated status for 1 report(s).') && ! str_contains($msg, 'skipped'));

        $this->assertSame(ReportStatus::AWAITING_CONFIRMATION, $withProof->fresh()->status);

        // 4. Mixed bulk request: report without proof is skipped while eligible report succeeds
        $anotherWithoutProof = Report::factory()->create([
            'status' => ReportStatus::IN_PROGRESS,
            'category_id' => $this->category->id,
        ]);
        $anotherWithProof = Report::factory()->create([
            'status' => ReportStatus::IN_PROGRESS,
            'category_id' => $this->category->id,
        ]);
        ReportProofPhoto::create([
            'report_id' => $anotherWithProof->id,
            'path' => 'proof_photos/another-canonical.jpg',
            'uploaded_by' => $this->staff->id,
        ]);

        $this->actingAs($this->staff)
            ->post(route('admin.reports.bulk-action'), [
                'action' => 'status',
                'status' => ReportStatus::AWAITING_CONFIRMATION,
                'report_ids' => [$anotherWithoutProof->id, $anotherWithProof->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success', fn (string $msg) => str_contains($msg, '1 report(s)') && str_contains($msg, '1 skipped'));

        $this->assertSame(ReportStatus::IN_PROGRESS, $anotherWithoutProof->fresh()->status);
        $this->assertSame(ReportStatus::AWAITING_CONFIRMATION, $anotherWithProof->fresh()->status);

        // 5. Unrelated status transitions (e.g. under_review -> routed) do not require proof photos
        $unrelatedReport = Report::factory()->create([
            'status' => ReportStatus::UNDER_REVIEW,
            'category_id' => $this->category->id,
        ]);

        $this->actingAs($this->staff)
            ->post(route('admin.reports.bulk-action'), [
                'action' => 'status',
                'status' => ReportStatus::ROUTED,
                'report_ids' => [$unrelatedReport->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success', fn (string $msg) => str_contains($msg, 'Updated status for 1 report(s).') && ! str_contains($msg, 'skipped'));

        $this->assertSame(ReportStatus::ROUTED, $unrelatedReport->fresh()->status);
    }

    public function test_manual_closure_hardening_and_safeguards(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // 1. Missing justification rejected
        $reportWithProof = Report::factory()->create(['status' => ReportStatus::AWAITING_CONFIRMATION]);
        ReportProofPhoto::create([
            'report_id' => $reportWithProof->id,
            'path' => 'proof_photos/test1.jpg',
            'uploaded_by' => $this->staff->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.reports.close', $reportWithProof), [])
            ->assertRedirect()
            ->assertSessionHasErrors('justification');
        $this->assertSame(ReportStatus::AWAITING_CONFIRMATION, $reportWithProof->fresh()->status);

        // 2. Justification shorter than 10 chars rejected
        $this->actingAs($admin)
            ->post(route('admin.reports.close', $reportWithProof), [
                'justification' => 'Too short',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('justification');
        $this->assertSame(ReportStatus::AWAITING_CONFIRMATION, $reportWithProof->fresh()->status);

        // 3. Report without proof rejected for manual closure
        $reportWithoutProof = Report::factory()->create(['status' => ReportStatus::AWAITING_CONFIRMATION]);
        $this->actingAs($admin)
            ->post(route('admin.reports.close', $reportWithoutProof), [
                'justification' => 'Valid admin justification text for closure.',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('justification');
        $this->assertSame(ReportStatus::AWAITING_CONFIRMATION, $reportWithoutProof->fresh()->status);

        // 4. Legacy proof record alone does not satisfy canonical proof requirement
        $reportLegacyOnly = Report::factory()->create(['status' => ReportStatus::AWAITING_CONFIRMATION]);
        $reportLegacyOnly->resolutionProofs()->create([
            'user_id' => $this->staff->id,
            'note' => 'Legacy proof record',
            'photo_path' => 'resolution_proofs/legacy.jpg',
        ]);
        $this->actingAs($admin)
            ->post(route('admin.reports.close', $reportLegacyOnly), [
                'justification' => 'Valid admin justification text for closure.',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('justification');
        $this->assertSame(ReportStatus::AWAITING_CONFIRMATION, $reportLegacyOnly->fresh()->status);

        // 5. Reports outside awaiting_confirmation cannot be manually closed (422)
        $inProgressReport = Report::factory()->create(['status' => ReportStatus::IN_PROGRESS]);
        ReportProofPhoto::create([
            'report_id' => $inProgressReport->id,
            'path' => 'proof_photos/test2.jpg',
            'uploaded_by' => $this->staff->id,
        ]);
        $this->actingAs($admin)
            ->post(route('admin.reports.close', $inProgressReport), [
                'justification' => 'Valid admin justification text for closure.',
            ])
            ->assertStatus(422);
        $this->assertSame(ReportStatus::IN_PROGRESS, $inProgressReport->fresh()->status);

        // 6. Successful manual closure creates AuditLog entry and transitions status
        $this->actingAs($admin)
            ->post(route('admin.reports.close', $reportWithProof), [
                'justification' => 'Resident reached confirmation deadline without dispute.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(ReportStatus::RESOLVED, $reportWithProof->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'manual_close_report',
            'model' => Report::class,
            'model_id' => $reportWithProof->id,
        ]);
    }

    public function test_resident_proof_photo_display_and_secure_streaming(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        // Create report in awaiting_confirmation with 2 canonical proof photos
        $report = Report::factory()->create([
            'status' => ReportStatus::AWAITING_CONFIRMATION,
            'category_id' => $this->category->id,
        ]);

        $proofFile1 = UploadedFile::fake()->image('proof1.jpg')->store('proof_photos', 'public');
        $proofFile2 = UploadedFile::fake()->image('proof2.jpg')->store('proof_photos', 'public');

        $proof1 = ReportProofPhoto::create([
            'report_id' => $report->id,
            'path' => $proofFile1,
            'uploaded_by' => $this->staff->id,
        ]);
        $proof2 = ReportProofPhoto::create([
            'report_id' => $report->id,
            'path' => $proofFile2,
            'uploaded_by' => $this->staff->id,
        ]);

        // 1. Resident show page renders links to both canonical proof photos
        $response = $this->get(route('track.show', $report->tracking_code));
        $response->assertOk();
        $response->assertSee(route('track.proof-photo', [$report->tracking_code, $proof1]));
        $response->assertSee(route('track.proof-photo', [$report->tracking_code, $proof2]));

        // 2. Proof photo delivery route streams the image
        $imgResponse = $this->get(route('track.proof-photo', [$report->tracking_code, $proof1]));
        $imgResponse->assertOk();

        // 3. Requesting proof photo with wrong tracking code returns 404
        $otherReport = Report::factory()->create();
        $this->get(route('track.proof-photo', [$otherReport->tracking_code, $proof1]))
            ->assertNotFound();

        // 4. Legacy fallback: when report has no canonical photos but has legacy ResolutionProof
        $legacyReport = Report::factory()->create([
            'status' => ReportStatus::AWAITING_CONFIRMATION,
            'category_id' => $this->category->id,
        ]);
        $legacyFilePath = UploadedFile::fake()->image('legacy.jpg')->store('resolution_proofs', 'local');
        $legacyProof = $legacyReport->resolutionProofs()->create([
            'user_id' => $this->staff->id,
            'note' => 'Legacy repair completed',
            'photo_path' => $legacyFilePath,
        ]);

        $legacyShowResponse = $this->get(route('track.show', $legacyReport->tracking_code));
        $legacyShowResponse->assertOk();
        $legacyShowResponse->assertSee(route('track.legacy-proof', [$legacyReport->tracking_code, $legacyProof]));

        $legacyImgResponse = $this->get(route('track.legacy-proof', [$legacyReport->tracking_code, $legacyProof]));
        $legacyImgResponse->assertOk();
    }
}
