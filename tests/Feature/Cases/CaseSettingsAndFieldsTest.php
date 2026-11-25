<?php

namespace Tests\Feature\Cases;

use App\Models\BarangayCase;
use App\Models\CaseType;
use App\Models\FiscalYear;
use App\Models\Permission;
use App\Models\User;
use App\Support\CaseStatus;
use Database\Seeders\RequiredDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseSettingsAndFieldsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RequiredDataSeeder::class);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->staff = User::factory()->create(['role' => 'staff']);

        $settingsPermission = Permission::where('slug', 'settings.manage')->first();
        if ($settingsPermission) {
            $this->staff->permissions()->attach($settingsPermission);
        }
    }

    public function test_case_types_index_lists_classifications(): void
    {
        $this->actingAs($this->staff);

        $response = $this->get('/admin/settings/case-types');
        $response->assertOk();
        $response->assertSee('Dispute Classifications');
    }

    public function test_user_with_permission_can_create_and_update_case_type(): void
    {
        $this->actingAs($this->staff);

        // Create
        $response = $this->post('/admin/settings/case-types', [
            'name' => 'Fencing Violation',
            'description' => 'Erecting fences exceeding legal easements.',
            'sort_order' => 10,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.case-types.index'));
        $this->assertDatabaseHas('case_types', [
            'name' => 'Fencing Violation',
            'slug' => 'fencing-violation',
            'sort_order' => 10,
        ]);

        $createdType = CaseType::where('slug', 'fencing-violation')->first();

        // Update
        $updateResponse = $this->put("/admin/settings/case-types/{$createdType->id}", [
            'name' => 'Illegal Fencing and Encroachment',
            'description' => 'Updated description.',
            'sort_order' => 12,
            'is_active' => '1',
        ]);

        $updateResponse->assertRedirect(route('admin.case-types.index'));
        $this->assertDatabaseHas('case_types', [
            'id' => $createdType->id,
            'name' => 'Illegal Fencing and Encroachment',
            'sort_order' => 12,
        ]);
    }

    public function test_cannot_delete_case_type_with_associated_cases(): void
    {
        $this->actingAs($this->staff);

        $fy = FiscalYear::firstOrCreate(
            ['year' => 2026],
            ['label' => 'FY 2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'status' => 'active']
        );

        $caseType = CaseType::create(['name' => 'In-Use Dispute', 'slug' => 'in-use-dispute']);

        BarangayCase::create([
            'case_number' => 'CASE-2026-INUSE',
            'fiscal_year_id' => $fy->id,
            'case_type_id' => $caseType->id,
            'title' => 'In Use Test Case',
            'narrative' => 'Case narrative facts statement for checking delete guard.',
            'filed_on' => '2026-02-18',
            'encoded_by' => $this->staff->id,
            'status' => CaseStatus::FILED,
        ]);

        $response = $this->delete("/admin/settings/case-types/{$caseType->id}");
        $response->assertSessionHasErrors(['error']);
        $this->assertDatabaseHas('case_types', ['id' => $caseType->id]);
    }

    public function test_can_delete_unused_case_type(): void
    {
        $this->actingAs($this->staff);

        $caseType = CaseType::create(['name' => 'Unused Type', 'slug' => 'unused-type']);

        $response = $this->delete("/admin/settings/case-types/{$caseType->id}");
        $response->assertRedirect(route('admin.case-types.index'));
        $this->assertDatabaseMissing('case_types', ['id' => $caseType->id]);
    }
}
