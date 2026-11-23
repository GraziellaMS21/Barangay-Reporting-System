<?php

namespace Database\Seeders;

use App\Models\AgencyRegistration;
use App\Models\BarangayCase;
use App\Models\CaseEscalation;
use App\Models\CaseParty;
use App\Models\CaseStatusLog;
use App\Models\CaseType;
use App\Models\EscalationDestination;
use App\Models\FiscalYear;
use App\Models\Hearing;
use App\Models\Office;
use App\Models\Party;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Purok;
use App\Models\Setting;
use App\Models\Summons;
use App\Models\User;
use App\Services\PermissionService;
use App\Support\CaseStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure required data is present
        $this->call(RequiredDataSeeder::class);

        // 2. Active fiscal year for current year
        $currentYear = (int) now()->year;
        $fiscalYear = FiscalYear::firstOrCreate(
            ['year' => $currentYear],
            [
                'name' => "FY {$currentYear}",
                'status' => 'active',
                'start_date' => now()->startOfYear()->toDateString(),
                'end_date' => now()->endOfYear()->toDateString(),
            ]
        );

        // Ensure active
        if ($fiscalYear->status !== 'active') {
            $fiscalYear->update(['status' => 'active']);
        }

        // 3. Mark setup as completed in settings
        Setting::updateOrCreate(
            ['key' => 'system.setup_completed'],
            [
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'system',
                'label' => 'System Setup Completed',
                'description' => 'Flag indicating initial setup wizard has been concluded.',
                'is_confirmed' => true,
            ]
        );

        $defaultPassword = Hash::make('password123');
        $permissionService = app(PermissionService::class);

        // 4. Demo Accounts
        // Admin
        $admin = User::updateOrCreate(
            ['email' => 'admin@calarian.gov.ph'],
            [
                'name' => 'Hon. Eduardo V. Alvarez (Captain)',
                'password' => $defaultPassword,
                'role' => 'admin',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );

        // Secretary / Staff (staff_full preset)
        $staff = User::updateOrCreate(
            ['email' => 'staff@calarian.gov.ph'],
            [
                'name' => 'Maria Clara Delos Santos (Secretary)',
                'password' => $defaultPassword,
                'role' => 'staff',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        $permissionService->applyPreset($staff, 'staff_full');

        // Barangay Personnel Account
        $personnelUser = User::updateOrCreate(
            ['email' => 'personnel@calarian.gov.ph'],
            [
                'name' => 'Barangay Personnel',
                'password' => $defaultPassword,
                'role' => 'personnel',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        $permissionService->applyPreset($personnelUser, 'staff_full');

        // Encoder (case_encoder preset)
        $encoder = User::updateOrCreate(
            ['email' => 'clerk@calarian.gov.ph'],
            [
                'name' => 'Juana Reyes (Records Clerk)',
                'password' => $defaultPassword,
                'role' => 'staff',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );
        $permissionService->applyPreset($encoder, 'case_encoder');

        // CSWDO Office & Agency User
        $cswdoOffice = Office::firstOrCreate(
            ['name' => 'City Social Welfare and Development Office (CSWDO)'],
            [
                'type' => 'external',
                'description' => 'Handles social welfare cases, child protection, family mediation, and social assistance.',
                'is_internal' => false,
                'is_external' => true,
                'contact' => 'cswdo@zamboanga.gov.ph',
                'contact_person' => 'Atty. Cristina Mendoza',
                'contact_email' => 'cswdo@zamboanga.gov.ph',
                'contact_phone' => '(062) 991-2345',
                'address' => 'City Hall Complex, Pettit Barracks, Zamboanga City',
                'is_active' => true,
            ]
        );

        $agencyUser = User::updateOrCreate(
            ['email' => 'cswdo@zamboanga.gov.ph'],
            [
                'name' => 'Atty. Cristina Mendoza (CSWDO)',
                'password' => $defaultPassword,
                'role' => 'agency',
                'is_active' => true,
                'must_change_password' => false,
            ]
        );

        AgencyRegistration::updateOrCreate(
            ['user_id' => $agencyUser->id],
            [
                'office_id' => $cswdoOffice->id,
                'agency_name_claimed' => 'City Social Welfare and Development Office',
                'position_title' => 'Social Welfare Officer IV',
                'contact_number' => '0917-000-1122',
                'id_document_path' => 'demo/cswdo_officer_id.pdf',
                'status' => 'approved',
                'is_office_admin' => true,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'decision_note' => 'Official agency account approved for Calarian referral coordination.',
            ]
        );

        // 5. Personnel Records
        $pbPosition = Position::firstOrCreate(
            ['code' => 'punong_barangay'],
            ['name' => 'Punong Barangay', 'sort_order' => 1, 'is_signatory_default' => true, 'is_confirmed' => true]
        );

        $secPosition = Position::firstOrCreate(
            ['code' => 'barangay_secretary'],
            ['name' => 'Barangay Secretary', 'sort_order' => 2, 'is_signatory_default' => true, 'is_confirmed' => true]
        );

        $luponPosition = Position::firstOrCreate(
            ['code' => 'lupon_member'],
            ['name' => 'Lupon Member / Conciliator', 'sort_order' => 3, 'is_signatory_default' => false, 'is_confirmed' => true]
        );

        $tanodPosition = Position::firstOrCreate(
            ['code' => 'barangay_tanod'],
            ['name' => 'Barangay Tanod / Peace Officer', 'sort_order' => 4, 'is_signatory_default' => false, 'is_confirmed' => true]
        );

        $pbPersonnel = Personnel::updateOrCreate(
            ['position_id' => $pbPosition->id, 'full_name' => 'Hon. Eduardo V. Alvarez'],
            [
                'user_id' => $admin->id,
                'contact_number' => '0917-111-2233',
                'email' => 'admin@calarian.gov.ph',
                'term_start' => now()->subYears(2)->toDateString(),
                'term_end' => now()->addYear()->toDateString(),
                'is_signatory' => true,
                'is_active' => true,
            ]
        );

        $secPersonnel = Personnel::updateOrCreate(
            ['position_id' => $secPosition->id, 'full_name' => 'Maria Clara Delos Santos'],
            [
                'user_id' => $staff->id,
                'contact_number' => '0918-222-3344',
                'email' => 'staff@calarian.gov.ph',
                'term_start' => now()->subYears(2)->toDateString(),
                'term_end' => now()->addYear()->toDateString(),
                'is_signatory' => true,
                'is_active' => true,
            ]
        );

        $tanodPersonnel = Personnel::updateOrCreate(
            ['position_id' => $tanodPosition->id, 'full_name' => 'Rogelio B. Gutierrez'],
            [
                'contact_number' => '0919-333-4455',
                'email' => 'tanod.gutierrez@calarian.gov.ph',
                'term_start' => now()->subYear()->toDateString(),
                'term_end' => now()->addYear()->toDateString(),
                'is_signatory' => false,
                'is_active' => true,
            ]
        );

        $luponPersonnel = Personnel::updateOrCreate(
            ['position_id' => $luponPosition->id, 'full_name' => 'Juana Reyes'],
            [
                'user_id' => $encoder->id,
                'contact_number' => '0919-444-5566',
                'email' => 'clerk@calarian.gov.ph',
                'term_start' => now()->subYear()->toDateString(),
                'term_end' => now()->addYear()->toDateString(),
                'is_signatory' => false,
                'is_active' => true,
            ]
        );

        $pangkatPersonnel = Personnel::updateOrCreate(
            ['position_id' => $luponPosition->id, 'full_name' => 'Hon. Alberto C. Mendoza (Pangkat Chairman)'],
            [
                'contact_number' => '0919-777-8899',
                'email' => 'pangkat.mendoza@calarian.gov.ph',
                'term_start' => now()->subYear()->toDateString(),
                'term_end' => now()->addYear()->toDateString(),
                'is_signatory' => true,
                'is_active' => true,
            ]
        );

        // 6. Demo Cases
        $purok = Purok::first();
        $purokId = $purok?->id;

        // Case 1: Filed (Boundary Dispute)
        $caseTypeBoundary = CaseType::where('slug', 'boundary_dispute')->first();
        $partyComplainant1 = Party::firstOrCreate(
            ['full_name' => 'Juan M. Dela Cruz'],
            [
                'contact_number' => '0917-555-0101',
                'address_line' => '124 Sampaguita St., Purok 1',
                'home_barangay' => 'Calarian',
                'home_city' => 'Zamboanga City',
                'residency_status' => 'resident',
                'purok_id' => $purokId,
            ]
        );
        $partyRespondent1 = Party::firstOrCreate(
            ['full_name' => 'Pedro S. Penduko'],
            [
                'contact_number' => '0917-555-0102',
                'address_line' => '126 Sampaguita St., Purok 1',
                'home_barangay' => 'Calarian',
                'home_city' => 'Zamboanga City',
                'residency_status' => 'resident',
                'purok_id' => $purokId,
            ]
        );

        $case1 = BarangayCase::updateOrCreate(
            ['case_number' => 'CASE-2026-00001'],
            [
                'fiscal_year_id' => $fiscalYear->id,
                'case_type_id' => $caseTypeBoundary?->id,
                'source' => 'direct',
                'title' => 'Dela Cruz vs. Penduko - Boundary Fence Encroachment',
                'narrative' => 'Complainant alleges respondent extended hollow-block boundary fence approximately 0.5 meters into complainant lot without prior survey.',
                'filed_on' => now()->subDays(5)->toDateString(),
                'incident_date' => now()->subDays(10)->toDateString(),
                'incident_location' => 'Sampaguita Street, Purok 1, Calarian',
                'purok_id' => $purokId,
                'status' => CaseStatus::FILED,
                'encoded_by' => $encoder->id,
                'assigned_to' => $staff->id,
                'is_confidential' => false,
            ]
        );

        CaseParty::updateOrCreate(
            ['case_id' => $case1->id, 'party_id' => $partyComplainant1->id],
            ['role' => 'complainant', 'residency_at_filing' => 'resident']
        );
        CaseParty::updateOrCreate(
            ['case_id' => $case1->id, 'party_id' => $partyRespondent1->id],
            ['role' => 'respondent', 'residency_at_filing' => 'resident']
        );
        CaseStatusLog::firstOrCreate(
            ['case_id' => $case1->id, 'to_status' => CaseStatus::FILED],
            ['from_status' => null, 'user_id' => $encoder->id, 'note' => 'Direct complaint filed and docketed.']
        );

        // Case 2: In Mediation with Summons and Hearing (Noise Disturbance)
        $caseTypeNoise = CaseType::where('slug', 'noise_disturbance')->first();
        $partyComplainant2 = Party::firstOrCreate(
            ['full_name' => 'Corazon A. Ramos'],
            [
                'contact_number' => '0917-555-0201',
                'address_line' => '45 Jasmin Lane, Purok 2',
                'home_barangay' => 'Calarian',
                'home_city' => 'Zamboanga City',
                'residency_status' => 'resident',
                'purok_id' => $purokId,
            ]
        );
        $partyRespondent2 = Party::firstOrCreate(
            ['full_name' => 'Danilo B. Bautista'],
            [
                'contact_number' => '0917-555-0202',
                'address_line' => '47 Jasmin Lane, Purok 2',
                'home_barangay' => 'Calarian',
                'home_city' => 'Zamboanga City',
                'residency_status' => 'resident',
                'purok_id' => $purokId,
            ]
        );

        $case2 = BarangayCase::updateOrCreate(
            ['case_number' => 'CASE-2026-00002'],
            [
                'fiscal_year_id' => $fiscalYear->id,
                'case_type_id' => $caseTypeNoise?->id,
                'record_type' => 'complaint',
                'source' => 'direct',
                'title' => 'Ramos vs. Bautista - Excessive Late Night Videoke',
                'narrative' => 'Respondent repeatedly operates commercial sound amplification and videoke past 10:00 PM on weeknights despite neighborhood reminders.',
                'filed_on' => now()->subDays(14)->toDateString(),
                'complaint_started_on' => now()->subDays(14)->toDateString(),
                'incident_date' => now()->subDays(16)->toDateString(),
                'incident_location' => 'Jasmin Lane, Purok 2, Calarian',
                'purok_id' => $purokId,
                'status' => CaseStatus::IN_MEDIATION,
                'encoded_by' => $encoder->id,
                'assigned_to' => $staff->id,
                'is_confidential' => false,
            ]
        );

        CaseParty::updateOrCreate(
            ['case_id' => $case2->id, 'party_id' => $partyComplainant2->id],
            ['role' => 'complainant', 'residency_at_filing' => 'resident']
        );
        $casePartyRespondent2 = CaseParty::updateOrCreate(
            ['case_id' => $case2->id, 'party_id' => $partyRespondent2->id],
            ['role' => 'respondent', 'residency_at_filing' => 'resident']
        );

        $summons1 = Summons::updateOrCreate(
            ['case_id' => $case2->id, 'sequence_no' => 1],
            [
                'respondent_case_party_id' => $casePartyRespondent2->id,
                'summons_number' => 'SUM-2026-0001',
                'issued_on' => now()->subDays(10)->toDateString(),
                'issued_by' => $staff->id,
                'hearing_at' => now()->addDays(2)->setTime(10, 0),
                'venue' => 'Barangay Hall Session Hall, Calarian',
                'served_by_personnel_id' => $tanodPersonnel->id,
                'served_on' => now()->subDays(9)->toDateString(),
                'service_result' => 'served_personal',
                'remarks' => 'Personally received and signed by respondent Danilo Bautista.',
            ]
        );

        Hearing::updateOrCreate(
            ['case_id' => $case2->id, 'summons_id' => $summons1->id],
            [
                'scheduled_at' => now()->addDays(2)->setTime(10, 0),
                'venue' => 'Barangay Hall Session Hall, Calarian',
                'presided_by_personnel_id' => $pbPersonnel->id,
                'status' => 'scheduled',
                'outcome_note' => 'First conciliation proceeding set before Punong Barangay.',
            ]
        );

        // Case 3: Settled (Debt Claim)
        $caseTypeDebt = CaseType::where('slug', 'debt_claim')->first();
        $partyComplainant3 = Party::firstOrCreate(
            ['full_name' => 'Roberto T. Gomez'],
            [
                'contact_number' => '0917-555-0301',
                'address_line' => '88 Ilang-Ilang Road, Purok 3',
                'home_barangay' => 'Calarian',
                'home_city' => 'Zamboanga City',
                'residency_status' => 'resident',
                'purok_id' => $purokId,
            ]
        );
        $partyRespondent3 = Party::firstOrCreate(
            ['full_name' => 'Carmelita D. Flores'],
            [
                'contact_number' => '0917-555-0302',
                'address_line' => '90 Ilang-Ilang Road, Purok 3',
                'home_barangay' => 'Calarian',
                'home_city' => 'Zamboanga City',
                'residency_status' => 'resident',
                'purok_id' => $purokId,
            ]
        );

        $case3 = BarangayCase::updateOrCreate(
            ['case_number' => 'CASE-2026-00003'],
            [
                'fiscal_year_id' => $fiscalYear->id,
                'case_type_id' => $caseTypeDebt?->id,
                'source' => 'direct',
                'title' => 'Gomez vs. Flores - Unpaid Sari-Sari Store Merchandise Credit',
                'narrative' => 'Collection of PHP 8,500 for grocery store credit incurred between August and October.',
                'filed_on' => now()->subDays(25)->toDateString(),
                'incident_date' => now()->subDays(30)->toDateString(),
                'incident_location' => 'Purok 3 Commercial Stalls',
                'purok_id' => $purokId,
                'status' => CaseStatus::SETTLED,
                'encoded_by' => $encoder->id,
                'assigned_to' => $staff->id,
                'settled_on' => now()->subDays(3)->toDateString(),
                'settlement_terms' => 'Respondent agreed to pay PHP 4,250 on the 15th and PHP 4,250 on the 30th of next month.',
                'outcome_note' => 'Amicable settlement signed and attested by Punong Barangay.',
                'is_confidential' => false,
            ]
        );

        CaseParty::updateOrCreate(
            ['case_id' => $case3->id, 'party_id' => $partyComplainant3->id],
            ['role' => 'complainant', 'residency_at_filing' => 'resident']
        );
        CaseParty::updateOrCreate(
            ['case_id' => $case3->id, 'party_id' => $partyRespondent3->id],
            ['role' => 'respondent', 'residency_at_filing' => 'resident']
        );

        // Case 4: Escalated (Physical Altercation / CFA)
        $caseTypeAltercation = CaseType::where('slug', 'altercation')->first();
        $partyComplainant4 = Party::firstOrCreate(
            ['full_name' => 'Arnel P. Mercado'],
            [
                'contact_number' => '0917-555-0401',
                'address_line' => '12 Rosal St., Purok 4',
                'home_barangay' => 'Calarian',
                'home_city' => 'Zamboanga City',
                'residency_status' => 'resident',
                'purok_id' => $purokId,
            ]
        );
        $partyRespondent4 = Party::firstOrCreate(
            ['full_name' => 'Fernando C. Aquino'],
            [
                'contact_number' => '0917-555-0402',
                'address_line' => '14 Rosal St., Purok 4',
                'home_barangay' => 'Calarian',
                'home_city' => 'Zamboanga City',
                'residency_status' => 'resident',
                'purok_id' => $purokId,
            ]
        );

        $case4 = BarangayCase::updateOrCreate(
            ['case_number' => 'CASE-2026-00004'],
            [
                'fiscal_year_id' => $fiscalYear->id,
                'case_type_id' => $caseTypeAltercation?->id,
                'source' => 'direct',
                'title' => 'Mercado vs. Aquino - Physical Altercation and Property Damage',
                'narrative' => 'Dispute over vehicle driveway blockage escalated into physical altercation and broken vehicle tail light. Respondent failed to appear after 3 valid summonses.',
                'filed_on' => now()->subDays(40)->toDateString(),
                'incident_date' => now()->subDays(45)->toDateString(),
                'incident_location' => 'Rosal Street, Purok 4',
                'purok_id' => $purokId,
                'status' => CaseStatus::ESCALATED,
                'encoded_by' => $encoder->id,
                'assigned_to' => $staff->id,
                'outcome_note' => 'Certificate to File Action (CFA) issued due to respondent repeated non-appearance.',
                'is_confidential' => false,
            ]
        );

        CaseParty::updateOrCreate(
            ['case_id' => $case4->id, 'party_id' => $partyComplainant4->id],
            ['role' => 'complainant', 'residency_at_filing' => 'resident']
        );
        $casePartyRespondent4 = CaseParty::updateOrCreate(
            ['case_id' => $case4->id, 'party_id' => $partyRespondent4->id],
            ['role' => 'respondent', 'residency_at_filing' => 'resident']
        );

        $mtcc = EscalationDestination::where('name', 'like', '%Municipal Trial Court%')->first();
        if ($mtcc) {
            CaseEscalation::updateOrCreate(
                ['case_id' => $case4->id],
                [
                    'escalation_destination_id' => $mtcc->id,
                    'escalated_by' => $staff->id,
                    'decided_by_personnel_id' => $pbPersonnel->id,
                    'reason' => 'Respondent failed to appear for 3 scheduled hearings despite personal service of summons. Certificate to File Action issued.',
                    'summons_count_at_time' => 3,
                    'escalated_at' => now()->subDays(2),
                ]
            );
        }

        // Case 5: Blotter Record (Incident Documentation)
        $partyBlotter = Party::firstOrCreate(
            ['full_name' => 'Elena S. Ramos'],
            [
                'contact_number' => '0917-555-0501',
                'address_line' => '55 Bougainvillea St., Purok 1',
                'home_barangay' => 'Calarian',
                'home_city' => 'Zamboanga City',
                'residency_status' => 'resident',
                'purok_id' => $purokId,
            ]
        );

        $case5 = BarangayCase::updateOrCreate(
            ['case_number' => 'BLOT-2026-00001'],
            [
                'fiscal_year_id' => $fiscalYear->id,
                'case_type_id' => $caseTypeNoise?->id,
                'record_type' => 'blotter',
                'source' => 'walk_in',
                'title' => 'Blotter Report - Lost Government Identification Cards',
                'narrative' => 'Resident reported loss of PhilHealth ID and UMID card while commuting along Normal Road. Logged for police/agency record purposes.',
                'filed_on' => now()->subDays(2)->toDateString(),
                'incident_date' => now()->subDays(2)->toDateString(),
                'incident_location' => 'Normal Road, Calarian',
                'purok_id' => $purokId,
                'status' => CaseStatus::FILED,
                'encoded_by' => $encoder->id,
                'assigned_to' => $staff->id,
                'outcome_note' => 'Blotter certification issued upon resident request.',
                'is_confidential' => false,
            ]
        );

        CaseParty::updateOrCreate(
            ['case_id' => $case5->id, 'party_id' => $partyBlotter->id],
            ['role' => 'complainant', 'residency_at_filing' => 'resident']
        );
    }
}
