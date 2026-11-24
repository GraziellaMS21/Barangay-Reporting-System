<?php

namespace Tests\Feature;

use App\Models\BarangayCase;
use App\Models\Category;
use App\Models\Office;
use App\Models\Purok;
use App\Models\Report;
use App\Models\ResponseTemplate;
use App\Models\User;
use App\Services\PermissionService;
use App\Support\CaseStatus;
use App\Support\ReportStatus;
use Database\Seeders\RequiredDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Priority1FixesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RequiredDataSeeder::class);
    }

    public function test_staff_with_cases_edit_can_record_settlement(): void
    {
        Storage::fake('private');

        $user = $this->actingAsEncoderUser();
        $case = BarangayCase::factory()->create([
            'record_type' => 'complaint',
            'status' => CaseStatus::IN_MEDIATION,
            'settled_on' => null,
        ]);

        $response = $this->actingAs($user)->post(route('admin.cases.settlement.store', $case), [
            'settled_on' => '2026-11-24',
            'terms' => 'Respondent agrees to pay 5,000 PHP in two equal installments.',
        ]);

        $response->assertRedirect(route('admin.cases.show', $case));
    }

    public function test_staff_with_cases_edit_can_download_settlement_agreement(): void
    {
        Storage::fake('private');

        $path = 'case-files/1/test-agreement.pdf';
        Storage::disk('private')->put($path, 'sample pdf content');

        $user = $this->actingAsEncoderUser();
        $case = BarangayCase::factory()->create([
            'record_type' => 'complaint',
            'status' => CaseStatus::SETTLED,
            'settlement_agreement_path' => $path,
            'is_confidential' => false,
        ]);

        $this->actingAs($user)
            ->get(route('admin.cases.settlement.agreement', $case))
            ->assertOk();
    }

    public function test_staff_without_cases_edit_cannot_access_settlement_routes(): void
    {
        Storage::fake('private');

        $user = User::factory()->create(['role' => 'staff', 'is_active' => true]);
        app(PermissionService::class)->applyPreset($user, 'reports_analyst');

        $case = BarangayCase::factory()->create([
            'record_type' => 'complaint',
            'status' => CaseStatus::IN_MEDIATION,
            'settled_on' => null,
        ]);

        $this->actingAs($user)
            ->post(route('admin.cases.settlement.store', $case), [
                'settled_on' => '2026-11-24',
                'terms' => 'Respondent agrees to pay 5,000 PHP in two equal installments.',
            ])
            ->assertForbidden();

        $path = 'case-files/1/test-agreement.pdf';
        Storage::disk('private')->put($path, 'sample pdf content');
        $case2 = BarangayCase::factory()->create([
            'record_type' => 'complaint',
            'status' => CaseStatus::SETTLED,
            'settlement_agreement_path' => $path,
            'is_confidential' => false,
        ]);

        $this->actingAs($user)
            ->get(route('admin.cases.settlement.agreement', $case2))
            ->assertForbidden();
    }

    public function test_staff_without_templates_manage_can_load_templates_json(): void
    {
        $user = User::factory()->create(['role' => 'staff', 'is_active' => true]);
        app(PermissionService::class)->applyPreset($user, 'case_encoder');

        ResponseTemplate::create([
            'title' => 'First Summons Notice',
            'name' => 'First Summons Notice',
            'kind' => 'hearing_notice',
            'body' => 'Please appear before the Punong Barangay.',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('admin.templates.json', ['kind' => 'hearing_notice']))
            ->assertOk()
            ->assertJsonFragment(['title' => 'First Summons Notice']);
    }

    public function test_staff_without_templates_manage_cannot_manage_templates(): void
    {
        $user = User::factory()->create(['role' => 'staff', 'is_active' => true]);
        app(PermissionService::class)->applyPreset($user, 'case_encoder');

        $this->actingAs($user)
            ->get(route('admin.templates.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('admin.templates.store'), [
                'title' => 'Test Template',
                'kind' => 'general',
                'body' => 'Body',
            ])
            ->assertForbidden();
    }

    public function test_unauthenticated_and_agency_cannot_access_templates_json(): void
    {
        $this->get(route('admin.templates.json'))->assertRedirect(route('login'));

        $agency = User::factory()->create(['role' => 'agency', 'is_active' => true]);
        $this->actingAs($agency)
            ->get(route('admin.templates.json'))
            ->assertForbidden();
    }

    public function test_converted_to_case_reason_persists(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'is_active' => true]);
        app(PermissionService::class)->applyPreset($staff, 'staff_full');

        $purok = Purok::factory()->create(['name' => 'Purok 1']);
        $office = Office::create(['name' => 'Barangay Works', 'type' => 'barangay', 'is_internal' => true]);
        $category = Category::create(['name' => 'Drainage Test', 'slug' => 'drainage-test', 'default_office_id' => $office->id, 'is_active' => true]);

        $report = Report::factory()->create([
            'purok_id' => $purok->id,
            'category_id' => $category->id,
            'status' => ReportStatus::SUBMITTED,
        ]);

        $this->actingAs($staff)
            ->patch(route('admin.reports.status', $report), [
                'status' => ReportStatus::NO_ACTION,
                'no_action_reason' => ReportStatus::REASON_CONVERTED_TO_CASE,
                'note' => 'Converted to dispute case.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $report->refresh();
        $this->assertSame(ReportStatus::NO_ACTION, $report->status);
        $this->assertSame('converted_to_case', $report->no_action_reason);

        $this->assertDatabaseHas('status_logs', [
            'report_id' => $report->id,
            'from_status' => ReportStatus::SUBMITTED,
            'to_status' => ReportStatus::NO_ACTION,
            'no_action_reason' => 'converted_to_case',
        ]);
    }

    private function actingAsEncoderUser(): User
    {
        $user = User::factory()->create(['role' => 'staff', 'is_active' => true]);
        app(PermissionService::class)->applyPreset($user, 'case_encoder');

        return $user;
    }
}
