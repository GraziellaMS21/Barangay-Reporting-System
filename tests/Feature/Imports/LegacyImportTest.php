<?php

namespace Tests\Feature\Imports;

use App\Models\AuditLog;
use App\Models\BarangayCase;
use App\Models\CaseType;
use App\Models\FiscalYear;
use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\NumberSequence;
use App\Models\Party;
use App\Models\Permission;
use App\Models\User;
use App\Services\GlobalSearchService;
use App\Services\Imports\SpreadsheetFileService;
use App\Support\CaseStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LegacyImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private');

        Permission::firstOrCreate(
            ['slug' => 'imports.manage'],
            ['label' => 'Manage Legacy File Imports', 'group' => 'imports']
        );

        CaseType::firstOrCreate(
            ['slug' => 'boundary_dispute'],
            [
                'name' => 'Boundary and Property Line Dispute',
                'description' => 'Boundary disputes',
                'sort_order' => 1,
                'is_active' => true,
                'is_confirmed' => false,
            ]
        );
    }

    public function test_unprivileged_staff_cannot_access_legacy_imports(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($staff);

        $this->get(route('admin.imports.index'))->assertForbidden();
        $this->get(route('admin.imports.create'))->assertForbidden();
        $this->get(route('admin.imports.template'))->assertForbidden();
    }

    public function test_admin_can_download_blank_xlsx_and_csv_templates(): void
    {
        $this->actingAsAdmin();

        $csvResponse = $this->get(route('admin.imports.template', ['format' => 'csv']));
        $csvResponse->assertOk();
        $this->assertStringContainsString('case_number', $csvResponse->streamedContent());

        $xlsxResponse = $this->get(route('admin.imports.template'));
        $xlsxResponse->assertOk();
    }

    public function test_100_row_sample_imports_with_preview_conflicts_rollback_and_global_search(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        // Pre-seed 1 existing case to trigger a Rule 1 case_number conflict on Row 1,
        // and 1 existing case to trigger a Rule 5 30-day duplicate warning on Row 2.
        $fy2024 = FiscalYear::firstOrCreate(
            ['year' => 2024],
            [
                'label' => 'FY 2024',
                'starts_on' => '2024-01-01',
                'ends_on' => '2024-12-31',
                'status' => 'closed',
                'is_locked' => true,
            ]
        );
        $caseType = CaseType::first();

        $existingCase = BarangayCase::create([
            'case_number' => '01-001-2024',
            'fiscal_year_id' => $fy2024->id,
            'case_type_id' => $caseType->id,
            'record_type' => 'complaint',
            'source' => 'direct',
            'title' => 'Existing Case 01-001-2024',
            'narrative' => 'Pre-existing case that must not be overwritten.',
            'filed_on' => '2024-01-10',
            'status' => CaseStatus::FILED,
            'encoded_by' => $admin->id,
        ]);

        // Pre-seed an existing party for exact matching (name + birth_date) and another for likely matching (name only)
        $exactExistingParty = Party::create([
            'full_name' => 'Elena G. Santos',
            'birth_date' => '1980-04-12',
            'contact_number' => '09171112222',
            'residency_status' => 'resident',
        ]);

        $likelyExistingParty = Party::create([
            'full_name' => 'Ricardo M. Dalisay',
            'birth_date' => '1975-09-01',
            'contact_number' => '09189998888',
            'residency_status' => 'resident',
        ]);

        $existingCase->parties()->attach($exactExistingParty->id, ['role' => 'complainant', 'residency_at_filing' => 'resident']);
        $existingCase->parties()->attach($likelyExistingParty->id, ['role' => 'respondent', 'residency_at_filing' => 'resident']);

        // Record baseline sequence count to verify Rule 2 (NumberingService is never called)
        $initialSequenceCount = NumberSequence::count();

        // Build a 100-row CSV across 2023, 2024, and 2025:
        // - Row 1: Conflict (case_number '01-001-2024' already exists)
        // - Row 2: 30-day duplicate warning (Elena G. Santos vs Ricardo M. Dalisay on 2024-01-20, plus Ricardo has no DOB/phone so triggers likely party match)
        // - Row 3: Unknown legacy status ('Archived in Box') -> defaults to 'filed' with a note
        // - Row 4: Blotter-only entry ('blotter')
        // - Rows 5..100: Valid historical cases across 2023, 2024, 2025
        $headers = [
            'case_number',
            'record_type',
            'filed_on',
            'incident_date',
            'incident_location',
            'case_type',
            'purok',
            'title',
            'narrative',
            'status',
            'complainant_full_name',
            'complainant_birth_date',
            'complainant_contact',
            'complainant_address',
            'complainant_residency',
            'respondent_full_name',
            'respondent_birth_date',
            'respondent_contact',
            'respondent_address',
            'respondent_residency',
        ];

        $fh = fopen('php://temp', 'r+');
        fputcsv($fh, $headers);

        // Row 1: Conflict case number
        fputcsv($fh, [
            '01-001-2024',
            'complaint',
            '2024-01-10',
            '2024-01-09',
            'Purok 1, Calarian',
            'Boundary and Property Line Dispute',
            'Purok 1',
            'Conflict Case Title',
            'Conflict narrative text that should not overwrite existing case.',
            'Settled',
            'Juan Conflict',
            '1985-01-01',
            '09170000001',
            'Purok 1',
            'resident',
            'Pedro Conflict',
            '1984-01-01',
            '09170000002',
            'Purok 1',
            'resident',
        ]);

        // Row 2: Same complainant & respondent within 30 days of 01-001-2024 + exact complainant match + likely respondent match
        fputcsv($fh, [
            '01-002-2024',
            'complaint',
            '2024-01-20',
            '2024-01-18',
            'Purok 2, Calarian',
            'Boundary and Property Line Dispute',
            'Purok 2',
            'Santos vs. Dalisay Follow-up',
            'Second complaint filed 10 days after initial dispute between same parties.',
            'In Mediation',
            'Elena G. Santos',
            '1980-04-12', // matches $exactExistingParty
            '09171112222',
            'Purok 2',
            'resident',
            'Ricardo M. Dalisay',
            '', // no birth date or phone -> triggers likely party match warning, must NOT auto-merge
            '',
            'Purok 2',
            'resident',
        ]);

        // Row 3: Unknown legacy status ('Archived in Box')
        fputcsv($fh, [
            '02-003-2023',
            'complaint',
            '2023-02-14',
            '2023-02-10',
            'Purok 3, Calarian',
            'Boundary and Property Line Dispute',
            'Purok 3',
            'Mendoza vs. Villanueva',
            'Historical dispute with custom legacy status from old logbook.',
            'Archived in Box',
            'Carlos Mendoza',
            '1978-03-11',
            '09173334444',
            'Purok 3',
            'resident',
            'Lorna Villanueva',
            '1981-07-22',
            '09175556666',
            'Purok 3',
            'resident',
        ]);

        // Row 4: Blotter-only record
        fputcsv($fh, [
            '05-004-2025',
            'blotter',
            '2025-05-19',
            '2025-05-18',
            'Purok 5, Calarian',
            'Boundary and Property Line Dispute',
            'Purok 5',
            'Blotter Entry 05-004-2025',
            'Blotter record only for lost identification and neighborhood report.',
            'Settled',
            'Teresa Aquino',
            '1992-10-05',
            '09178889999',
            'Purok 5',
            'resident',
            'Bong Navarro',
            '1990-12-12',
            '09176667777',
            'Purok 5',
            'non_resident',
        ]);

        // Rows 5..100 (96 valid rows)
        $years = [2023, 2024, 2025];
        for ($i = 5; $i <= 100; $i++) {
            $yr = $years[$i % 3];
            $seq = str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $caseNo = "08-{$seq}-{$yr}";
            fputcsv($fh, [
                $caseNo,
                'complaint',
                "{$yr}-08-15",
                "{$yr}-08-12",
                'Purok 1, Calarian',
                'Boundary and Property Line Dispute',
                'Purok 1',
                "Historical Case {$caseNo}",
                "Detailed historical complaint narrative for case {$caseNo} in Barangay Calarian.",
                'Settled',
                "Complainant Person {$i}",
                '1988-06-15',
                '0917'.str_pad((string) $i, 7, '0', STR_PAD_LEFT),
                'Purok 1, Calarian',
                'resident',
                "Respondent Person {$i}",
                '1986-04-20',
                '0918'.str_pad((string) $i, 7, '0', STR_PAD_LEFT),
                'Purok 2, Calarian',
                'resident',
            ]);
        }

        rewind($fh);
        $csvContent = (string) stream_get_contents($fh);
        fclose($fh);

        $uploadedFile = UploadedFile::fake()->createWithContent('calarian-legacy-100.csv', $csvContent);

        // Step 1: Upload
        $uploadResponse = $this->post(route('admin.imports.store'), [
            'file' => $uploadedFile,
            'source_name' => 'Calarian Legacy Program',
        ]);

        $batch = ImportBatch::firstOrFail();
        $uploadResponse->assertRedirect(route('admin.imports.map', $batch));
        $this->assertSame(100, $batch->stats['total_rows']);
        $this->assertSame(100, ImportRow::where('import_batch_id', $batch->id)->count());

        // Step 2: View & Save Column Mapping
        $this->get(route('admin.imports.map', $batch))->assertOk();

        $mappingPayload = array_combine($headers, $headers);
        $mapResponse = $this->post(route('admin.imports.save-map', $batch), [
            'mapping' => $mappingPayload,
            'save_mapping_name' => 'Calarian Legacy Program',
        ]);

        $batch->refresh();
        $mapResponse->assertRedirect(route('admin.imports.preview', $batch));
        $this->assertSame(ImportBatch::STATUS_PREVIEWED, $batch->status);
        $this->assertSame(1, $batch->stats['conflicts']);
        $this->assertSame(1, $batch->stats['errors']);
        $this->assertSame(2, $batch->stats['warnings']); // Row 2 (dup case + likely party) & Row 3 (unknown status)
        $this->assertSame(97, $batch->stats['valid']);

        // Verify Preview Screen lists conflicts and warnings
        $previewPage = $this->get(route('admin.imports.preview', $batch));
        $previewPage->assertOk();
        $previewPage->assertSee('01-001-2024');
        $previewPage->assertSee('Conflict');
        $previewPage->assertSee('Possible duplicate case');

        // Step 3: Commit Batch (97 valid + 2 warning = 99 imported, 1 conflict skipped)
        $commitResponse = $this->post(route('admin.imports.commit', $batch), [
            'include_warnings' => '1',
            'delete_source_file' => '1',
        ]);

        $batch->refresh();
        $commitResponse->assertRedirect(route('admin.imports.preview', $batch));
        $this->assertSame(ImportBatch::STATUS_COMMITTED, $batch->status);
        $this->assertSame(99, $batch->stats['imported']);
        $this->assertSame(1, $batch->stats['skipped']);
        $this->assertNull($batch->stored_path);

        // Rule 1: Existing case 01-001-2024 was NOT overwritten
        $this->assertSame('Existing Case 01-001-2024', $existingCase->fresh()->title);

        // Rule 2: Number sequences were never consumed
        $this->assertSame($initialSequenceCount, NumberSequence::count());

        // Rule 3: Historical fiscal years 2023, 2024, 2025 exist and are closed & locked
        foreach ([2023, 2024, 2025] as $histYear) {
            $fy = FiscalYear::where('year', $histYear)->first();
            $this->assertNotNull($fy);
            $this->assertSame('closed', $fy->status);
            $this->assertTrue($fy->is_locked);
        }

        // Rule 4: Exact party match linked to $exactExistingParty, likely match created a distinct Party (no auto-merge)
        $importedCaseRow2 = BarangayCase::where('case_number', '01-002-2024')->firstOrFail();
        $this->assertTrue($importedCaseRow2->complainants()->where('parties.id', $exactExistingParty->id)->exists());
        $this->assertFalse($importedCaseRow2->respondents()->where('parties.id', $likelyExistingParty->id)->exists());
        $this->assertSame(2, Party::where('full_name', 'Ricardo M. Dalisay')->count());

        // Rule 6: Unknown legacy status defaulted to 'filed' with a recorded note, and blotter stayed 'filed'
        $importedCaseRow3 = BarangayCase::where('case_number', '02-003-2023')->firstOrFail();
        $this->assertSame(CaseStatus::FILED, $importedCaseRow3->status);
        $this->assertStringContainsString('Archived in Box', (string) $importedCaseRow3->outcome_note);

        $importedBlotterRow4 = BarangayCase::where('case_number', '05-004-2025')->firstOrFail();
        $this->assertSame('blotter', $importedBlotterRow4->record_type);
        $this->assertSame(CaseStatus::FILED, $importedBlotterRow4->status);

        // Global Search verification: imported cases appear under their original numbers (with and without hyphens)
        $searchService = app(GlobalSearchService::class);
        $resultsWithHyphens = $searchService->search('08-050-2025', $admin);
        $this->assertNotEmpty($resultsWithHyphens['cases']);
        $this->assertSame('08-050-2025', $resultsWithHyphens['cases'][0]['case_number']);

        $resultsWithoutHyphens = $searchService->search('080502025', $admin);
        $this->assertNotEmpty($resultsWithoutHyphens['cases']);
        $this->assertSame('08-050-2025', $resultsWithoutHyphens['cases'][0]['case_number']);

        // Step 4: Roll Back Entire Batch
        $rollbackResponse = $this->post(route('admin.imports.rollback', $batch), [
            'rollback_note' => 'Rolling back test import batch after verification.',
        ]);

        $batch->refresh();
        $rollbackResponse->assertRedirect(route('admin.imports.preview', $batch));
        $this->assertSame(ImportBatch::STATUS_ROLLED_BACK, $batch->status);
        $this->assertSame(0, BarangayCase::where('import_batch_id', $batch->id)->count());

        // Pre-existing case and pre-existing parties remain intact
        $this->assertNotNull($existingCase->fresh());
        $this->assertNotNull($exactExistingParty->fresh());
        $this->assertNotNull($likelyExistingParty->fresh());
        $this->assertSame(1, Party::where('full_name', 'Ricardo M. Dalisay')->count());

        // Audit logs recorded for upload, preview, commit, and rollback
        $this->assertTrue(AuditLog::where('action', 'import_batch.uploaded')->exists());
        $this->assertTrue(AuditLog::where('action', 'import_batch.previewed')->exists());
        $this->assertTrue(AuditLog::where('action', 'import_batch.committed')->exists());
        $this->assertTrue(AuditLog::where('action', 'import_batch.rolled_back')->exists());
    }

    public function test_xlsx_file_can_be_generated_and_imported(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $spreadsheetService = app(SpreadsheetFileService::class);
        $tmpFile = tempnam(sys_get_temp_dir(), 'ws6_xlsx_').'.xlsx';
        $spreadsheetService->createXlsxTemplate($tmpFile);

        $uploadedXlsx = new UploadedFile(
            $tmpFile,
            'sample-template.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $this->post(route('admin.imports.store'), [
            'file' => $uploadedXlsx,
        ])->assertRedirect();

        $batch = ImportBatch::latest('id')->firstOrFail();
        $this->assertSame(2, $batch->stats['total_rows']);

        @unlink($tmpFile);
    }
}
