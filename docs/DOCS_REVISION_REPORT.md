# Documentation Revision & Reconciliation Report

**Date**: 2026-11-22  
**Repository**: `Barangay-Reporting-System-1` (Laravel 13, Blade, Tailwind v4)  
**Author**: Technical Documentation Agent  
**Mandate**: Reconcile project documentation with actual codebase and the Barangay Secretary's 43-question questionnaire answers (Documentation only, zero code changes).

---

## 1. Verification Results (Step 0)

Step 0 verification was conducted against the repository files, SQLite schema, migrations, seeders, and routes using an isolated runner:

| # | Checked Item | Repository Fact Discovered [V] |
| :--- | :--- | :--- |
| 1 | **Route Registry** | Total routes registered: **244 routes**. Snapshot generated and committed to `docs/ROUTES.snapshot.json`. The handoff table documented 55 foundation endpoints; the entire application has 244 total routes including public, resident, auth, staff, admin, and engine APIs. |
| 2 | **Table & Model Counts** | **53 database tables** exist in total schema (including migrations, pivots, sessions, cache, jobs). **44 Eloquent models** exist in `app/Models/` (21 models created in Phase 0 + 23 baseline models). |
| 3 | **Altered Tables & `referral_updates`** | `settings` table gained `type`, `group`, `label`, `description`, `is_confirmed`, `confirmed_by`, `confirmed_at`, `source_question`. `response_templates` gained `title`, `kind`, `created_by`. `referral_updates` table exists from migration `2026_11_18_000001_referral_tracking_and_proof_photos.php` and was reused without duplication. |
| 4 | **`App\Support\CaseStatus` Constants** | `CaseStatus` currently defines 7 constants: `FILED = 'filed'`, `IN_MEDIATION = 'in_mediation'`, `SETTLED = 'settled'`, `UNSETTLED = 'unsettled'`, `ESCALATED = 'escalated'`, `DISMISSED = 'dismissed'`, `WITHDRAWN = 'withdrawn'`. `IN_CONCILIATION` is **not yet in code** (planned for Phase 0.5). |
| 5 | **Permission Presets** | `config/permission_presets.php` defines 3 presets: `staff_full` (16 permissions), `case_encoder` (5 permissions), `reports_analyst` (3 permissions). `case_reviewer` does not exist in code yet. Exactly 18 permission slugs are currently seeded. |
| 6 | **Schema Column Values** | `summons.service_result` is varchar default `'pending'`; `summons.attendance` is varchar default `'pending'`; `sequence_no` (int) and `override_note` (text, nullable) exist. `cases.source` default is `'walk_in'`; `cases.is_confidential` default is `0`. `fiscal_years.status` has values `'active'`, `'inactive'`, `'closed'`, `'locked'`. `number_sequences` has unique key (`fiscal_year_id`, `type`). `parties.residency_status` has 4 values (`resident`, `non_resident`, `transient`, `unverified`). |
| 7 | **Referral Follow-Up Tests** | Two tests in `tests/Feature/ReferralAndProofWorkflowTest.php` assert 7 days: `test_referral_can_be_created_with_generated_reference_number` and `test_referral_service_creates_referral_with_proper_defaults`. These will need updating when code changes default to 3 days. |
| 8 | **Baseline Entity Counts** | 6 categories, 6 routing rules, 6 offices, 4 positions (`punong_barangay`, `barangay_secretary`, `lupon_member`, `barangay_tanod`), 6 case types, 4 escalation destinations, 18 permission slugs. |
| 9 | **Demo Credentials** | `admin@calarian.gov.ph`, `staff@calarian.gov.ph`, `clerk@calarian.gov.ph`, `cswdo@zamboanga.gov.ph` (all password `password123`). |
| 10 | **SPEC_ADDENDUM Section 11** | `BarangayCase::visibleTo()` scope implemented on model. Private disk configured in `config/filesystems.php`. `.env.example` mail keys present. `ReferralService::dueForFollowUp()` implemented. `<x-ui.global-search>` Blade component exists. Seeded response templates and privacy notice present. `fiscal_year_id` stamping in `ReportController@store` implemented. `User` does not yet implement `MustVerifyEmail`. |

---

## 2. Files Changed & Committed

All edits strictly followed the single-file commit rule with format `docs: <file> - <what>`:

