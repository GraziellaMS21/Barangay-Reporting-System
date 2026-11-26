<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Office;
use App\Models\Permission;
use App\Models\Purok;
use App\Models\Referral;
use App\Models\Report;
use App\Models\RoutingRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralsAndRoutingDirectoryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $staff;

    private User $ownerStaff;

    private Purok $purok;

    private Category $category;

    private Office $internalOffice;

    private Office $externalAgency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create(['name' => 'Captain Admin']);
        $this->staff = User::factory()->staff()->create(['name' => 'Desk Staff']);
        $this->staff->permissions()->sync([
            Permission::firstOrCreate(['slug' => 'reports.view'], ['label' => 'View Resident Reports Queue', 'group' => 'reports'])->id,
            Permission::firstOrCreate(['slug' => 'referrals.manage'], ['label' => 'Manage External Referrals', 'group' => 'referrals'])->id,
        ]);
        $this->ownerStaff = User::factory()->staff()->create(['name' => 'Field Assignee']);
        $this->purok = Purok::factory()->create(['name' => 'Purok 2']);
        $this->category = Category::create([
            'name' => 'Drainage',
            'slug' => 'drainage',
            'risk_weight' => 2,
            'is_active' => true,
        ]);

        $this->internalOffice = Office::create([
            'name' => 'Barangay Sanitation Office',
            'type' => 'internal',
            'description' => 'Handles barangay sanitation and canal clearing.',
            'is_internal' => true,
            'is_external' => false,
            'default_assignee_id' => $this->ownerStaff->id,
            'is_active' => true,
        ]);

        $this->externalAgency = Office::create([
            'name' => 'City Engineering Office',
            'type' => 'external',
            'description' => 'Handles city road and major drainage works.',
            'is_internal' => false,
            'is_external' => true,
            'contact_person' => 'Engr. Ana Reyes',
            'contact_phone' => '(062) 991-0001',
            'contact_email' => 'engineering@example.test',
            'is_active' => true,
        ]);
    }

    public function test_unified_referrals_and_routing_page_lists_both_internal_and_external_rows_and_filters_work(): void
    {
        $internalReport = Report::factory()->create([
            'tracking_code' => 'BRG-INT001',
            'purok_id' => $this->purok->id,
            'category_id' => $this->category->id,
            'office_id' => $this->internalOffice->id,
            'assigned_to' => $this->ownerStaff->id,
            'status' => 'in_progress',
            'due_at' => now()->subDay(), // Overdue internal SLA
        ]);

        $externalReport = Report::factory()->create([
            'tracking_code' => 'BRG-EXT001',
            'purok_id' => $this->purok->id,
            'category_id' => $this->category->id,
            'office_id' => $this->externalAgency->id,
            'status' => 'referred',
        ]);

        $referral = Referral::create([
            'report_id' => $externalReport->id,
            'office_id' => $this->externalAgency->id,
            'reference_no' => 'REF-2026-0101',
            'referral_text' => 'Please inspect the collapsed city culvert.',
            'status' => 'sent',
            'sent_at' => now()->subDays(2),
            'follow_up_at' => now()->addDays(5),
            'prepared_by' => $this->staff->id,
        ]);

        // Default follow_up_owner_id is set from prepared_by
        $this->assertSame($this->staff->id, $referral->fresh()->follow_up_owner_id);
        // No duplicate referral record created for internal report
        $this->assertDatabaseMissing('referrals', ['report_id' => $internalReport->id]);

        // 1. All tab shows both internal and external rows
        $this->actingAs($this->staff)
            ->get(route('admin.referrals.index'))
            ->assertOk()
            ->assertSee('Referrals and routing')
            ->assertSee('BRG-INT001')
            ->assertSee('Within barangay')
            ->assertSee('Barangay Sanitation Office')
            ->assertSee('Field Assignee')
            ->assertSee('Overdue')
            ->assertSee('REF-2026-0101')
            ->assertSee('External')
            ->assertSee('City Engineering Office')
            ->assertSee('Desk Staff');

        // 2. Filter by type = internal
        $this->actingAs($this->staff)
            ->get(route('admin.referrals.index', ['type' => 'internal']))
            ->assertOk()
            ->assertSee('BRG-INT001')
            ->assertDontSee('REF-2026-0101');

        // 3. Filter by type = external
        $this->actingAs($this->staff)
            ->get(route('admin.referrals.index', ['type' => 'external']))
            ->assertOk()
            ->assertSee('REF-2026-0101')
            ->assertDontSee('BRG-INT001');

        // 4. Filter by office_id
        $this->actingAs($this->staff)
            ->get(route('admin.referrals.index', ['office_id' => $this->internalOffice->id]))
            ->assertOk()
            ->assertSee('BRG-INT001')
            ->assertDontSee('REF-2026-0101');
    }

    public function test_admin_can_add_edit_and_deactivate_offices_while_non_admin_is_blocked(): void
    {
        // Staff cannot access directory management
        $this->actingAs($this->staff)
            ->get(route('admin.settings.offices.index'))
            ->assertForbidden();

        $this->actingAs($this->staff)
            ->post(route('admin.settings.offices.store'), [
                'name' => 'Unauthorized Office',
                'type' => 'internal',
            ])
            ->assertForbidden();

        // Admin can create an internal office with default assignee and description
        $this->actingAs($this->admin)
            ->post(route('admin.settings.offices.store'), [
                'name' => 'Barangay Health Committee',
                'type' => 'internal',
                'description' => 'Handles local health and dengue prevention.',
                'contact_person' => 'Nurse Clara',
                'contact_phone' => '09171234567',
                'default_assignee_id' => $this->ownerStaff->id,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.settings.offices.index'))
            ->assertSessionHas('success', 'Office added.');

        $created = Office::query()->where('name', 'Barangay Health Committee')->firstOrFail();
        $this->assertTrue($created->is_internal);
        $this->assertFalse($created->is_external);
        $this->assertTrue($created->is_active);
        $this->assertSame($this->ownerStaff->id, $created->default_assignee_id);
        $this->assertSame('Handles local health and dengue prevention.', $created->description);
        $this->assertDatabaseHas('audit_logs', [
            'model' => Office::class,
            'model_id' => $created->id,
            'action' => 'created',
        ]);

        // Admin can toggle active state
        $this->actingAs($this->admin)
            ->patch(route('admin.settings.offices.toggle-active', $created))
            ->assertRedirect(route('admin.settings.offices.index'));

        $this->assertFalse($created->fresh()->is_active);
    }

    public function test_inactive_offices_do_not_appear_in_route_and_assign_and_deactivating_office_with_open_reports_is_safe(): void
    {
        // Link an open report and a routing rule to internalOffice
        RoutingRule::create([
            'category_id' => $this->category->id,
            'office_id' => $this->internalOffice->id,
            'within_authority' => true,
            'jurisdiction_note' => 'Handled by Barangay Sanitation.',
        ]);

        $openReport = Report::factory()->create([
            'tracking_code' => 'BRG-OPEN01',
            'purok_id' => $this->purok->id,
            'category_id' => $this->category->id,
            'office_id' => $this->internalOffice->id,
            'status' => 'routed',
        ]);

        // Hard-deleting an office with open reports is blocked
        $this->actingAs($this->admin)
            ->delete(route('admin.settings.offices.destroy', $this->internalOffice))
            ->assertSessionHasErrors('office');
        $this->assertModelExists($this->internalOffice);

        // Deactivating the office succeeds safely
        $this->actingAs($this->admin)
            ->patch(route('admin.settings.offices.toggle-active', $this->internalOffice), [
                'is_active' => '0',
            ])
            ->assertRedirect(route('admin.settings.offices.index'));

        $this->assertFalse($this->internalOffice->fresh()->is_active);

        // Existing open report still appears on Referrals and routing page
        $this->actingAs($this->staff)
            ->get(route('admin.referrals.index'))
            ->assertOk()
            ->assertSee('BRG-OPEN01')
            ->assertSee('Barangay Sanitation Office');

        // Create a second active internal office
        $activeOffice = Office::create([
            'name' => 'Barangay Infrastructure Office',
            'type' => 'internal',
            'is_internal' => true,
            'is_external' => false,
            'is_active' => true,
        ]);

        // On a new unrouted report, the deactivated office does NOT appear in the Route and assign select
        $newReport = Report::factory()->create([
            'tracking_code' => 'BRG-NEW01',
            'purok_id' => $this->purok->id,
            'category_id' => $this->category->id,
            'urgency' => null,
            'status' => 'submitted',
        ]);

        $this->actingAs($this->staff)
            ->get(route('admin.reports.show', $newReport))
            ->assertOk()
            ->assertSee('Urgency:')
            ->assertSee('not set')
            ->assertDontSee('Priority: Unassigned')
            ->assertSee('The department that handles this report')
            ->assertSee('The staff member responsible for follow-through')
            ->assertSee('Barangay Infrastructure Office')
            ->assertDontSee('Barangay Sanitation Office');

        // Staff can Confirm and route with "Unassigned" (assigned_to = '')
        $this->actingAs($this->staff)
            ->post(route('admin.reports.review', $newReport), [
                'intent' => 'route',
                'authority_mode' => 'within',
                'category_id' => $this->category->id,
                'urgency' => 'Medium',
                'staff_description' => 'Reviewed drainage report.',
                'office_id' => $activeOffice->id,
                'assigned_to' => '',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $newReport->refresh();
        $this->assertSame('routed', $newReport->status);
        $this->assertSame($activeOffice->id, $newReport->office_id);
        $this->assertNull($newReport->assigned_to);
    }

    public function test_referrals_and_routing_hub_displays_kpis_and_office_directory_cards(): void
    {
        Report::factory()->create([
            'tracking_code' => 'BRG-INT99',
            'purok_id' => $this->purok->id,
            'category_id' => $this->category->id,
            'office_id' => $this->internalOffice->id,
            'assigned_to' => $this->ownerStaff->id,
            'status' => 'in_progress',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.referrals.index'))
            ->assertOk()
            ->assertSee('Referrals &amp; Routing Directory', false)
            ->assertSee('Destinations')
            ->assertSee('Internal referrals')
            ->assertSee('External referrals')
            ->assertSee('Overdue')
            ->assertSee('Add office or agency')
            ->assertSee('Barangay Sanitation Office')
            ->assertSee('City Engineering Office')
            ->assertSee('1 case');
    }

    public function test_selecting_an_office_filters_reports_for_that_destination_and_shows_office_summary(): void
    {
        $internalReport = Report::factory()->create([
            'tracking_code' => 'BRG-TARGET01',
            'purok_id' => $this->purok->id,
            'category_id' => $this->category->id,
            'office_id' => $this->internalOffice->id,
            'assigned_to' => $this->ownerStaff->id,
            'status' => 'in_progress',
        ]);

        $externalReport = Report::factory()->create([
            'tracking_code' => 'BRG-OTHER01',
            'purok_id' => $this->purok->id,
            'category_id' => $this->category->id,
            'office_id' => $this->externalAgency->id,
            'status' => 'referred',
        ]);

        Referral::create([
            'report_id' => $externalReport->id,
            'office_id' => $this->externalAgency->id,
            'reference_no' => 'REF-TARGET02',
            'referral_text' => 'City assistance requested.',
            'status' => 'sent',
            'sent_at' => now(),
            'prepared_by' => $this->staff->id,
        ]);

        // Drill down into internalOffice
        $response = $this->actingAs($this->staff)
            ->get(route('admin.referrals.index', ['office_id' => $this->internalOffice->id]))
            ->assertOk()
            ->assertSee('Barangay Sanitation Office')
            ->assertSee('BRG-TARGET01')
            ->assertDontSee('REF-TARGET02');

        // Clicking a report redirects directly to the review/show workspace
        $this->actingAs($this->staff)
            ->get(route('admin.reports.show', $internalReport))
            ->assertOk()
            ->assertSee('BRG-TARGET01');
    }

    public function test_admin_can_add_edit_toggle_and_delete_offices_with_redirect_back_to_hub(): void
    {
        $hubUrl = route('admin.referrals.index');

        // 1. Add office from hub modal
        $this->actingAs($this->admin)
            ->post(route('admin.settings.offices.store'), [
                'name' => 'Barangay Peace & Order Committee',
                'type' => 'internal',
                'description' => 'Security and conflict management.',
                'contact_person' => 'Chief Tanod',
                'contact_phone' => '09181112233',
                'default_assignee_id' => $this->ownerStaff->id,
                'is_active' => '1',
                '_redirect_to' => $hubUrl,
            ])
            ->assertRedirect($hubUrl)
            ->assertSessionHas('success', 'Office added.');

        $office = Office::where('name', 'Barangay Peace & Order Committee')->firstOrFail();
        $this->assertTrue($office->is_internal);

        // 2. Edit office from hub modal
        $this->actingAs($this->admin)
            ->put(route('admin.settings.offices.update', $office), [
                'name' => 'Barangay Peace & Public Safety',
                'type' => 'internal',
                'description' => 'Updated security description.',
                'contact_person' => 'Senior Tanod',
                'is_active' => '1',
                '_redirect_to' => $hubUrl,
            ])
            ->assertRedirect($hubUrl)
            ->assertSessionHas('success', 'Office updated.');

        $this->assertSame('Barangay Peace & Public Safety', $office->fresh()->name);

        // 3. Toggle active from hub card
        $this->actingAs($this->admin)
            ->patch(route('admin.settings.offices.toggle-active', $office), [
                '_redirect_to' => $hubUrl,
            ])
            ->assertRedirect($hubUrl)
            ->assertSessionHas('success');

        $this->assertFalse($office->fresh()->is_active);

        // 4. Delete office from hub (0 cases linked)
        $this->actingAs($this->admin)
            ->delete(route('admin.settings.offices.destroy', $office), [
                '_redirect_to' => $hubUrl,
            ])
            ->assertRedirect($hubUrl)
            ->assertSessionHas('success', 'Office deleted.');

        $this->assertModelMissing($office);
    }

    public function test_delete_guard_blocks_deleting_office_with_active_cases(): void
    {
        Report::factory()->create([
            'tracking_code' => 'BRG-BLOCK01',
            'purok_id' => $this->purok->id,
            'category_id' => $this->category->id,
            'office_id' => $this->internalOffice->id,
            'status' => 'routed',
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.settings.offices.destroy', $this->internalOffice), [
                '_redirect_to' => route('admin.referrals.index'),
            ])
            ->assertSessionHasErrors('office');

        $this->assertModelExists($this->internalOffice);
    }
}
