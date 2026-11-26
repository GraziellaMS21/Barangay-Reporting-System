<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DuplicateSuggestion;
use App\Models\Office;
use App\Models\Permission;
use App\Models\Referral;
use App\Models\Report;
use App\Models\User;
use App\Services\PermissionService;
use Database\Seeders\RequiredDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoutePermissionHardeningTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    private Office $externalOffice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RequiredDataSeeder::class);
        $this->category = Category::query()->where('is_active', true)->firstOrFail();
        $this->externalOffice = Office::query()->where('is_external', true)->first()
            ?? Office::create(['name' => 'City Engineering', 'type' => 'city', 'is_internal' => false, 'is_active' => true]);
    }

    public function test_bare_staff_without_permissions_receives_403_on_dashboard_reports_and_referrals(): void
    {
        $bareStaff = User::factory()->staff()->create();
        $report = Report::factory()->create(['category_id' => $this->category->id, 'status' => 'submitted']);
        $candidate = Report::factory()->create(['category_id' => $this->category->id]);
        $suggestion = DuplicateSuggestion::create([
            'report_id' => $report->id,
            'candidate_id' => $candidate->id,
            'score' => 0.80,
            'decision' => 'pending',
        ]);
        $referral = Referral::create([
            'report_id' => $report->id,
            'office_id' => $this->externalOffice->id,
            'referral_text' => 'Referral text.',
            'status' => 'prepared',
        ]);

        $this->actingAs($bareStaff)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($bareStaff)->get(route('admin.dashboard.legacy'))->assertForbidden();
        $this->actingAs($bareStaff)->get(route('admin.reports.index'))->assertForbidden();
        $this->actingAs($bareStaff)->get(route('admin.reports.show', $report))->assertForbidden();
        $this->actingAs($bareStaff)->get(route('admin.map'))->assertForbidden();
        $this->actingAs($bareStaff)->get(route('admin.referrals.index'))->assertForbidden();

        // Mutations are blocked and leave records untouched
        $this->actingAs($bareStaff)
            ->patch(route('admin.reports.duplicate.dismiss', [$report, $suggestion]))
            ->assertForbidden();
        $this->actingAs($bareStaff)
            ->post(route('admin.referrals.mark-sent', $referral))
            ->assertForbidden();

        $this->assertSame('pending', $suggestion->fresh()->decision);
        $this->assertSame('prepared', $referral->fresh()->status);
    }

    public function test_restricted_presets_can_access_dashboard_but_not_reports_or_referrals(): void
    {
        $report = Report::factory()->create(['category_id' => $this->category->id, 'status' => 'submitted']);
        $referral = Referral::create([
            'report_id' => $report->id,
            'office_id' => $this->externalOffice->id,
            'referral_text' => 'Referral text.',
            'status' => 'prepared',
        ]);

        foreach (['case_encoder', 'reports_analyst', 'minor_kagawad'] as $preset) {
            $user = User::factory()->staff()->create();
            app(PermissionService::class)->applyPreset($user, $preset);

            $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();
            $this->actingAs($user)->get(route('admin.reports.index'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.reports.show', $report))->assertForbidden();
            $this->actingAs($user)->get(route('admin.reports.export'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.map'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.referrals.index'))->assertForbidden();
            $this->actingAs($user)->post(route('admin.referrals.close', $referral), [
                'outcome' => 'resolved',
                'note' => 'Unauthorized close attempt.',
            ])->assertForbidden();

            $this->assertSame('prepared', $referral->fresh()->status);
        }
    }

    public function test_case_reviewer_and_vaw_focal_can_access_reports_but_not_phone_intake_or_referrals(): void
    {
        $report = Report::factory()->create(['category_id' => $this->category->id, 'status' => 'submitted']);
        $referral = Referral::create([
            'report_id' => $report->id,
            'office_id' => $this->externalOffice->id,
            'referral_text' => 'Referral text.',
            'status' => 'prepared',
        ]);

        foreach (['case_reviewer', 'vaw_focal'] as $preset) {
            $user = User::factory()->staff()->create();
            app(PermissionService::class)->applyPreset($user, $preset);

            $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();
            $this->actingAs($user)->get(route('admin.reports.index'))->assertOk();
            $this->actingAs($user)->get(route('admin.reports.show', $report))->assertOk();
            $this->actingAs($user)->get(route('admin.map'))->assertOk();

            $this->actingAs($user)->get(route('admin.reports.phone.create'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.referrals.index'))->assertForbidden();
            $this->actingAs($user)->get(route('admin.referrals.letter', $referral))->assertForbidden();
            $this->actingAs($user)->post(route('admin.referrals.mark-sent', $referral))->assertForbidden();

            $this->assertSame('prepared', $referral->fresh()->status);
        }
    }

    public function test_staff_full_and_admin_can_access_dashboard_reports_phone_and_referrals(): void
    {
        $report = Report::factory()->create(['category_id' => $this->category->id, 'status' => 'submitted']);
        $referral = Referral::create([
            'report_id' => $report->id,
            'office_id' => $this->externalOffice->id,
            'referral_text' => 'Referral text.',
            'status' => 'prepared',
        ]);

        $staffFull = User::factory()->staff()->create();
        app(PermissionService::class)->applyPreset($staffFull, 'staff_full');

        $admin = User::factory()->admin()->create();

        foreach ([$staffFull, $admin] as $user) {
            $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();
            $this->actingAs($user)->get(route('admin.reports.index'))->assertOk();
            $this->actingAs($user)->get(route('admin.reports.show', $report))->assertOk();
            $this->actingAs($user)->get(route('admin.reports.phone.create'))->assertOk();
            $this->actingAs($user)->get(route('admin.map'))->assertOk();
            $this->actingAs($user)->get(route('admin.referrals.index'))->assertOk();
            $this->actingAs($user)->get(route('admin.referrals.letter', $referral))->assertOk();
        }
    }

    public function test_admin_reports_refer_requires_both_reports_view_and_referrals_manage(): void
    {
        $report = Report::factory()->create(['category_id' => $this->category->id, 'status' => 'under_review']);

        $reportsViewPerm = Permission::where('slug', 'reports.view')->firstOrFail();
        $referralsManagePerm = Permission::where('slug', 'referrals.manage')->firstOrFail();

        // User with ONLY reports.view -> 403
        $onlyReports = User::factory()->staff()->create();
        $onlyReports->permissions()->sync([$reportsViewPerm->id]);

        $this->actingAs($onlyReports)
            ->patch(route('admin.reports.refer', $report), [
                'office_id' => $this->externalOffice->id,
                'note' => 'Only reports.view',
            ])
            ->assertForbidden();

        // User with ONLY referrals.manage -> 403
        $onlyReferrals = User::factory()->staff()->create();
        $onlyReferrals->permissions()->sync([$referralsManagePerm->id]);

        $this->actingAs($onlyReferrals)
            ->patch(route('admin.reports.refer', $report), [
                'office_id' => $this->externalOffice->id,
                'note' => 'Only referrals.manage',
            ])
            ->assertForbidden();

        $this->assertSame('under_review', $report->fresh()->status);
        $this->assertDatabaseCount('referrals', 0);

        // User with BOTH -> succeeds
        $both = User::factory()->staff()->create();
        $both->permissions()->sync([$reportsViewPerm->id, $referralsManagePerm->id]);

        $this->actingAs($both)
            ->patch(route('admin.reports.refer', $report), [
                'office_id' => $this->externalOffice->id,
                'note' => 'Authorized referral.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('referred', $report->fresh()->status);
        $this->assertDatabaseCount('referrals', 1);
    }
}
