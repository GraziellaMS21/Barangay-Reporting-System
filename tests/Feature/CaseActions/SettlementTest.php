<?php

namespace Tests\Feature\CaseActions;

use App\Models\BarangayCase;
use App\Models\User;
use App\Support\CaseStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettlementTest extends TestCase
{
    use RefreshDatabase;

    public function test_settlement_can_be_recorded_with_terms_amount_and_signed_document(): void
    {
        Storage::fake('private');

        $admin = User::factory()->admin()->create();
        $case = BarangayCase::factory()->create([
            'record_type' => 'complaint',
            'status' => CaseStatus::IN_MEDIATION,
            'settled_on' => null,
            'settlement_terms' => null,
            'settlement_amount' => null,
        ]);

        $file = UploadedFile::fake()->create('agreement.pdf', 500, 'application/pdf');

        $response = $this->actingAs($admin)->post(route('admin.cases.settlement.store', $case), [
            'settled_on' => '2026-11-24',
            'terms' => 'Respondent agrees to pay 5,000 PHP in two equal installments.',
            'settlement_amount' => 5000.00,
            'agreement_file' => $file,
        ]);

        $response->assertRedirect(route('admin.cases.show', $case));

        $case->refresh();
        $this->assertSame(CaseStatus::SETTLED, $case->status);
        $this->assertSame('2026-11-24', $case->settled_on->format('Y-m-d'));
        $this->assertSame('Respondent agrees to pay 5,000 PHP in two equal installments.', $case->settlement_terms);
        $this->assertEquals(5000.00, $case->settlement_amount);
        $this->assertNotNull($case->settlement_agreement_path);

        Storage::disk('private')->assertExists($case->settlement_agreement_path);
    }

    public function test_settlement_agreement_document_can_be_downloaded(): void
    {
        Storage::fake('private');

        $admin = User::factory()->admin()->create();
        $path = 'case-files/1/test-agreement.pdf';
        Storage::disk('private')->put($path, 'sample pdf content');

        $case = BarangayCase::factory()->create([
            'record_type' => 'complaint',
            'status' => CaseStatus::SETTLED,
            'settlement_agreement_path' => $path,
            'is_confidential' => false,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.cases.settlement.agreement', $case));

        $response->assertOk();
    }
}