1. `docs/FOUNDATION_DISCOVERY.md` — `docs: FOUNDATION_DISCOVERY.md - record verification round 2 results`
2. `docs/WORKFLOW.md` — `docs: WORKFLOW.md - revise case and referral lifecycles per round 2`
3. `docs/SPEC_ADDENDUM.md` — `docs: SPEC_ADDENDUM.md - align deadlines, CFA and v2 questions`
4. `docs/TEAM_WORKSTREAMS.md` — `docs: TEAM_WORKSTREAMS.md - update briefs, contracts and add WS6`
5. `docs/API.md` — `docs: API.md - update route tables and workstream partitions per snapshot`
6. `docs/TASKS.md` — `docs: TASKS.md - add Phase 0.5 alignment tasks and update workstreams`
7. `docs/TESTING.md` — `docs: TESTING.md - update demo credentials, CLI commands and workstream test suites`
8. `docs/CONVENTIONS.md` — `docs: CONVENTIONS.md - add status colors, migration ranges and governance rules`
9. `docs/UI_UX_SPEC.md` — `docs: UI_UX_SPEC.md - replace summons counter with deadline panel and add WS6 import pattern`
10. `docs/DECISIONS.md` — `docs: DECISIONS.md - record Revision 2 decisions D1-D5 and C1-C10`
11. `docs/DATABASE.md` — `docs/DATABASE.md - update user schema and planned Phase 0.5/WS6 changes`
12. `docs/ACADEMIC_DOCS_UPDATE.md` — `docs/ACADEMIC_DOCS_UPDATE.md - rewrite SR-22 and FR-31 for working-day deadlines, CFA and import`
13. `docs/ARCHITECTURE.md` — `docs: ARCHITECTURE.md - update service descriptions, tokens and permission counts`
14. `docs/CURRENT-STATE.md` — `docs: CURRENT-STATE.md - add Phase 0 completion and test metrics`
15. `docs/PROJECT.md` — `docs: PROJECT.md - update user roles and adviser scope status`
16. `docs/FOUNDATION_HANDOFF.md` — `docs: FOUNDATION_HANDOFF.md - correct counts, route map caveats and workstream assignments`
17. `docs/PHASE0_FOUNDATION_TASK.md` — `docs: PHASE0_FOUNDATION_TASK.md - mark executed and historical per Revision 2`
18. `docs/PLACEHOLDERS.md` — `docs: PLACEHOLDERS.md - update placeholder register to v2 43-question form`
19. `docs/REVISION_2.md` — `docs: REVISION_2.md - add revision 2 decisions, reconciliation and answers package`
20. `docs/ROUTES.snapshot.json` — `docs: ROUTES.snapshot.json - add full application route snapshot`

---

## 3. Differences Found Between Documents and Repository

1. **Unpatched Documentation**: `REVISION_2.md` claimed lines 12–15 that `WORKFLOW.md`, `SPEC_ADDENDUM.md`, and `TEAM_WORKSTREAMS.md` were already patched. Git diff and filesystem inspection proved they were completely unpatched prior to this round. They have now been patched in full.
2. **Table & Model Count Discrepancies**: Earlier documents reported 19 tables and 20 models. Actual counts verified in the repository are 53 database tables and 44 Eloquent models (21 models created in Phase 0).
3. **Route Counts**: Planning documents reported 35 stub endpoints; the handoff route table contained 55 endpoints; the actual application registers 244 routes.
4. **Permission Slugs**: Earlier texts mentioned 22 permission slugs; the actual seed in `RequiredDataSeeder` created 18 slugs. (Target is 22 plus `imports.manage` in Phase 0.5).
5. **Code Status vs Workflow Status**: `CaseStatus` in code currently holds 7 statuses (`filed`, `in_mediation`, `settled`, `unsettled`, `escalated`, `dismissed`, `withdrawn`). `in_conciliation` was in documentation but not in code.
6. **Permission Presets**: `case_reviewer` was documented in revision 2 but does not exist in `config/permission_presets.php`.

---

## 4. Code Problems Found (Not Fixed, Recorded per Rule 0)

Per Rule 0 ("Docs only. If you find a code problem, record it in the report, do not fix it"), the following code items must be addressed during Phase 0.5 execution:

