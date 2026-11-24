<?php

namespace Tests\Feature\Resident;

use App\Models\ComplaintIntake;
use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResidentComplaintIntakeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        FiscalYear::firstOrCreate(['year' => 2026], [
            'label' => 'FY 2026',
            'starts_on' => '2026-01-01',
            'ends_on' => '2026-12-31',
            'status' => 'active',
        ]);
    }

    public function test_complaint_intake_form_screen_loads_with_vawc_screening(): void
    {
        $response = $this->get(route('complaint.create'));

        $response->assertOk();
        $response->assertSee('File a Dispute Complaint');
        $response->assertSeeText('Mandatory Safety & Confidentiality Screening');
        $response->assertSee('VAWC');
        $response->assertSee('Quick Exit');
        $response->assertSee('National Emergency Hotline');
    }

    public function test_honeypot_submission_redirects_without_creating_record(): void
    {
        $response = $this->post(route('complaint.store'), [
            'honeypot_website' => 'http://spam-bot.com',
            'complainant_name' => 'Spam Bot',
            'complainant_contact' => '09171234567',
            'complainant_address' => 'Purok 1',
            'respondent_name' => 'Target Person',
            'respondent_address' => 'Purok 2',
            'what_happened' => 'Spam content test narrative description.',
            'who_involved' => 'Spam Bot',
            'when_happened' => now()->subDay()->format('Y-m-d\TH:i'),
            'where_happened' => 'Calarian Street',
            'why_how_happened' => 'Testing bot submission failure.',
            'rights_violated' => 'None spam test violation.',
            'relief_requested' => 'None spam test relief.',
            'consent' => '1',
        ]);

        $response->assertRedirect(route('complaint.received'));
        $this->assertDatabaseCount('complaint_intakes', 0);
    }

    public function test_valid_complaint_intake_creates_pending_record_without_creating_parties(): void
    {
        $response = $this->post(route('complaint.store'), [
            'complainant_name' => 'Maria Clara',
            'complainant_alias' => 'Clara',
            'complainant_age' => 28,
            'complainant_contact' => '09171234567',
            'complainant_address' => 'Sitio Lower Calarian, Zamboanga City',
            'complainant_residency_claim' => 'resident',
            'respondent_name' => 'Crisostomo Ibarra',
            'respondent_alias' => 'Cris',
            'respondent_address' => 'Upper Calarian, near chapel',
            'respondent_contact' => '09187654321',
            'what_happened' => 'Disagreement regarding unpaid loan and boundary wall reconstruction.',
            'who_involved' => 'Maria Clara and Crisostomo Ibarra',
            'when_happened' => now()->subDays(2)->format('Y-m-d\TH:i'),
            'where_happened' => 'Sitio Lower Calarian boundary',
            'why_how_happened' => 'Boundary dispute after typhoons damaged fence line.',
            'rights_violated' => 'Property boundary encroached and refusal to settle loan.',
            'relief_requested' => 'Re-survey of boundary line and repayment agreement.',
            'case_type_suggestion' => 'Property / Boundary',
            'consent' => '1',
        ]);

        $response->assertRedirect(route('complaint.received'));

        // Assert record created in complaint_intakes
        $this->assertDatabaseHas('complaint_intakes', [
            'complainant_name' => 'Maria Clara',
            'respondent_name' => 'Crisostomo Ibarra',
            'status' => 'pending',
            'confidentiality_screen_passed' => true,
        ]);

        $intake = ComplaintIntake::first();
        $this->assertNotNull($intake->consent_at);
        $this->assertNotNull($intake->submitted_at);

        // STRICT PII & RESPONDENT ISOLATION: Assert zero records created in canonical parties table
        $this->assertDatabaseCount('parties', 0);
    }

    public function test_complaint_submission_requires_ra10173_consent(): void
    {
        $response = $this->from(route('complaint.create'))->post(route('complaint.store'), [
            'complainant_name' => 'Maria Clara',
            'complainant_contact' => '09171234567',
            'complainant_address' => 'Sitio Lower Calarian',
            'respondent_name' => 'Crisostomo Ibarra',
            'respondent_address' => 'Upper Calarian',
            'what_happened' => 'Disagreement regarding boundary.',
            'who_involved' => 'Maria Clara and Crisostomo',
            'when_happened' => now()->subDay()->format('Y-m-d\TH:i'),
            'where_happened' => 'Sitio Lower Calarian',
            'why_how_happened' => 'Boundary conflict.',
            'rights_violated' => 'Property boundary encroachment.',
            'relief_requested' => 'Repair fence.',
            // Omit consent
        ]);

        $response->assertRedirect(route('complaint.create'));
        $response->assertSessionHasErrors('consent');
        $this->assertDatabaseCount('complaint_intakes', 0);
    }

    public function test_complaint_received_screen_withholds_respondent_and_tracking_data(): void
    {
        $response = $this->get(route('complaint.received'));

        $response->assertOk();
        $response->assertSee('Complaint Request Received');
        $response->assertSee('In-Person Sworn Signing');
        $response->assertSee('Data Privacy Protection');
        // Ensure no tracking input or respondent leakage
        $response->assertDontSee('Respondent:');
        $response->assertDontSee('BRG-');
    }
}
