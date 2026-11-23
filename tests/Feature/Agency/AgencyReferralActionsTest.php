<?php

namespace Tests\Feature\Agency;

use App\Models\AgencyRegistration;
use App\Models\BarangayCase;
use App\Models\CaseParty;
use App\Models\EscalationDestination;
use App\Models\FiscalYear;
use App\Models\Office;
use App\Models\Party;
use App\Models\Permission;
use App\Models\Referral;
use App\Models\ReferralAttachment;
use App\Models\Report;
use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AgencyReferralActionsTest extends TestCase
{
    use RefreshDatabase;

    private FiscalYear $fy;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        $this->fy = FiscalYear::first() ?? FiscalYear::factory()->create(['status' => 'active', 'year' => 2026]);
    }

    public function test_due_for_follow_up_returns_only_open_overdue_referrals(): void
    {
        $office = Office::create(['name' => 'City Social Welfare', 'type' => 'external', 'is_external' => true, 'is_active' => true]);
        $report = Report::factory()->create();

        // 1. Open and past follow-up (due)
        $due1 = Referral::create([
            'report_id' => $report->id,
            'office_id' => $office->id,
            'status' => 'sent',
            'follow_up_at' => now()->subDay(),
            'referral_text' => 'Due referral 1',
        ]);

        // 2. Open and today's follow-up (due)
        $due2 = Referral::create([
            'report_id' => $report->id,
            'office_id' => $office->id,
            'status' => 'acknowledged',
            'follow_up_at' => today(),
            'referral_text' => 'Due referral 2',
        ]);

        // 3. Open and future follow-up (not due)
        Referral::create([
            'report_id' => $report->id,
            'office_id' => $office->id,
            'status' => 'sent',
            'follow_up_at' => now()->addDays(3),
            'referral_text' => 'Future referral',
        ]);

        // 4. Closed referral (even if follow_up_at is past, it is not due)
        Referral::create([
            'report_id' => $report->id,
            'office_id' => $office->id,
            'status' => 'closed',
            'follow_up_at' => now()->subDays(5),
            'referral_text' => 'Closed referral',
        ]);

        $service = app(ReferralService::class);
        $dueReferrals = $service->dueForFollowUp();

        $this->assertTrue($dueReferrals->contains('id', $due1->id));
        $this->assertTrue($dueReferrals->contains('id', $due2->id));
        $this->assertCount(2, $dueReferrals);
        $this->assertTrue($dueReferrals->first()->relationLoaded('office'));
    }

    public function test_create_from_case_creates_referral_and_dossier_attachment(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $case = BarangayCase::create([
            'fiscal_year_id' => $this->fy->id,
            'case_number' => 'CASE-2026-0001',
            'title' => 'Property boundary conflict',
            'incident_type' => 'Boundary Dispute',
            'narrative' => 'Dispute over boundary fence located at Purok 1.',
            'status' => 'unsettled',
            'filed_on' => now()->subDays(20)->toDateString(),
            'encoded_by' => $staff->id,
        ]);

        $complainant = Party::create(['full_name' => 'Juan Dela Cruz', 'contact_number' => '09123456789']);
        $respondent = Party::create(['full_name' => 'Pedro Santos', 'contact_number' => '09987654321']);

        CaseParty::create(['case_id' => $case->id, 'party_id' => $complainant->id, 'role' => 'complainant']);
        CaseParty::create(['case_id' => $case->id, 'party_id' => $respondent->id, 'role' => 'respondent']);

        $destination = EscalationDestination::create([
            'name' => 'Municipal Trial Court in Cities',
            'agency_type' => 'court',
            'contact_person' => 'Clerk of Court',
            'contact_email' => 'mtcc@judiciary.gov.ph',
            'address' => 'Hall of Justice, Zamboanga City',
        ]);

        $service = app(ReferralService::class);
        $referral = $service->createFromCase($case, $destination, $staff);

        $this->assertInstanceOf(Referral::class, $referral);
        $this->assertDatabaseHas('referrals', [
            'id' => $referral->id,
            'case_id' => $case->id,
            'status' => 'sent',
        ]);

        $this->assertNull($referral->report_id);
        $this->assertNotNull($referral->reference_no);
        $this->assertStringContainsString('CASE-2026-0001', $referral->referral_text);

        // Check dossier attachment exists in DB and private storage
        $attachment = ReferralAttachment::where('referral_id', $referral->id)->first();
        $this->assertNotNull($attachment);
        $this->assertStringContainsString('Case Dossier', $attachment->label);
        Storage::disk('private')->assertExists($attachment->path);

        $content = Storage::disk('private')->get($attachment->path);
        $this->assertStringContainsString('OFFICIAL CASE DOSSIER', $content);
        $this->assertStringContainsString('CASE-2026-0001', $content);
        $this->assertStringContainsString('Juan Dela Cruz', $content);
    }

    public function test_agency_user_can_upload_and_download_attachment(): void
    {
        $office = Office::create(['name' => 'PNP Maritime', 'type' => 'external', 'is_external' => true, 'is_active' => true]);
        $agencyUser = User::factory()->create([
            'role' => 'agency',
            'office_id' => $office->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        AgencyRegistration::factory()->create([
            'user_id' => $agencyUser->id,
            'office_id' => $office->id,
            'status' => 'approved',
        ]);

        $report = Report::factory()->create();
        $referral = Referral::create([
            'report_id' => $report->id,
            'office_id' => $office->id,
            'reference_no' => 'REF-PNP-001',
            'status' => 'sent',
            'referral_text' => 'Incident investigation',
        ]);

        $fakeFile = UploadedFile::fake()->create('police_report.pdf', 300, 'application/pdf');

        $uploadResponse = $this->actingAs($agencyUser)->post(
            route('agency.referrals.attachments.upload', $referral),
            [
                'file' => $fakeFile,
                'label' => 'Initial Incident Police Report',
            ]
        );

        $uploadResponse->assertRedirect();
        $uploadResponse->assertSessionHas('success', 'Document attachment uploaded successfully.');

        $attachment = ReferralAttachment::where('referral_id', $referral->id)->first();
        $this->assertNotNull($attachment);
        $this->assertSame('Initial Incident Police Report', $attachment->label);
        Storage::disk('private')->assertExists($attachment->path);

        // Test download
        $downloadResponse = $this->actingAs($agencyUser)->get(
            route('agency.referrals.attachments.download', [$referral, $attachment])
        );

        $downloadResponse->assertStatus(200);
        $downloadResponse->assertHeader('content-disposition');
    }

    public function test_agency_user_cannot_access_attachments_of_other_office_referrals(): void
    {
        $officeA = Office::create(['name' => 'Office A', 'type' => 'external', 'is_external' => true, 'is_active' => true]);
        $officeB = Office::create(['name' => 'Office B', 'type' => 'external', 'is_external' => true, 'is_active' => true]);

        $agencyUserA = User::factory()->create([
            'role' => 'agency',
            'office_id' => $officeA->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        AgencyRegistration::factory()->create([
            'user_id' => $agencyUserA->id,
            'office_id' => $officeA->id,
            'status' => 'approved',
        ]);

        $report = Report::factory()->create();
        $referralB = Referral::create([
            'report_id' => $report->id,
            'office_id' => $officeB->id,
            'reference_no' => 'REF-OFFICE-B',
            'status' => 'sent',
            'referral_text' => 'Confidential investigation',
        ]);

        Storage::disk('private')->put('referral-attachments/test.pdf', 'dummy content');
        $attachment = ReferralAttachment::create([
            'referral_id' => $referralB->id,
            'label' => 'Confidential File',
            'path' => 'referral-attachments/test.pdf',
            'uploaded_by' => null,
        ]);

        $fakeFile = UploadedFile::fake()->create('attack.pdf', 100, 'application/pdf');

        // Attempt upload to referral of Office B
        $uploadResponse = $this->actingAs($agencyUserA)->post(
            route('agency.referrals.attachments.upload', $referralB),
            [
                'file' => $fakeFile,
                'label' => 'Malicious file',
            ]
        );
        $uploadResponse->assertStatus(403);

        // Attempt download from referral of Office B
        $downloadResponse = $this->actingAs($agencyUserA)->get(
            route('agency.referrals.attachments.download', [$referralB, $attachment])
        );
        $downloadResponse->assertStatus(403);
    }

    public function test_staff_can_upload_and_download_referral_attachments(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $permission = Permission::firstOrCreate(['slug' => 'referrals.manage'], ['label' => 'Manage Referrals', 'group' => 'referrals']);
        $staff->permissions()->attach($permission);

        $office = Office::create(['name' => 'City Health', 'type' => 'external', 'is_external' => true, 'is_active' => true]);
        $report = Report::factory()->create();

        $referral = Referral::create([
            'report_id' => $report->id,
            'office_id' => $office->id,
            'reference_no' => 'REF-HLTH-001',
            'status' => 'prepared',
            'referral_text' => 'Health inspection requested',
        ]);

        $fakeFile = UploadedFile::fake()->create('sanitation_checklist.pdf', 250, 'application/pdf');

        $uploadResponse = $this->actingAs($staff)->post(
            route('admin.referrals.attachments.upload', $referral),
            [
                'file' => $fakeFile,
                'label' => 'Sanitation Checklist',
            ]
        );

        $uploadResponse->assertRedirect();
        $attachment = ReferralAttachment::where('referral_id', $referral->id)->first();
        $this->assertNotNull($attachment);

        $downloadResponse = $this->actingAs($staff)->get(
            route('admin.referrals.attachments.download', [$referral, $attachment])
        );
        $downloadResponse->assertStatus(200);
    }

    public function test_printable_referral_letter_renders_correctly(): void
    {
        $staff = User::factory()->create(['role' => 'admin']);
        $office = Office::create(['name' => 'DSWD Field Office', 'type' => 'external', 'is_external' => true, 'is_active' => true]);
        $report = Report::factory()->create();

        $referral = Referral::create([
            'report_id' => $report->id,
            'office_id' => $office->id,
            'reference_no' => 'REF-DSWD-2026-009',
            'status' => 'sent',
            'referral_text' => 'Emergency shelter assistance required for family.',
        ]);

        $response = $this->actingAs($staff)->get(route('admin.referrals.letter', $referral));
        $response->assertStatus(200);
        $response->assertSee('REF-DSWD-2026-009');
        $response->assertSee('DSWD FIELD OFFICE');
        $response->assertSee('OFFICIAL REFERRAL NOTICE');
    }

    public function test_admin_dashboard_and_referral_index_handle_case_referrals_without_report(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $office = Office::create(['name' => 'Prosecutor Office', 'type' => 'external', 'is_external' => true, 'is_active' => true]);
        $case = BarangayCase::create([
            'fiscal_year_id' => $this->fy->id,
            'case_number' => 'CASE-2026-9999',
            'title' => 'Criminal Assault Case',
            'incident_type' => 'Assault',
            'narrative' => 'Assault case requiring prosecution escalation.',
            'status' => 'unsettled',
            'filed_on' => now()->subDays(10)->toDateString(),
            'encoded_by' => $admin->id,
        ]);

        $referral = Referral::create([
            'report_id' => null,
            'case_id' => $case->id,
            'office_id' => $office->id,
            'reference_no' => 'REF-CASE-9999',
            'status' => 'prepared',
            'referral_text' => 'Prosecution escalation',
            'follow_up_at' => now()->addDays(3),
        ]);

        // Dashboard test
        $dashResponse = $this->actingAs($admin)->get(route('admin.dashboard'));
        $dashResponse->assertStatus(200);
        $dashResponse->assertSee('REF-CASE-9999');
        $dashResponse->assertSee('CASE-2026-9999');

        // Referral index test
        $indexResponse = $this->actingAs($admin)->get(route('admin.referrals.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('REF-CASE-9999');
        $indexResponse->assertSee('CASE-2026-9999');
    }
}
