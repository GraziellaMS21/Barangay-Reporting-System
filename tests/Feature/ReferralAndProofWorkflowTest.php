<?php

namespace Tests\Feature;

use App\Mail\ReferralUpdateMail;
use App\Models\Category;
use App\Models\Office;
use App\Models\Purok;
use App\Models\Referral;
use App\Models\Report;
use App\Models\ReportAiResult;
use App\Models\ReportProofPhoto;
use App\Models\RoutingRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReferralAndProofWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private Category $category;

    private Purok $purok;

    private Office $internalOffice;

    private Office $externalOffice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = User::factory()->staff()->create(['name' => 'Officer Staff']);
        $this->purok = Purok::factory()->create(['name' => 'Purok 1']);
        $this->category = Category::create([
            'name' => 'Infrastructure',
            'slug' => 'infrastructure',
            'is_active' => true,
        ]);
        $this->internalOffice = Office::create([
            'name' => 'Barangay Infrastructure Office',
            'type' => 'barangay committee',
            'is_internal' => true,
            'is_external' => false,
        ]);
        $this->externalOffice = Office::create([
            'name' => 'City Engineering Office',
            'type' => 'city',
            'is_internal' => false,
            'is_external' => true,
            'contact_person' => 'Engr. Ana Reyes',
            'contact_email' => 'city-engineering@example.test',
            'contact_phone' => '(062) 991-0001',
            'address' => 'City Hall',
        ]);
    }

    private function report(array $attributes = []): Report
    {
        return Report::factory()->create(array_merge([
            'purok_id' => $this->purok->id,
            'category_id' => $this->category->id,
            'status' => 'submitted',
        ], $attributes));
    }

    public function test_review_page_shows_mode_banner_based_on_routing_rule(): void
    {
        RoutingRule::create([
            'category_id' => $this->category->id,
            'office_id' => $this->internalOffice->id,
            'within_authority' => true,
            'jurisdiction_note' => 'Handled by the barangay infrastructure committee.',
        ]);

        $withinReport = $this->report();
        $this->actingAs($this->staff)
            ->get(route('admin.reports.show', $withinReport))
            ->assertOk()
            ->assertSee('data-rule-mode="within"', false)
            ->assertSee('Within barangay authority')
            ->assertSee('Handled by the barangay infrastructure committee.');

        RoutingRule::query()->update(['within_authority' => false]);

        $beyondReport = $this->report();
        $this->actingAs($this->staff)
            ->get(route('admin.reports.show', $beyondReport))
            ->assertOk()
            ->assertSee('data-rule-mode="beyond"', false)
            ->assertSee('Beyond barangay authority')
            ->assertSee('Prepare referral');
    }

    public function test_review_page_renders_undo_rows_and_accept_all_for_ai_fields(): void
    {
        $report = $this->report(['ai_status' => 'done']);
        ReportAiResult::create([
            'report_id' => $report->id,
            'suggested_category' => $this->category->slug,
            'suggested_urgency' => 'High',
            'summary' => 'AI summary of the report.',
            'confidence' => 0.92,
        ]);

        $this->actingAs($this->staff)
            ->get(route('admin.reports.show', $report))
            ->assertOk()
            ->assertSee('data-accept-all', false)
            ->assertSee('data-undo-row', false)
            ->assertSee('data-final-control', false)
            ->assertSee('Accept all AI suggestions');
    }

    public function test_proof_photo_upload_stores_rows_and_files(): void
    {
        Storage::fake('public');
        $report = $this->report();

        $this->actingAs($this->staff)
            ->post(route('admin.reports.proof-photos.store', $report), [
                'proof_photos' => [
                    UploadedFile::fake()->image('proof-one.jpg'),
                    UploadedFile::fake()->image('proof-two.png'),
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseCount('report_proof_photos', 2);
        $report->refresh();
        $report->proofPhotos->each(function (ReportProofPhoto $photo): void {
            $this->assertStringStartsWith('proof_photos/', $photo->path);
            $this->assertSame($this->staff->id, $photo->uploaded_by);
            Storage::disk('public')->assertExists($photo->path);
        });
    }

    public function test_proof_photo_upload_rejects_non_image_and_over_limit_files(): void
    {
        Storage::fake('public');
        $report = $this->report();

        // Non-image file
        $this->actingAs($this->staff)
            ->post(route('admin.reports.proof-photos.store', $report), [
                'proof_photos' => [UploadedFile::fake()->create('notes.pdf', 100)],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('proof_photos.0');
        $this->assertDatabaseCount('report_proof_photos', 0);

        // More than max_files (5)
        $this->actingAs($this->staff)
            ->post(route('admin.reports.proof-photos.store', $report), [
                'proof_photos' => array_map(
                    fn (int $index): UploadedFile => UploadedFile::fake()->image("proof-$index.jpg"),
                    range(1, 6)
                ),
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('proof_photos');
        $this->assertDatabaseCount('report_proof_photos', 0);
    }

    public function test_proof_photo_delete_removes_row_and_file(): void
    {
        Storage::fake('public');
        $report = $this->report();

        $this->actingAs($this->staff)
            ->post(route('admin.reports.proof-photos.store', $report), [
                'proof_photos' => [UploadedFile::fake()->image('proof.jpg')],
            ])
            ->assertRedirect();

        $photo = $report->proofPhotos()->first();
        $path = $photo->path;

        $this->actingAs($this->staff)
            ->delete(route('admin.reports.proof-photos.destroy', [$report, $photo]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('report_proof_photos', ['id' => $photo->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_route_intent_with_matching_mode_does_not_log_override(): void
    {
        RoutingRule::create([
            'category_id' => $this->category->id,
            'office_id' => $this->internalOffice->id,
            'within_authority' => true,
            'jurisdiction_note' => 'Within barangay mandate.',
        ]);

        $report = $this->report();

        $this->actingAs($this->staff)
            ->post(route('admin.reports.review', $report), [
                'intent' => 'route',
                'category_id' => $this->category->id,
                'urgency' => 'High',
                'staff_description' => 'Reviewed description.',
                'office_id' => $this->internalOffice->id,
                'authority_mode' => 'within',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $report->refresh();
        $this->assertSame('routed', $report->status);
        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'jurisdiction_mode_override',
            'model_id' => $report->id,
        ]);
    }

    public function test_route_intent_with_overridden_mode_logs_audit_and_refers(): void
    {
        RoutingRule::create([
            'category_id' => $this->category->id,
            'office_id' => $this->internalOffice->id,
            'within_authority' => true,
            'jurisdiction_note' => 'Within barangay mandate.',
        ]);

        $report = $this->report();

        $this->actingAs($this->staff)
            ->post(route('admin.reports.review', $report), [
                'intent' => 'route',
                'category_id' => $this->category->id,
                'urgency' => 'High',
                'staff_description' => 'Reviewed description.',
                'office_id' => $this->externalOffice->id,
                'authority_mode' => 'beyond',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $report->refresh();
        $this->assertSame('referred', $report->status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'jurisdiction_mode_override',
            'model' => Report::class,
            'model_id' => $report->id,
            'user_id' => $this->staff->id,
        ]);

        $referral = Referral::query()->where('report_id', $report->id)->first();
        $this->assertNotNull($referral);
        $this->assertSame('prepared', $referral->status);
    }

    public function test_referral_intent_creates_referral_with_reference_number(): void
    {
        Mail::fake();
        $report = $this->report(['reporter_email' => 'resident@example.test']);

        $this->actingAs($this->staff)
            ->post(route('admin.reports.review', $report), [
                'intent' => 'referral',
                'category_id' => $this->category->id,
                'urgency' => 'Medium',
                'staff_description' => 'Reviewed description.',
                'office_id' => $this->externalOffice->id,
                'authority_mode' => 'beyond',
                'referral_letter' => 'Please act on report '.$report->tracking_code.'.',
                'resident_message' => 'We referred this to the City Engineering Office.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $report->refresh();
        $this->assertSame('referred', $report->status);

        $referral = Referral::query()->where('report_id', $report->id)->firstOrFail();
        $this->assertSame($this->externalOffice->id, $referral->office_id);
        $this->assertSame('prepared', $referral->status);
        $this->assertSame($this->staff->id, $referral->prepared_by);
        $this->assertMatchesRegularExpression('/^REF-'.now()->format('Y').'-\d{4}$/', $referral->reference_no);
        $this->assertStringContainsString($report->tracking_code, $referral->referral_text);

        // A second referral gets a unique, sequential reference number.
        $second = $this->report(['reporter_email' => 'resident2@example.test']);
        $this->actingAs($this->staff)
            ->post(route('admin.reports.review', $second), [
                'intent' => 'referral',
                'category_id' => $this->category->id,
                'urgency' => 'Low',
                'staff_description' => 'Second report review.',
                'office_id' => $this->externalOffice->id,
            ])
            ->assertRedirect();

        $secondReferral = Referral::query()->where('report_id', $second->id)->firstOrFail();
        $this->assertNotSame($referral->reference_no, $secondReferral->reference_no);
        // Preparing a referral does not email the resident yet; only sending does.
        Mail::assertNothingSent();
    }

    public function test_referral_intent_requires_an_external_agency(): void
    {
        $report = $this->report();

        $this->actingAs($this->staff)
            ->post(route('admin.reports.review', $report), [
                'intent' => 'referral',
                'category_id' => $this->category->id,
                'urgency' => 'Medium',
                'staff_description' => 'Reviewed description.',
                'office_id' => $this->internalOffice->id,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('review');

        $this->assertDatabaseCount('referrals', 0);
        $this->assertDatabaseMissing('status_logs', [
            'report_id' => $report->id,
            'to_status' => 'referred',
        ]);
    }

    public function test_mark_sent_sets_follow_up_and_notifies_resident(): void
    {
        Mail::fake();
        $report = $this->report([
            'status' => 'referred',
            'reporter_email' => 'resident@example.test',
        ]);
        $referral = Referral::create([
            'report_id' => $report->id,
            'office_id' => $this->externalOffice->id,
            'reference_no' => 'REF-2026-0001',
            'referral_text' => 'Referral letter text.',
            'status' => 'prepared',
            'prepared_by' => $this->staff->id,
        ]);

        $this->actingAs($this->staff)
            ->post(route('admin.referrals.mark-sent', $referral), [
                'sent_at' => now()->toDateString(),
                'sent_via' => 'email',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $referral->refresh();
        $this->assertSame('sent', $referral->status);
        $this->assertTrue($referral->sent_at->isToday());
        $this->assertSame('email', $referral->sent_via);
        // follow_up_at = sent_at + referral_follow_up_days (default 7 from config).
        $this->assertTrue($referral->follow_up_at->equalTo($referral->sent_at->copy()->addDays(7)));

        Mail::assertSent(ReferralUpdateMail::class, 1);

        $sentLog = $report->statusLogs()
            ->where('note', 'like', '%marked as sent%')
            ->first();
        $this->assertNotNull($sentLog);
        $this->assertStringContainsString($referral->reference_no, $sentLog->note);
    }

    public function test_agency_update_advances_status_and_respects_resident_visibility(): void
    {
        Mail::fake();
        $report = $this->report([
            'status' => 'referred',
            'reporter_email' => 'resident@example.test',
        ]);
        $referral = Referral::create([
            'report_id' => $report->id,
            'office_id' => $this->externalOffice->id,
            'reference_no' => 'REF-2026-0002',
            'referral_text' => 'Confidential internal letter text.',
            'status' => 'sent',
            'sent_at' => now()->subDay(),
            'sent_via' => 'email',
            'follow_up_at' => now()->addDays(7),
            'prepared_by' => $this->staff->id,
        ]);

        // Internal-only update: not shown on the tracking page, no email.
        $this->actingAs($this->staff)
            ->post(route('admin.referrals.updates.store', $referral), [
                'note' => 'Internal note about the agency call.',
                'channel' => 'phone',
                'status' => 'acknowledged',
                'visible_to_resident' => '0',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $referral->refresh();
        $this->assertSame('acknowledged', $referral->status);
        $this->assertDatabaseHas('referral_updates', [
            'referral_id' => $referral->id,
            'channel' => 'phone',
            'visible_to_resident' => false,
        ]);

        // Visible update: shared with the resident and emailed.
        $this->actingAs($this->staff)
            ->post(route('admin.referrals.updates.store', $referral), [
                'note' => 'The agency will inspect the site this week.',
                'channel' => 'email',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertSent(ReferralUpdateMail::class, 1);

        $this->get(route('track.show', $report->tracking_code))
            ->assertOk()
            ->assertSee('City Engineering Office')
            ->assertSee('The agency will inspect the site this week.')
            ->assertDontSee('Internal note about the agency call.')
            // Residents never see staff names, emails or the letter text.
            ->assertDontSee('Officer Staff')
            ->assertDontSee('city-engineering@example.test')
            ->assertDontSee('Confidential internal letter text.');
    }

    public function test_close_referral_as_resolved_resolves_the_report(): void
    {
        $report = $this->report(['status' => 'referred']);
        $referral = Referral::create([
            'report_id' => $report->id,
            'office_id' => $this->externalOffice->id,
            'reference_no' => 'REF-2026-0003',
            'referral_text' => 'Referral letter text.',
            'status' => 'action_taken',
            'prepared_by' => $this->staff->id,
        ]);

        $this->actingAs($this->staff)
            ->post(route('admin.referrals.close', $referral), [
                'outcome' => 'resolved',
                'note' => 'The agency repaired the road.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $referral->refresh();
        $report->refresh();
        $this->assertSame('closed', $referral->status);
        $this->assertSame('resolved', $referral->outcome);
        $this->assertNotNull($referral->closed_at);
        $this->assertSame('resolved', $report->status);
    }

    public function test_close_referral_as_declined_keeps_the_report_open(): void
    {
        $report = $this->report(['status' => 'referred']);
        $referral = Referral::create([
            'report_id' => $report->id,
            'office_id' => $this->externalOffice->id,
            'reference_no' => 'REF-2026-0004',
            'referral_text' => 'Referral letter text.',
            'status' => 'sent',
            'sent_at' => now()->subDays(2),
            'follow_up_at' => now()->addDays(5),
            'prepared_by' => $this->staff->id,
        ]);

        $this->actingAs($this->staff)
            ->post(route('admin.referrals.close', $referral), [
                'outcome' => 'declined',
                'note' => 'The agency declined; not under their mandate.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $referral->refresh();
        $report->refresh();
        $this->assertSame('closed', $referral->status);
        $this->assertSame('declined', $referral->outcome);
        // The report stays open for the next action (re-route, out of scope...).
        $this->assertSame('referred', $report->status);
        $this->assertDatabaseHas('referral_updates', [
            'referral_id' => $referral->id,
            'visible_to_resident' => false,
        ]);
    }

    public function test_out_of_scope_and_duplicate_require_reason_fields(): void
    {
        $report = $this->report();

        $this->actingAs($this->staff)
            ->patch(route('admin.reports.status', $report), [
                'status' => 'out_of_scope',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('note');

        $other = $this->report();
        $this->actingAs($this->staff)
            ->patch(route('admin.reports.duplicate', $report), [
                'note' => 'Same issue as the other report.',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('candidate_id');

        // The report was not marked out of scope or merged.
        $report->refresh();
        $this->assertSame('submitted', $report->status);
        $this->assertSame('submitted', $other->status);
    }
}