1. **Hardcoded 7-Day Referral Tests**: `tests/Feature/ReferralAndProofWorkflowTest.php` has two test methods asserting `$referral->sent_at->addDays(7)`. When the default shifts to 3 days (`referral.follow_up_days`), these tests will fail until updated.
2. **Missing `IN_CONCILIATION` in `CaseStatus`**: `app/Support/CaseStatus.php` needs `const IN_CONCILIATION = 'in_conciliation'` and inclusion in `CaseStatus::all()`.
3. **Missing `case_reviewer` Preset**: `config/permission_presets.php` needs preset `case_reviewer` with slugs `cases.view`, `cases.view_confidential`, `reports.view`, `reports_center.view`.
4. **NumberingService Token Limitation**: `app/Services/NumberingService.php` does not yet parse the `{MM}` token, does not support sequence type `blotter`, and still permits `reset = never`.
5. **Summons Service Result Values**: `database/migrations/2026_11_22_000104_create_case_hearings_and_summons_tables.php` defined `service_result` with values (`served_personal`, `served_substituted`, `unserved`). Needs migration to map to (`received`, `refused`, `not_found`, `other`).
6. **Missing Case Columns**: `cases` table needs migration for `record_type`, `complaint_started_on`, `endorsed_to_pangkat_on`, `conciliation_extension_days`, `pangkat_chairman_personnel_id`, and `settlement_amount`.
7. **Missing Case Type Flags**: `case_types` needs `is_confidential_default` and `uses_kp_timeline`.
8. **Missing Case Escalation Columns**: `case_escalations` needs `requested_by_case_party_id`, `requested_on`, `cfa_number`, and `signed_by_personnel_id`.
9. **Missing Office Reference on Escalations**: `escalation_destinations` needs nullable `office_id` foreign key.
10. **Missing Table `referral_attachments`**: Needed on private disk for escalated case document handoff to agencies.
11. **User Model Interface**: `app/Models/User.php` does not yet implement `MustVerifyEmail`.

---

## 5. Open Questions for Secretary & Adviser

### A. Questions for the Barangay Secretary (Form Collection Day, Mon 2026-11-23)
1. **Mediation Clock Start Date**: Does the 15-working-day mediation clock start from the date filed, the date of the first summons, or the date of the first hearing?
2. **Pangkat Chairman Assignment**: Is the Pangkat Chairman a fixed official across all cases, or is a separate Pangkat Chairman constituted for each individual dispute? Who prepares the Certificate to File Action (CFA)?
3. **Case Numbering Example**: Please show a real physical case number from the blotter logbook. Does the number sequence restart every year (as Q9 indicates) or every month? Do blotter entries, formal complaints, and external referral letters share the same numbering sequence?
4. **Blotter vs Complaint Separation**: Are blotter-only entries and formal conciliation complaints recorded in the same physical logbook? Can an initial blotter entry later be upgraded into a formal complaint?
5. **BCPC and VAWC Cases**: Are BCPC (child protection) and VAWC (violence against women and children) handled as two separate case types? Do they follow the standard Katarungang Pambarangay 15-day timeline or special non-KP procedures?
6. **Lupon Access to Sensitive Cases**: May the Lupon member who personally encoded a confidential case continue viewing it? Are family and neighbor disputes confidential by default?
7. **Agency Referral Actions**: When an agency accesses a referral via the portal, should they be permitted to decline or close the referral, or only acknowledge receipt and log progress notes? Which specific documents must be transmitted to the PNP and CSWDO?
8. **Official Privacy Notice**: Does the barangay have an existing written Data Privacy Notice or consent statement (Q35 was "Not sure")?
9. **Paper Size Standards**: What paper size is standard for official printed reports and certifications (A4, Letter, or Long Bond)?
10. **Blotter Summary Recipient**: In Question 32, the blotter summary is marked "Not submitted" but lists an external recipient. Should blotter summaries be submitted to an external agency?
11. **Kagawad Roles**: Is "Kagawad (Peace & Order)" a single official? Which kagawads specifically handle minors' cases?
12. **Historical Scope**: Exactly which calendar years comprise "the last 4 years" for old records? Who on staff will type or digitize handwritten logbooks?

### B. Questions for the Academic Adviser
1. **Case Module Requirement**: Are the dispute blotter, summons, and conciliation tracking features required for the final thesis scope, given that Secretary answers Q15–Q27 demonstrate they are central to daily barangay operations?
2. **Legacy Import Scope**: Is the historical Excel/CSV file import acceptable as a stretch workstream (WS6)?

---

## 6. Proposed `REVISION_2.md` Addendum

The following addendum should be noted in `REVISION_2.md` Section 13:

> **Addendum (2026-11-22):**
> 1. Step 0 verification confirmed that `RequiredDataSeeder` seeds 18 permission slugs. Slugs `cases.view_confidential`, `backups.manage`, and `imports.manage` will bring the total to 22.
> 2. Total registered application routes number 244, as snapshotted in `docs/ROUTES.snapshot.json`.
> 3. Two existing tests in `tests/Feature/ReferralAndProofWorkflowTest.php` assert 7-day follow-up intervals and must be updated to 3 days in Phase 0.5.
