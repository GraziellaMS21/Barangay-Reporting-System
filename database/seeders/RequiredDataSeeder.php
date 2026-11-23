<?php

namespace Database\Seeders;

use App\Models\CaseFieldConfig;
use App\Models\CaseType;
use App\Models\EscalationDestination;
use App\Models\Permission;
use App\Models\Position;
use App\Models\ReportTemplate;
use App\Models\Setting;
use App\Models\SignatoryAssignment;
use Illuminate\Database\Seeder;

class RequiredDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. All permissions
        $permissions = [
            ['slug' => 'settings.manage', 'label' => 'Manage Settings', 'group' => 'settings'],
            ['slug' => 'fiscal_year.manage', 'label' => 'Manage Fiscal Years', 'group' => 'settings'],
            ['slug' => 'templates.manage', 'label' => 'Manage Response Templates', 'group' => 'settings'],
            ['slug' => 'backups.manage', 'label' => 'Manage Backups', 'group' => 'settings'],
            ['slug' => 'personnel.manage', 'label' => 'Manage Personnel & Positions', 'group' => 'personnel'],
            ['slug' => 'users.manage', 'label' => 'Manage User Accounts', 'group' => 'users'],
            ['slug' => 'reports.view', 'label' => 'View Resident Reports Queue', 'group' => 'reports'],
            ['slug' => 'reports_center.view', 'label' => 'View Reports Center', 'group' => 'reports_center'],
            ['slug' => 'reports_center.export', 'label' => 'Export Reports', 'group' => 'reports_center'],
            ['slug' => 'cases.view', 'label' => 'View Barangay Cases', 'group' => 'cases'],
            ['slug' => 'cases.view_confidential', 'label' => 'View Confidential Cases', 'group' => 'cases'],
            ['slug' => 'cases.create', 'label' => 'Create & File Cases', 'group' => 'cases'],
            ['slug' => 'cases.edit', 'label' => 'Edit Case Details & Parties', 'group' => 'cases'],
            ['slug' => 'cases.summons', 'label' => 'Issue Summons & Hearings', 'group' => 'cases'],
            ['slug' => 'cases.escalate', 'label' => 'Escalate Cases to Court/Agencies', 'group' => 'cases'],
            ['slug' => 'agencies.manage', 'label' => 'Manage Partner Agencies', 'group' => 'agencies'],
            ['slug' => 'referrals.manage', 'label' => 'Manage External Referrals', 'group' => 'referrals'],
            ['slug' => 'imports.manage', 'label' => 'Manage Legacy File Imports', 'group' => 'imports'],
            ['slug' => 'dashboard.view', 'label' => 'View Dashboard Analytics', 'group' => 'dashboard'],
            ['slug' => 'audit.view', 'label' => 'View Audit Log', 'group' => 'audit'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['slug' => $perm['slug']], $perm);
        }

        // 2. Settings placeholders (is_confirmed = false)
        $settings = [
            [
                'key' => 'barangay.name',
                'value' => 'Barangay Calarian',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Barangay Name',
                'description' => 'Official name of the barangay local government unit.',
                'source_question' => 'Q1: What is the official name of the barangay?',
                'is_confirmed' => false,
            ],
            [
                'key' => 'barangay.city',
                'value' => 'Zamboanga City',
                'type' => 'string',
                'group' => 'general',
                'label' => 'City / Municipality',
                'description' => 'City or municipality having territorial jurisdiction.',
                'source_question' => 'Q2: In which city or municipality is the barangay located?',
                'is_confirmed' => false,
            ],
            [
                'key' => 'barangay.province',
                'value' => 'Zamboanga del Sur',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Province',
                'description' => 'Province of the barangay.',
                'source_question' => 'Q3: Province',
                'is_confirmed' => false,
            ],
            [
                'key' => 'barangay.region',
                'value' => 'Region IX (Zamboanga Peninsula)',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Region',
                'description' => 'Administrative region.',
                'source_question' => 'Q4: Region',
                'is_confirmed' => false,
            ],
            [
                'key' => 'barangay.hall_address',
                'value' => 'Calarian, Zamboanga City, 7000',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Barangay Hall Address',
                'description' => 'Physical location of the barangay hall for summons and hearing notices.',
                'source_question' => 'Q5: Where is the barangay hall situated?',
                'is_confirmed' => false,
            ],
            [
                'key' => 'barangay.contact_number',
                'value' => '0917-123-4567',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Contact Number',
                'description' => 'Official landline or mobile contact number.',
                'source_question' => 'Q6: Official contact number',
                'is_confirmed' => false,
            ],
            [
                'key' => 'barangay.email',
                'value' => 'info@calarian.gov.ph',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Official Email',
                'description' => 'Official email address for communication and notices.',
                'source_question' => 'Q7: Official email address',
                'is_confirmed' => false,
            ],
            [
                'key' => 'numbering.referral.format',
                'value' => 'REF-{YEAR}-{SEQ:4}',
                'type' => 'string',
                'group' => 'numbering',
                'label' => 'Referral Number Format',
                'description' => 'Numbering scheme for external referrals.',
                'source_question' => 'Q8: Referral numbering scheme',
                'is_confirmed' => false,
            ],
            [
                'key' => 'numbering.case.format',
                'value' => 'CASE-{YEAR}-{SEQ:5}',
                'type' => 'string',
                'group' => 'numbering',
                'label' => 'Case Number Format',
                'description' => 'Numbering scheme for blotter / conciliation cases.',
                'source_question' => 'Q9: Case numbering scheme',
                'is_confirmed' => false,
            ],
            [
                'key' => 'numbering.blotter.format',
                'value' => '{MM}-{SEQ:3}-{YEAR}',
                'type' => 'string',
                'group' => 'numbering',
                'label' => 'Blotter Entry Number Format',
                'description' => 'Numbering scheme for incident blotter records.',
                'source_question' => 'Q9: Blotter numbering scheme',
                'is_confirmed' => false,
            ],
            [
                'key' => 'numbering.summons.format',
                'value' => 'SUM-{YEAR}-{SEQ:4}',
                'type' => 'string',
                'group' => 'numbering',
                'label' => 'Summons Number Format',
                'description' => 'Numbering scheme for summons notices.',
                'source_question' => 'Q10: Summons numbering scheme',
                'is_confirmed' => false,
            ],
            [
                'key' => 'calendar.holidays',
                'value' => json_encode([]),
                'type' => 'json',
                'group' => 'calendar',
                'label' => 'Official Holidays',
                'description' => 'List of official holiday dates (YYYY-MM-DD) excluded from working day calculations.',
                'source_question' => 'Q41: Barangay holiday schedule',
                'is_confirmed' => false,
            ],
            [
                'key' => 'cases.mediation_deadline_days',
                'value' => '15',
                'type' => 'integer',
                'group' => 'cases',
                'label' => 'Mediation Period Deadline (Days)',
                'description' => 'Statutory mediation period before Punong Barangay under Katarungang Pambarangay (15 calendar/working days).',
                'source_question' => 'Q25: Statutory mediation window',
                'is_confirmed' => false,
            ],
            [
                'key' => 'cases.hearing_interval_days',
                'value' => '7',
                'type' => 'integer',
                'group' => 'cases',
                'label' => 'Days Between Hearings',
                'description' => 'Standard interval between scheduled mediation hearings.',
                'source_question' => 'Q25: Standard hearing interval in days',
                'is_confirmed' => false,
            ],
            [
                'key' => 'cases.settlement_repudiation_days',
                'value' => '10',
                'type' => 'integer',
                'group' => 'cases',
                'label' => 'Settlement Repudiation Window (Days)',
                'description' => 'Statutory period to repudiate an amicable settlement under Local Government Code.',
                'source_question' => 'Q26: Days allowed to repudiate settlement',
                'is_confirmed' => false,
            ],
            [
                'key' => 'cases.enabled',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'cases',
                'label' => 'Enable Case Management Module',
                'description' => 'Master switch for the case blotter and conciliation module.',
                'source_question' => 'Q27: Enable case module?',
                'is_confirmed' => false,
            ],
        ];

        foreach ($settings as $s) {
            Setting::firstOrCreate(['key' => $s['key']], $s);
        }

        // 3. Positions (is_confirmed = false)
        $positions = [
            ['name' => 'Punong Barangay', 'code' => 'punong_barangay', 'sort_order' => 1, 'is_signatory_default' => true, 'is_confirmed' => false],
            ['name' => 'Barangay Secretary', 'code' => 'barangay_secretary', 'sort_order' => 2, 'is_signatory_default' => true, 'is_confirmed' => false],
            ['name' => 'Lupon Member / Conciliator', 'code' => 'lupon_member', 'sort_order' => 3, 'is_signatory_default' => false, 'is_confirmed' => false],
            ['name' => 'Barangay Tanod / Peace Officer', 'code' => 'barangay_tanod', 'sort_order' => 4, 'is_signatory_default' => false, 'is_confirmed' => false],
            ['name' => 'Barangay Kagawad (Peace & Order)', 'code' => 'kagawad_peace_order', 'sort_order' => 5, 'is_signatory_default' => false, 'is_confirmed' => false],
        ];

        foreach ($positions as $pos) {
            Position::firstOrCreate(['code' => $pos['code']], $pos);
        }

        // 4. Signatory assignments (is_confirmed = false)
        $pb = Position::where('code', 'punong_barangay')->first();
        $sec = Position::where('code', 'barangay_secretary')->first();

        if ($pb && $sec) {
            $assignments = [
                ['document_type' => 'summons', 'role_label' => 'Prepared by', 'position_id' => $sec->id, 'sort_order' => 1, 'is_confirmed' => false],
                ['document_type' => 'summons', 'role_label' => 'Presided by', 'position_id' => $pb->id, 'sort_order' => 2, 'is_confirmed' => false],
                ['document_type' => 'case_endorsement', 'role_label' => 'Prepared by', 'position_id' => $sec->id, 'sort_order' => 1, 'is_confirmed' => false],
                ['document_type' => 'case_endorsement', 'role_label' => 'Approved by', 'position_id' => $pb->id, 'sort_order' => 2, 'is_confirmed' => false],
                ['document_type' => 'referral_letter', 'role_label' => 'Prepared by', 'position_id' => $sec->id, 'sort_order' => 1, 'is_confirmed' => false],
                ['document_type' => 'referral_letter', 'role_label' => 'Approved by', 'position_id' => $pb->id, 'sort_order' => 2, 'is_confirmed' => false],
                ['document_type' => 'settlement_agreement', 'role_label' => 'Attested by', 'position_id' => $pb->id, 'sort_order' => 1, 'is_confirmed' => false],
                ['document_type' => 'settlement_agreement', 'role_label' => 'Approved by', 'position_id' => $pb->id, 'sort_order' => 2, 'is_confirmed' => false],
            ];

            foreach ($assignments as $assign) {
                SignatoryAssignment::firstOrCreate(
                    ['document_type' => $assign['document_type'], 'role_label' => $assign['role_label']],
                    $assign
                );
            }
        }

        // 5. Case types (is_confirmed = false)
        $caseTypes = [
            ['name' => 'Boundary and Property Line Dispute', 'slug' => 'boundary_dispute', 'description' => 'Encroachment, boundary markings, and right-of-way disagreements.', 'sort_order' => 1, 'is_confirmed' => false],
            ['name' => 'Neighborhood Noise and Disturbance', 'slug' => 'noise_disturbance', 'description' => 'Loud music, videoke beyond permitted hours, and public disturbance.', 'sort_order' => 2, 'is_confirmed' => false],
            ['name' => 'Physical Injury / Verbal Altercation', 'slug' => 'altercation', 'description' => 'Heated arguments, minor scuffles, and oral defamation without serious injury.', 'sort_order' => 3, 'is_confirmed' => false],
            ['name' => 'Minor Debt and Monetary Claim', 'slug' => 'debt_claim', 'description' => 'Unpaid personal debts and small financial obligations.', 'sort_order' => 4, 'is_confirmed' => false],
            ['name' => 'Tenancy and Rental Disagreement', 'slug' => 'tenancy_dispute', 'description' => 'Unpaid rent, house rules violations, and eviction disputes.', 'sort_order' => 5, 'is_confirmed' => false],
            ['name' => 'Other Lupon Tagapamayapa Matter', 'slug' => 'other_lupon', 'description' => 'Other community disputes subject to Katarungang Pambarangay.', 'sort_order' => 6, 'is_confirmed' => false],
        ];

        foreach ($caseTypes as $ct) {
            CaseType::firstOrCreate(['slug' => $ct['slug']], $ct);
        }

        // 6. Case field configs (is_confirmed = false)
        $fieldConfigs = [
            ['entity' => 'party', 'field_key' => 'full_name', 'label' => 'Full Name', 'is_required' => true, 'is_visible' => true, 'applies_to' => 'all', 'sort_order' => 1, 'is_confirmed' => false],
            ['entity' => 'party', 'field_key' => 'contact_number', 'label' => 'Contact Number', 'is_required' => false, 'is_visible' => true, 'applies_to' => 'all', 'sort_order' => 2, 'is_confirmed' => false],
            ['entity' => 'party', 'field_key' => 'address_line', 'label' => 'Street Address', 'is_required' => false, 'is_visible' => true, 'applies_to' => 'all', 'sort_order' => 3, 'is_confirmed' => false],
            ['entity' => 'case', 'field_key' => 'title', 'label' => 'Case Title', 'is_required' => true, 'is_visible' => true, 'applies_to' => 'all', 'sort_order' => 1, 'is_confirmed' => false],
            ['entity' => 'case', 'field_key' => 'narrative', 'label' => 'Complaint Narrative', 'is_required' => true, 'is_visible' => true, 'applies_to' => 'all', 'sort_order' => 2, 'is_confirmed' => false],
            ['entity' => 'case', 'field_key' => 'incident_location', 'label' => 'Incident Location', 'is_required' => false, 'is_visible' => true, 'applies_to' => 'all', 'sort_order' => 3, 'is_confirmed' => false],
        ];

        foreach ($fieldConfigs as $fc) {
            CaseFieldConfig::firstOrCreate(
                ['entity' => $fc['entity'], 'field_key' => $fc['field_key']],
                $fc
            );
        }

        // 7. Escalation destinations (is_confirmed = false)
        $destinations = [
            ['name' => 'Municipal Trial Court in Cities (MTCC)', 'sort_order' => 1, 'is_active' => true, 'is_confirmed' => false],
            ['name' => 'City Prosecutor Office', 'sort_order' => 2, 'is_active' => true, 'is_confirmed' => false],
            ['name' => 'PNP Station 8 (Ayala/Calarian)', 'sort_order' => 3, 'is_active' => true, 'is_confirmed' => false],
            ['name' => 'City Social Welfare and Development Office (CSWDO)', 'sort_order' => 4, 'is_active' => true, 'is_confirmed' => false],
        ];

        foreach ($destinations as $dest) {
            EscalationDestination::firstOrCreate(['name' => $dest['name']], $dest);
        }

        // 8. Report templates (is_confirmed = false)
        $reportTemplates = [
            [
                'key' => 'cases_summary',
                'title' => 'Barangay Cases Summary',
                'description' => 'Summary of all filed and settled Katarungang Pambarangay cases.',
                'source' => 'cases',
                'columns' => ['case_number', 'filed_on', 'title', 'status', 'settled_on'],
                'filters' => ['status', 'case_type_id'],
                'orientation' => 'landscape',
                'signatory_document_type' => 'case_endorsement',
                'is_active' => true,
                'is_confirmed' => false,
            ],
            [
                'key' => 'kp_monthly',
                'title' => 'Katarungang Pambarangay Monthly Report',
                'description' => 'DILG monthly compliance report for Lupon Tagapamayapa operations.',
                'source' => 'cases',
                'columns' => ['case_number', 'case_type', 'filed_on', 'status', 'hearings_count'],
                'filters' => ['month', 'year'],
                'orientation' => 'portrait',
                'signatory_document_type' => 'case_endorsement',
                'is_active' => true,
                'is_confirmed' => false,
            ],
            [
                'key' => 'referrals_status',
                'title' => 'External Referrals Tracking Report',
                'description' => 'Status and SLA follow-up report for cases referred to outside agencies.',
                'source' => 'referrals',
                'columns' => ['reference_no', 'office', 'status', 'sent_at', 'follow_up_at'],
                'filters' => ['office_id', 'status'],
                'orientation' => 'landscape',
                'signatory_document_type' => 'referral_letter',
                'is_active' => true,
                'is_confirmed' => false,
            ],
        ];

        foreach ($reportTemplates as $rt) {
            ReportTemplate::firstOrCreate(['key' => $rt['key']], $rt);
        }
    }
}
