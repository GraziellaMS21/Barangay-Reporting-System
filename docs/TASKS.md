# Implementation Roadmap & Pending Tasks

Ordered, outcome-driven tasks to bring the existing codebase into alignment with the canonical target design in [**`docs/WORKFLOW.md`**](file:///C:/githubFiles/Barangay-Reporting-System-1/docs/WORKFLOW.md) and [**`docs/REVISION_2.md`**](file:///C:/githubFiles/Barangay-Reporting-System-1/docs/REVISION_2.md).

> [!IMPORTANT]
> **Execution Rule**: Execute tasks in this exact numerical sequence. Do not combine tasks into a single unreviewed commit. Verify with tests after each step.

---

## Task 1: Remove `on_hold` Status [Completed]
- **Status**: Completed (2026-11-19)
- **Goal**: Eliminate `on_hold` from the core state machine, representing "waiting on materials" as a progress update note under `in_progress`.
- **Verification**: `./vendor/bin/pint --test` passes, `ReportProgressUpdateTest` passes (5 tests, 48 assertions), and `on_hold` is completely absent from active status sets.

---

## Task 2: Replace `out_of_scope` & `closed` with `no_action` and Reason Codes [Completed]
- **Status**: Completed (2026-11-19)
- **Goal**: Unify non-actionable end states into `no_action` with mandatory reason codes and data migration.
- **Verification**: Zero occurrences of `out_of_scope` or `closed` remain in status sets/logic, `./vendor/bin/pint --test` passes, and all 60 feature tests pass.

---

## Task 3: Add Public `canTransition()` Method to `ReportStatusService` [Completed]
- **Status**: Completed (2026-11-19)
- **Goal**: Provide a clean public helper method to validate state transitions before invoking database mutations.
- **Verification**: `./vendor/bin/pint --test` passes, unit tests pass, and `canTransition()` is available across the system.

---

## Task 4: Implement New Recovery & Direct Transitions [Completed]
- **Status**: Completed (2026-11-19)
- **Goal**: Implement the approved edge-case transitions from Phase B requirements (`merged -> under_review`, `emergency_flagged -> in_progress`, `no_action -> in_progress`, and `no_action -> reopened`).
- **Verification**: `./vendor/bin/pint --test` passes and all 67 unit and feature tests pass.

---

## Task 5: End-to-End Verification of Code and Documentation [Completed]
- **Status**: Completed (2026-11-19)
- **Goal**: Ensure absolute parity between documentation and live codebase.
- **Done When**: Test suite is 100% green, Pint is clean, and docs in `docs/` reflect the exact code state.

---

## Task 6: Phase 0 — Foundation & Infrastructure [Completed]
- **Status**: Completed (2026-11-22)
- **Branch**: `feature/phase0-foundation` (merged to `main`)
- **Goal**: Implement complete foundation layer across schema, services, middleware, route stubs, navigation, shared UI components, and test harness without building feature screens beyond stubs.
- **Verified Deliverables**:
  1. [x] **Dependencies**: `barryvdh/laravel-dompdf` (^3.1), `openspout/openspout` (^5.15).
  2. [x] **Database Migrations**: 22 foundation tables and altered schema (`settings`, `response_templates`, `referral_updates` reused).
  3. [x] **Models & Factories**: 21 new Eloquent models (44 models total across app) with typed casts, relations, and factories; `HasConfirmation` and `BelongsToFiscalYear` concerns.
  4. [x] **Core Services**: `SettingsService` (with `setting()` helper), `FiscalYearService`, `FiscalYearContext`, `FiscalYearGuard`, `NumberingService`, `PermissionService`, `AuditLogger`, `PersonnelService`, and initial stubs.
  5. [x] **Middleware & Gates**: `SetupCompleted`, `CheckPermission`, `EnsurePasswordChanged`, `EnsureAgencyActive`, and updated `EnsureRole` with test helpers (`actingAsAdmin`, `actingAsStaff`, `actingAsEncoder`, `actingAsAgency`).
  6. [x] **Routes & Controller Stubs**: 244 total registered routes snapshotted in `docs/ROUTES.snapshot.json` across 7 foundation route files.
  7. [x] **Navigation & Shared UI Components**: `config/navigation.php`, updated `layouts/admin.blade.php`, `layouts/agency.blade.php`, `layouts/print.blade.php`, and 11 Blade components in `resources/views/components/ui/` with interactive showcase at `/admin/_ui`.
  8. [x] **Seeders & Commands**: `RequiredDataSeeder` (idempotent, unconfirmed placeholders), `DemoDataSeeder` (active FY, realistic Calarian cases, demo accounts: `admin@calarian.gov.ph`, `staff@calarian.gov.ph`, `clerk@calarian.gov.ph`, `cswdo@zamboanga.gov.ph`), `app:empty`, and `app:demo`.
  9. [x] **Configuration & Presets**: `config/features.php`, `config/documents.php`, `config/permission_presets.php` (presets `staff_full`, `case_encoder`, `reports_analyst`).
  10. [x] **Verification**: All 184 tests pass (1,374 assertions), Pint is 100% clean.

---

## Task 7: Phase 0.5 — Foundation Alignment (Lead) [Pending Execution]
- **Status**: Ready to Start
- **Branch**: `feature/phase0-5-alignment`
- **Goal**: Align Phase 0 foundation code and schema with the Secretary's answers and decisions from `REVISION_2.md` before team workstreams branch.
- **Sub-tasks**:
  1. [ ] **Migrations (`2026_11_23_0002NN_`)**:
     - `summons`: update `service_result` to string(20), map built values to `pending`, `received`, `refused`, `not_found`, `other` [A Q21, R10].
     - `cases`: add `record_type string(20) default 'complaint'` (`blotter`, `complaint`) [A Q18]; `endorsed_to_pangkat_on date ?`; `conciliation_extension_days unsigned tinyint default 0` [A Q25]; `pangkat_chairman_personnel_id fk personnel ?` [A Q5]; `settlement_amount decimal(12,2) ?` [A Q24]; `complaint_started_on date ?` [P].
     - `case_types`: add `is_confidential_default bool default false` [A Q7, C6]; `uses_kp_timeline bool default true` [P].
     - `case_escalations`: add `requested_by_case_party_id fk case_parties ?` [A Q23]; `requested_on date ?`; `cfa_number string(60) ?`; `signed_by_personnel_id fk personnel ?` [A Q5].
     - `escalation_destinations`: add `office_id fk offices ?` [R12].
     - `referral_attachments` (new): `id`, `referral_id fk cascade`, `label str`, `path str` (private disk), `uploaded_by fk users ?`, timestamps [C7].
  2. [ ] **Services & Helpers**:
     - `NumberingService`: add `{MM}` token, type `blotter`, format validation, enforce fiscal year reset [C4, R7].
     - `App\Support\WorkingDays`: calculate working days excluding weekends and `calendar.holidays` setting [C1, A Q25].
     - `App\Support\CaseStatus`: add `IN_CONCILIATION = 'in_conciliation'` constant and update `all()` [5.1].
     - `SummonsCounterService`: adjust contract to return info-only count (`limit => null`, `eligible => false`) [C1].
     - `SignatoryResolver`: update signature to accept optional `?BarangayCase $case = null` [5.4].
     - `config/permission_presets.php`: add `case_reviewer` preset [5.6].
     - `config/features.php`: add `legacy_import => false`.
     - Seed permission `imports.manage`.
  3. [ ] **Tests & Fixtures**:
     - Update referral follow-up tests in `tests/Feature/ReferralAndProofWorkflowTest.php` to reflect 3 days default [A Q12].
     - Add unit tests for `WorkingDays` (weekends, holidays, boundaries).
     - Add unit tests for numbering format `{MM}-{SEQ:3}-{YEAR}`.
     - Update `DemoDataSeeder` with a Lupon member, a Pangkat Chairman, a blotter-only entry, and a case at day 14 of 15.

---

## Upcoming Team Workstreams (Ready to Branch after Phase 0.5)

All workstreams branch from `main` after Phase 0.5 merges, developing on isolated feature branches per `docs/TEAM_WORKSTREAMS.md`:

| Workstream | Scope / Objective | Owner | Target Branch |
| :--- | :--- | :--- | :--- |
| **WS1: Setup, Fiscal Year & Settings** | 7-step wizard at `/setup`, fiscal year lifecycle UI, numbering config with `{MM}`, calendar holidays page, backups, global multi-year search, pending confirmation register. | Lead | `feature/ws1-setup` |
| **WS2: People, Access & Reports Center** | Personnel directory, positions, signatories matrix, granular permissions with presets, DILG reports (yearly KP accomplishment, monthly case summary, purok-problem matrix) with print layout. | Member | `feature/ws2-people-reports` |
| **WS3: Case Intake & Parties** | Case intake with blotter vs complaint selection, trimmed party form with residency verification, duplicate party & case warnings, expected party merge (`/admin/parties/{id}/merge`), canonical status matrix. | Member | `feature/ws3-cases` |
| **WS4: Summons, Hearings & Deadlines** | Sequential summons without artificial limits, 15-working-day mediation/conciliation countdowns, hearings schedule, amicable settlement capture, CFA printables, complainant-led escalation. | Member | `feature/ws4-case-actions` |
| **WS5: Agency Accounts & Referrals** | Outside agency registration with ID/letter upload, Secretary approval, agency portal inbox, referral progress updates, referral attachments on private disk, 3-day follow-up. | Member | `feature/ws5-agency` |
| **WS6 (Stretch): Legacy File Import** | Import the last 4 record years of cases, blotter entries, and parties from Excel/CSV without AI, column mapping preview, chunked commit, batch rollback, blank downloadable template. | Member (WS2/WS5) | `feature/ws6-import` |
