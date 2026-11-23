<?php

namespace Tests\Feature\People;

use App\Models\BarangayCase;
use App\Models\CaseType;
use App\Models\FiscalYear;
use App\Models\Permission;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\SignatoryAssignment;
use App\Services\SignatoryResolver;
use App\Support\CaseStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PersonnelAndSignatoriesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            ['slug' => 'personnel.manage', 'label' => 'Manage Personnel', 'group' => 'personnel'],
            ['slug' => 'cases.view', 'label' => 'View Cases', 'group' => 'cases'],
            ['slug' => 'cases.create', 'label' => 'Create Cases', 'group' => 'cases'],
            ['slug' => 'cases.edit', 'label' => 'Edit Cases', 'group' => 'cases'],
            ['slug' => 'cases.summons', 'label' => 'Issue Summons', 'group' => 'cases'],
            ['slug' => 'dashboard.view', 'label' => 'View Dashboard', 'group' => 'dashboard'],
        ] as $perm) {
            Permission::firstOrCreate(['slug' => $perm['slug']], $perm);
        }
    }

    public function test_positions_crud_and_cannot_delete_position_in_use(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.positions.index'))->assertOk();

        // Invalid code is rejected
        $this->post(route('admin.positions.store'), [
            'name' => 'Invalid Code Position',
            'code' => 'Invalid-Code!',
        ])->assertSessionHasErrors('code');

        // Valid creation
        $this->post(route('admin.positions.store'), [
            'name' => 'Pangkat Chairman',
            'code' => 'pangkat_chairman',
            'sort_order' => 5,
            'is_signatory_default' => '1',
            'is_active' => '1',
        ])->assertRedirect(route('admin.positions.index'));

        $position = Position::where('code', 'pangkat_chairman')->firstOrFail();
        $this->assertTrue($position->is_signatory_default);
        $this->assertFalse($position->is_confirmed);

        // Toggle active
        $this->post(route('admin.positions.toggle-active', $position))
            ->assertRedirect(route('admin.positions.index'));
        $this->assertFalse($position->fresh()->is_active);

        // Assign personnel so deletion is blocked
        Personnel::create([
            'position_id' => $position->id,
            'full_name' => 'Hon. Mario Santos',
            'term_start' => '2026-01-01',
            'is_active' => true,
        ]);

        $this->delete(route('admin.positions.destroy', $position))
            ->assertRedirect(route('admin.positions.index'))
            ->assertSessionHas('error');

        $this->assertModelExists($position);
    }

    public function test_personnel_creation_with_lupon_login_signature_and_deactivation(): void
    {
        Storage::fake('private');
        $this->actingAsAdmin();

        $luponPos = Position::create([
            'name' => 'Lupon Member',
            'code' => 'lupon_member',
            'sort_order' => 3,
            'is_signatory_default' => false,
            'is_active' => true,
        ]);

        $signatureFile = UploadedFile::fake()->image('sig.png', 200, 80);

        $this->post(route('admin.personnel.store'), [
            'full_name' => 'Kgd. Roberto Lim',
            'position_id' => $luponPos->id,
            'contact_number' => '0917-555-1234',
            'email' => 'roberto.lim@calarian.gov.ph',
            'term_start' => '2026-01-01',
            'is_signatory' => '1',
            'create_login' => '1',
            'signature' => $signatureFile,
        ])->assertRedirect(route('admin.personnel.index'))
            ->assertSessionHas('generated_password');

        $personnel = Personnel::where('full_name', 'Kgd. Roberto Lim')->firstOrFail();
        $this->assertNotNull($personnel->user_id);
        $this->assertNotNull($personnel->signature_path);
        Storage::disk('private')->assertExists($personnel->signature_path);

        // Lupon member receives case_encoder preset and must_change_password = true
        $linkedUser = $personnel->user;
        $this->assertSame('staff', $linkedUser->role);
        $this->assertTrue($linkedUser->must_change_password);
        $this->assertTrue($linkedUser->hasPermission('cases.create'));
        $this->assertFalse($linkedUser->hasPermission('cases.view_confidential'));

        // Deactivation requires a note of at least 5 characters
        $this->post(route('admin.personnel.deactivate', $personnel), [
            'term_end' => '2026-11-22',
            'note' => 'End',
        ])->assertSessionHasErrors('note');

        $this->post(route('admin.personnel.deactivate', $personnel), [
            'term_end' => '2026-11-22',
            'note' => 'Completed term as Lupon member.',
        ])->assertRedirect(route('admin.personnel.index'));

        $this->assertFalse($personnel->fresh()->is_active);
        $this->assertSame('2026-11-22', $personnel->fresh()->term_end?->toDateString());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'personnel.deactivated',
            'model_id' => $personnel->id,
        ]);
    }

    public function test_signatories_matrix_and_signatory_resolver_with_case_pangkat_chairman(): void
    {
        Storage::fake('private');
        $this->actingAsAdmin();

        $pbPos = Position::create(['name' => 'Punong Barangay', 'code' => 'punong_barangay', 'sort_order' => 1]);
        $secPos = Position::create(['name' => 'Barangay Secretary', 'code' => 'barangay_secretary', 'sort_order' => 2]);
        $pangkatPos = Position::create(['name' => 'Pangkat Chairman', 'code' => 'pangkat_chairman', 'sort_order' => 3]);

        $captain = Personnel::create([
            'position_id' => $pbPos->id,
            'full_name' => 'Hon. Eduardo Alvarez',
            'term_start' => '2025-01-01',
            'is_signatory' => true,
            'is_active' => true,
        ]);

        $secretary = Personnel::create([
            'position_id' => $secPos->id,
            'full_name' => 'Maria Clara Delos Santos',
            'term_start' => '2025-01-01',
            'is_signatory' => true,
            'is_active' => true,
        ]);

        $defaultChairman = Personnel::create([
            'position_id' => $pangkatPos->id,
            'full_name' => 'Default Pangkat Chair',
            'term_start' => '2025-01-01',
            'is_signatory' => true,
            'is_active' => true,
        ]);

        $caseChairman = Personnel::create([
            'position_id' => $pangkatPos->id,
            'full_name' => 'Case-Assigned Pangkat Chair',
            'term_start' => '2025-01-01',
            'is_signatory' => true,
            'is_active' => true,
        ]);

        // Update signatory matrix via controller
        $this->post(route('admin.signatories.update'), [
            'assignments' => [
                'summons' => [
                    'prepared_by' => $secPos->id,
                    'noted_by' => '',
                    'approved_by' => $pbPos->id,
                ],
                'certificate_to_file_action' => [
                    'prepared_by' => $secPos->id,
                    'noted_by' => '',
                    'approved_by' => $pangkatPos->id,
                ],
            ],
            'confirm_all' => '1',
        ])->assertRedirect(route('admin.signatories.edit'));

        $this->assertEquals(4, SignatoryAssignment::where('is_confirmed', true)->count());

        $resolver = app(SignatoryResolver::class);

        // 1. Resolve summons signatories
        $summonsSigs = $resolver->forDocument('summons');
        $this->assertCount(2, $summonsSigs);
        $this->assertSame('Prepared by', $summonsSigs[0]['label']);
        $this->assertSame('Maria Clara Delos Santos', $summonsSigs[0]['name']);
        $this->assertSame('Approved by', $summonsSigs[1]['label']);
        $this->assertSame('Hon. Eduardo Alvarez', $summonsSigs[1]['name']);

        // 2. Resolve CFA with case-specific Pangkat Chairman
        $fy = FiscalYear::firstOrFail();
        $caseType = CaseType::create(['name' => 'Dispute', 'slug' => 'dispute']);
        $case = BarangayCase::create([
            'case_number' => '11-001-2026',
            'fiscal_year_id' => $fy->id,
            'case_type_id' => $caseType->id,
            'title' => 'Dispute Case',
            'narrative' => 'Test narrative for CFA signatory resolution.',
            'filed_on' => '2026-11-01',
            'encoded_by' => auth()->id(),
            'status' => CaseStatus::UNSETTLED,
            'pangkat_chairman_personnel_id' => $caseChairman->id,
        ]);

        $cfaSigs = $resolver->forDocument('certificate_to_file_action', null, $case);
        $this->assertCount(2, $cfaSigs);
        $this->assertSame('Case-Assigned Pangkat Chair', $cfaSigs[1]['name']);
    }
}
