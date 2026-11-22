<?php

namespace Tests\Feature\Cases;

use App\Models\BarangayCase;
use App\Models\CaseParty;
use App\Models\CaseType;
use App\Models\FiscalYear;
use App\Models\Party;
use App\Models\Purok;
use App\Models\User;
use App\Services\Cases\PartyMatchingService;
use App\Support\CaseStatus;
use Database\Seeders\RequiredDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartyDirectoryAndMergeTest extends TestCase
{
    use RefreshDatabase;

    protected User $staff;
    protected Purok $purok;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RequiredDataSeeder::class);

        $this->staff = User::factory()->create(['role' => 'staff']);
        $permissions = \App\Models\Permission::whereIn('slug', ['cases.view', 'cases.edit'])->get();
        $this->staff->permissions()->sync($permissions);

        $this->purok = Purok::first() ?? Purok::create(['name' => 'Purok 1']);
    }

    public function test_party_directory_lists_parties_with_filters(): void
    {
        $this->actingAs($this->staff);

        $party = Party::create([
            'full_name' => 'Crisostomo Ibarra',
            'alias' => 'Simoun',
            'residency_status' => 'resident',
            'purok_id' => $this->purok->id,
            'contact_number' => '0917-123-4567',
        ]);

        $response = $this->get('/admin/parties');
        $response->assertOk();
        $response->assertSee('Crisostomo Ibarra');
        $response->assertSee('Simoun');
        $response->assertSee('Resident');
    }

    public function test_party_search_autocomplete_returns_json_results(): void
    {
        $this->actingAs($this->staff);

        Party::create([
            'full_name' => 'Maria Clara Dela Santos',
            'residency_status' => 'resident',
            'purok_id' => $this->purok->id,
            'contact_number' => '0919-888-7777',
        ]);

        $response = $this->getJson('/admin/parties/search?q=Maria');
        $response->assertOk();
        $response->assertJsonFragment([
            'full_name' => 'Maria Clara Dela Santos',
            'residency_status' => 'resident',
        ]);
    }

    public function test_party_profile_displays_case_history_and_details(): void
    {
        $this->actingAs($this->staff);

        $fy = FiscalYear::firstOrCreate(
            ['year' => 2026],
            ['label' => 'FY 2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'status' => 'active']
        );
        $caseType = CaseType::first() ?? CaseType::create(['name' => 'Dispute', 'slug' => 'dispute']);

        $party = Party::create([
            'full_name' => 'Elias Salome',
            'residency_status' => 'resident',
            'purok_id' => $this->purok->id,
        ]);

        $case = BarangayCase::create([
            'case_number' => 'CASE-2026-0005',
            'fiscal_year_id' => $fy->id,
            'case_type_id' => $caseType->id,
            'title' => 'Elias Dispute',
            'narrative' => 'Narrative facts statement for history testing.',
            'filed_on' => '2026-02-15',
            'encoded_by' => $this->staff->id,
            'status' => CaseStatus::FILED,
        ]);

        CaseParty::create([
            'case_id' => $case->id,
            'party_id' => $party->id,
            'role' => 'complainant',
            'residency_at_filing' => 'resident',
        ]);

        $response = $this->get("/admin/parties/{$party->id}");
        $response->assertOk();
        $response->assertSee('Elias Salome');
        $response->assertSee('CASE-2026-0005');
        $response->assertSee('complainant');
    }

    public function test_staff_can_update_party_record(): void
    {
        $this->actingAs($this->staff);

        $party = Party::create([
            'full_name' => 'Old Name',
            'residency_status' => 'unverified',
        ]);

        $response = $this->put("/admin/parties/{$party->id}", [
            'full_name' => 'Updated Legal Name',
            'alias' => 'New Alias',
            'residency_status' => 'resident',
            'purok_id' => $this->purok->id,
            'contact_number' => '0917-000-1111',
        ]);

        $response->assertRedirect(route('admin.parties.show', $party));
        $this->assertDatabaseHas('parties', [
            'id' => $party->id,
            'full_name' => 'Updated Legal Name',
            'alias' => 'New Alias',
            'residency_status' => 'resident',
        ]);
    }

    public function test_staff_can_verify_residency_with_document_basis(): void
    {
        $this->actingAs($this->staff);

        $party = Party::create([
            'full_name' => 'Sisa Tagalog',
            'residency_status' => 'unverified',
        ]);

        $response = $this->post("/admin/parties/{$party->id}/verify-residency", [
            'residency_status' => 'resident',
            'residency_basis' => 'Barangay Calarian ID #4412',
            'verification_notes' => 'Verified against 2026 household register.',
        ]);

        $response->assertRedirect(route('admin.parties.show', $party));
        $fresh = $party->fresh();
        $this->assertSame('resident', $fresh->residency_status);
        $this->assertSame('Barangay Calarian ID #4412', $fresh->residency_basis);
        $this->assertEquals($this->staff->id, $fresh->residency_verified_by);
        $this->assertNotNull($fresh->residency_verified_at);
    }

    public function test_duplicate_party_matching_service_finds_similar_names_and_contacts(): void
    {
        $matcher = app(PartyMatchingService::class);

        Party::create([
            'full_name' => 'Juan Dela Cruz',
            'birth_date' => '1990-05-15',
            'contact_number' => '0917-123-4567',
            'residency_status' => 'resident',
        ]);

        // Exact name match
        $matches = $matcher->findPossibleDuplicates('Juan Dela Cruz');
        $this->assertCount(1, $matches);

        // Typos / minor differences
        $typoMatches = $matcher->findPossibleDuplicates('Juan Del Cruz');
        $this->assertCount(1, $typoMatches);

        // Contact number matching with high similarity
        $phoneMatches = $matcher->findPossibleDuplicates('J. Dela Cruz', null, '0917-123-4567');
        $this->assertCount(1, $phoneMatches);
    }

    public function test_party_merge_repoints_case_parties_and_archives_source_record(): void
    {
        $this->actingAs($this->staff);

        $fy = FiscalYear::firstOrCreate(
            ['year' => 2026],
            ['label' => 'FY 2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'status' => 'active']
        );
        $caseType = CaseType::first() ?? CaseType::create(['name' => 'Dispute', 'slug' => 'dispute']);

        $masterParty = Party::create([
            'full_name' => 'Juan M. Dela Cruz',
            'residency_status' => 'resident',
            'purok_id' => $this->purok->id,
            'contact_number' => null, // empty, to be backfilled
        ]);

        $duplicateParty = Party::create([
            'full_name' => 'Juan Dela Cruz',
            'residency_status' => 'resident',
            'contact_number' => '0917-555-4321', // will backfill into master
        ]);

        $case = BarangayCase::create([
            'case_number' => 'CASE-2026-0008',
            'fiscal_year_id' => $fy->id,
            'case_type_id' => $caseType->id,
            'title' => 'Merge Test Case',
            'narrative' => 'Case narrative for checking party merge repointing.',
            'filed_on' => '2026-02-18',
            'encoded_by' => $this->staff->id,
            'status' => CaseStatus::FILED,
        ]);

        // Linked to duplicate
        CaseParty::create([
            'case_id' => $case->id,
            'party_id' => $duplicateParty->id,
            'role' => 'complainant',
            'residency_at_filing' => 'resident',
        ]);

        $response = $this->post("/admin/parties/{$duplicateParty->id}/merge", [
            'keep_party_id' => $masterParty->id,
            'note' => 'Confirmed duplicate person with missing middle initial.',
            'confirm_understanding' => '1',
        ]);

        $response->assertRedirect(route('admin.parties.show', $masterParty));

        // Duplicate is marked merged
        $this->assertNotNull($duplicateParty->fresh()->merged_at);
        $this->assertEquals($masterParty->id, $duplicateParty->fresh()->merged_into_id);

        // CaseParty is re-pointed to master
        $this->assertDatabaseHas('case_parties', [
            'case_id' => $case->id,
            'party_id' => $masterParty->id,
            'role' => 'complainant',
        ]);

        // Master got contact number backfilled
        $this->assertSame('0917-555-4321', $masterParty->fresh()->contact_number);

        // Accessing duplicate redirects to master
        $showResponse = $this->get("/admin/parties/{$duplicateParty->id}");
        $showResponse->assertRedirect(route('admin.parties.show', $masterParty));
    }
}
