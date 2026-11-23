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
use App\Models\NumberSequence;
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
use App\Support\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure baseline reference data is present
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

        // All demo accounts use password123 for testing and demonstration purposes only
        $defaultPassword = Hash::make('password123');
        $permissionService = app(PermissionService::class);

        // Clean up any legacy or deprecated personnel role accounts
        User::where('email', 'personnel@calarian.gov.ph')->orWhere('role', 'personnel')->delete();

        // 4. Demo Accounts
        // 4.1 Admin: Punong Barangay (Captain)
        $admin = User::updateOrCreate(
            ['email' => 'admin@calarian.gov.ph'],
            [
                'name' => 'Hon. Eduardo V. Alvarez (Captain)',
                'password' => $defaultPassword,
                'role' => UserRole::ADMIN,
                'is_active' => true,
                'email_verified_at' => now(),
                'must_change_password' => false,
            ]
        );

        // 4.2 Secretary: Admin role per Secretary Q6
        $secretary = User::updateOrCreate(
            ['email' => 'secretary@calarian.gov.ph'],
            [
                'name' => 'Maria Clara Delos Santos (Secretary)',
                'password' => $defaultPassword,
                'role' => UserRole::ADMIN,
                'is_active' => true,
                'email_verified_at' => now(),
                'must_change_password' => false,
            ]
        );

        // 4.3 Staff: Desk Officer / Tanod (staff_full preset)
        $staff = User::updateOrCreate(
            ['email' => 'staff@calarian.gov.ph'],
            [
                'name' => 'Rogelio B. Gutierrez (Desk Officer)',
                'password' => $defaultPassword,
                'role' => UserRole::STAFF,
                'is_active' => true,
                'email_verified_at' => now(),
                'must_change_password' => false,
            ]
        );
        $permissionService->applyPreset($staff, 'staff_full');

        // 4.4 Encoder: Lupon Records Clerk (case_encoder preset)
        $encoder = User::updateOrCreate(
            ['email' => 'clerk@calarian.gov.ph'],
            [
                'name' => 'Juana Reyes (Records Clerk)',
                'password' => $defaultPassword,
                'role' => UserRole::STAFF,
                'is_active' => true,
                'email_verified_at' => now(),
                'must_change_password' => false,
            ]
        );
        $permissionService->applyPreset($encoder, 'case_encoder');

        // 4.5 Reviewer: Kagawad Peace & Order (case_reviewer preset)
        $reviewer = User::updateOrCreate(
            ['email' => 'reviewer@calarian.gov.ph'],
            [
                'name' => 'Hon. Antonio S. Luna (Kagawad Peace & Order)',
                'password' => $defaultPassword,
                'role' => UserRole::STAFF,
                'is_active' => true,
                'email_verified_at' => now(),
                'must_change_password' => false,
            ]
        );
        $permissionService->applyPreset($reviewer, 'case_reviewer');

        // 4.6 Agency Partner: CSWDO external office user
        $cswdoOffice = Office::where('name', 'City Social Welfare and Development Office (CSWDO)')->first();
        if (! $cswdoOffice) {
            $cswdoOffice = Office::firstOrCreate(
                ['name' => 'City Social Welfare and Development Office (CSWDO)'],
                [
                    'type' => 'external',
                    'description' => 'Handles social welfare cases, child protection, family mediation, and social assistance.',
                    'is_internal' => false,
                    'is_external' => true,
                    'contact_email' => 'cswdo@zamboanga.gov.ph',
                    'contact_phone' => '(062) 991-2345',
                    'address' => 'City Hall Complex, Pettit Barracks, Zamboanga City',
                    'is_active' => true,
                ]
            );
        }

        $agencyUser = User::updateOrCreate(
            ['email' => 'cswdo@zamboanga.gov.ph'],
            [
                'name' => 'Atty. Cristina Mendoza (CSWDO)',
                'password' => $defaultPassword,
                'role' => UserRole::AGENCY,
                'office_id' => $cswdoOffice->id,
                'is_active' => true,
                'email_verified_at' => now(),
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

        // Set default assignee for internal offices to demo staff user
        Office::where('is_internal', true)->update(['default_assignee_id' => $staff->id]);

        // 5. Personnel Records linked to Position and User accounts
        $pbPosition = Position::where('code', 'punong_barangay')->first();
        $secPosition = Position::where('code', 'barangay_secretary')->first();
        $luponPosition = Position::where('code', 'lupon_member')->first();
        $tanodPosition = Position::where('code', 'barangay_tanod')->first();
        $kagawadPosition = Position::where('code', 'kagawad_peace_order')->first();

        $pbPersonnel = Personnel::updateOrCreate(
            ['full_name' => 'Hon. Eduardo V. Alvarez'],
            [
                'position_id' => $pbPosition?->id,
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
            ['full_name' => 'Maria Clara Delos Santos'],
            [
                'position_id' => $secPosition?->id,
                'user_id' => $secretary->id,
                'contact_number' => '0918-222-3344',
                'email' => 'secretary@calarian.gov.ph',
                'term_start' => now()->subYears(2)->toDateString(),
                'term_end' => now()->addYear()->toDateString(),
                'is_signatory' => true,
                'is_active' => true,
            ]
        );

        $tanodPersonnel = Personnel::updateOrCreate(
            ['full_name' => 'Rogelio B. Gutierrez'],
            [
                'position_id' => $tanodPosition?->id,
                'user_id' => $staff->id,
                'contact_number' => '0919-333-4455',
                'email' => 'staff@calarian.gov.ph',
                'term_start' => now()->subYear()->toDateString(),
                'term_end' => now()->addYear()->toDateString(),
                'is_signatory' => false,
                'is_active' => true,
            ]
        );

        $luponPersonnel = Personnel::updateOrCreate(
            ['full_name' => 'Juana Reyes'],
            [
                'position_id' => $luponPosition?->id,
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
            ['full_name' => 'Hon. Alberto C. Mendoza (Pangkat Chairman)'],
            [
                'position_id' => $luponPosition?->id,
                'contact_number' => '0919-777-8899',
                'email' => 'pangkat.mendoza@calarian.gov.ph',
                'term_start' => now()->subYear()->toDateString(),
                'term_end' => now()->addYear()->toDateString(),
                'is_signatory' => true,
                'is_active' => true,
            ]
        );

        if ($kagawadPosition) {
            Personnel::updateOrCreate(
                ['full_name' => 'Hon. Antonio S. Luna (Kagawad Peace & Order)'],
                [
                    'position_id' => $kagawadPosition->id,
                    'user_id' => $reviewer->id,
                    'contact_number' => '0919-888-9900',
                    'email' => 'reviewer@calarian.gov.ph',
                    'term_start' => now()->subYear()->toDateString(),
                    'term_end' => now()->addYear()->toDateString(),
                    'is_signatory' => false,
                    'is_active' => true,
                ]
            );
        }

        // 6. Demo Cases & Number Sequences
        $purok = Purok::first();
        $purokId = $purok?->id;

        $case1Number = sprintf('CASE-%04d-00001', $currentYear);
        $case2Number = sprintf('CASE-%04d-00002', $currentYear);
        $case3Number = sprintf('CASE-%04d-00003', $currentYear);
        $case4Number = sprintf('CASE-%04d-00004', $currentYear);
        $blotterNumber = sprintf('BLOT-%04d-00001', $currentYear);

        // Advance sequence counters so subsequent created cases do not collide
        NumberSequence::updateOrCreate(
            ['type' => 'case', 'fiscal_year_id' => $fiscalYear->id],
            ['last_number' => 4]
        );
        NumberSequence::updateOrCreate(
            ['type' => 'blotter', 'fiscal_year_id' => $fiscalYear->id],
            ['last_number' => 1]
        );

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
            ['case_number' => $case1Number],
            [
                'fiscal_year_id' => $fiscalYear->id,
                'case_type_id' => $caseTypeBoundary?->id,
                'record_type' => 'complaint',
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
            ['full_name' => 'Maria Theresa Santos'],
            [
                'contact_number' => '0918-555-0201',
                'address_line' => '45 Jasmin St., Purok 1',
                'home_barangay' => 'Calarian',
                'home_city' => 'Zamboanga City',
                'residency_status' => 'resident',
                'purok_id' => $purokId,
            ]
        );
        $partyRespondent2 = Party::firstOrCreate(
            ['full_name' => 'Danilo R. Bautista'],
            [
                'contact_number' => '0918-555-0202',
                'address_line' => '47 Jasmin St., Purok 1',
                'home_barangay' => 'Calarian',
                'home_city' => 'Zamboanga City',
                'residency_status' => 'resident',
                'purok_id' => $purokId,
            ]
        );

        $case2 = BarangayCase::updateOrCreate(
            ['case_number' => $case2Number],
            [
                'fiscal_year_id' => $fiscalYear->id,
                'case_type_id' => $caseTypeNoise?->id,
                'record_type' => 'complaint',
                'source' => 'direct',
                'title' => 'Santos vs. Bautista - Excessive Videoke Noise Disturbance',
                'narrative' => 'Complainant reports respondent operates commercial-grade videoke past 10:00 PM on weeknights causing sleep disruption and neighborhood unrest.',
                'filed_on' => now()->subDays(12)->toDateString(),
                'incident_date' => now()->subDays(14)->toDateString(),
                'incident_location' => 'Jasmin Street, Purok 1, Calarian',
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
                'summons_number' => sprintf('SUM-%04d-0001', $currentYear),
                'issued_on' => now()->subDays(10)->toDateString(),
                'issued_by' => $staff->id,
                'hearing_at' => now()->addDays(2)->setTime(10, 0),
                'venue' => 'Barangay Hall Session Hall, Calarian',
                'served_by_personnel_id' => $tanodPersonnel->id,
                'served_on' => now()->subDays(9)->toDateString(),
                'service_result' => 'received',
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
            ['full_name' => 'Elena G. Mendoza'],
            [
                'contact_number' => '0919-555-0301',
                'address_line' => '78 Rosas St., Purok 1',
                'home_barangay' => 'Calarian',
                'home_city' => 'Zamboanga City',
                'residency_status' => 'resident',
                'purok_id' => $purokId,
            ]
        );
        $partyRespondent3 = Party::firstOrCreate(
            ['full_name' => 'Roberto K. Tan'],
            [
                'contact_number' => '0919-555-0302',
                'address_line' => '82 Rosas St., Purok 1',
                'home_barangay' => 'Calarian',
                'home_city' => 'Zamboanga City',
                'residency_status' => 'resident',
                'purok_id' => $purokId,
            ]
        );

        $case3 = BarangayCase::updateOrCreate(
            ['case_number' => $case3Number],
            [
                'fiscal_year_id' => $fiscalYear->id,
                'case_type_id' => $caseTypeDebt?->id,
                'record_type' => 'complaint',
                'source' => 'direct',
                'title' => 'Mendoza vs. Tan - Unpaid Sari-sari Store Micro-loan',
                'narrative' => 'Complainant filed claim for unpaid balance of PHP 15,000 for sari-sari store merchandise loaned over a 4-month period.',
                'filed_on' => now()->subDays(25)->toDateString(),
                'incident_date' => now()->subDays(60)->toDateString(),
                'incident_location' => 'Rosas Street, Purok 1, Calarian',
                'purok_id' => $purokId,
                'status' => CaseStatus::SETTLED,
                'settlement_amount' => 15000.00,
                'settled_on' => now()->subDays(5)->toDateString(),
                'outcome_note' => 'Amicable settlement reached. Respondent agreed to 3 monthly payments of PHP 5,000 beginning end of month.',
                'encoded_by' => $encoder->id,
                'assigned_to' => $staff->id,
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
        CaseStatusLog::firstOrCreate(
            ['case_id' => $case3->id, 'to_status' => CaseStatus::SETTLED],
            ['from_status' => CaseStatus::IN_MEDIATION, 'user_id' => $staff->id, 'note' => 'Formal settlement agreement signed before Lupon Chairman.']
        );

        // Case 4: Escalated to Court (Physical Injury)
        $caseTypeAltercation = CaseType::where('slug', 'altercation')->first();
        $destinationCourt = EscalationDestination::where('code', 'mtcc_branch_1')->first()
            ?? EscalationDestination::first();

        $partyComplainant4 = Party::firstOrCreate(
            ['full_name' => 'Ferdinand A. Castro'],
            [
                'contact_number' => '0920-555-0401',
                'address_line' => '12 Orchids Lane, Purok 1',
                'home_barangay' => 'Calarian',
                'home_city' => 'Zamboanga City',
                'residency_status' => 'resident',
                'purok_id' => $purokId,
            ]
        );
        $partyRespondent4 = Party::firstOrCreate(
            ['full_name' => 'Alexander B. Ramos'],
            [
                'contact_number' => '0920-555-0402',
                'address_line' => '15 Orchids Lane, Purok 1',
                'home_barangay' => 'Calarian',
                'home_city' => 'Zamboanga City',
                'residency_status' => 'resident',
                'purok_id' => $purokId,
            ]
        );

        $case4 = BarangayCase::updateOrCreate(
            ['case_number' => $case4Number],
            [
                'fiscal_year_id' => $fiscalYear->id,
                'case_type_id' => $caseTypeAltercation?->id,
                'record_type' => 'complaint',
                'source' => 'direct',
                'title' => 'Castro vs. Ramos - Slander by Deed and Scuffle',
                'narrative' => 'Heated altercation during purok meeting resulting in minor physical confrontation. Mediation efforts failed due to repeated non-appearance.',
                'filed_on' => now()->subDays(40)->toDateString(),
                'incident_date' => now()->subDays(45)->toDateString(),
                'incident_location' => 'Orchids Lane, Purok 1, Calarian',
                'purok_id' => $purokId,
                'status' => CaseStatus::ESCALATED,
                'outcome_note' => 'Certification to File Action (CFA) issued following 3 failed conciliation sessions.',
                'encoded_by' => $encoder->id,
                'assigned_to' => $staff->id,
                'is_confidential' => false,
            ]
        );

        CaseParty::updateOrCreate(
            ['case_id' => $case4->id, 'party_id' => $partyComplainant4->id],
            ['role' => 'complainant', 'residency_at_filing' => 'resident']
        );
        CaseParty::updateOrCreate(
            ['case_id' => $case4->id, 'party_id' => $partyRespondent4->id],
            ['role' => 'respondent', 'residency_at_filing' => 'resident']
        );

        if ($destinationCourt) {
            $complainantCaseParty = CaseParty::where('case_id', $case4->id)->where('role', 'complainant')->first();
            CaseEscalation::updateOrCreate(
                ['case_id' => $case4->id],
                [
                    'escalation_destination_id' => $destinationCourt->id,
                    'requested_by_case_party_id' => $complainantCaseParty?->id,
                    'requested_on' => now()->subDays(2)->toDateString(),
                    'reason' => 'Failure of conciliation: respondent failed to appear across 3 scheduled sessions without valid excuse.',
                    'escalated_at' => now()->subDays(2),
                    'escalated_by' => $staff->id,
                    'decided_by_personnel_id' => $pbPersonnel->id,
                    'signed_by_personnel_id' => $pbPersonnel->id,
                    'cfa_number' => sprintf('CFA-%04d-0001', $currentYear),
                ]
            );
        }

        // Case 5: Blotter Entry (Lost IDs)
        $partyBlotter = Party::firstOrCreate(
            ['full_name' => 'Luzviminda O. Santos'],
            [
                'contact_number' => '0921-555-0501',
                'address_line' => '55 Bougainvillea St., Purok 1',
                'home_barangay' => 'Calarian',
                'home_city' => 'Zamboanga City',
                'residency_status' => 'resident',
                'purok_id' => $purokId,
            ]
        );

        $case5 = BarangayCase::updateOrCreate(
            ['case_number' => $blotterNumber],
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

        // 7. Seed demo resident reports (via DemoReportSeeder)
        $this->call(DemoReportSeeder::class);
    }
}
