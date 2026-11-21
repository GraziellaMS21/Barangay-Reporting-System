# Implementation Roadmap & Pending Tasks

Ordered, outcome-driven tasks to bring the existing codebase into alignment with the canonical target design in [**`docs/WORKFLOW.md`**](file:///C:/githubFiles/Barangay-Reporting-System-1/docs/WORKFLOW.md).

> [!IMPORTANT]
> **Execution Rule**: Execute tasks in this exact numerical sequence. Do not combine Tasks 1 and 2 into a single PR or commit. Verify with tests after each task.

---

## Task 1: Remove `on_hold` Status [Completed]
- **Status**: Completed (2026-11-19)
- **Goal**: Eliminate `on_hold` from the core state machine, representing "waiting on materials" as a progress update note under `in_progress`.
- **Steps**:
  1. [x] Update [`Report::UPDATE_TYPES`](file:///C:/githubFiles/Barangay-Reporting-System-1/app/Models/Report.php#L34): Change `'waiting_materials'` target status from `'on_hold'` to `'in_progress'`.
  2. [x] Remove `ON_HOLD = 'on_hold'` constant from [`ReportStatus.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/app/Support/ReportStatus.php) and `Report::STATUSES`.
  3. [x] Remove `on_hold` from [`ReportStatusService::$ALLOWED`](file:///C:/githubFiles/Barangay-Reporting-System-1/app/Services/ReportStatusService.php) and `Report::STATUS_GROUPS`.
  4. [x] Create a database migration to update existing records:
     - `UPDATE reports SET status = 'in_progress' WHERE status = 'on_hold';`
     - Update associated `status_logs` entries (`database/migrations/2026_11_19_000001_remove_on_hold_status_and_migrate_records.php`).
  5. [x] Remove or update references in [`status-badge.blade.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/resources/views/components/status-badge.blade.php), [`review-workflow.blade.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/resources/views/admin/reports/review-workflow.blade.php), and [`referrals/index.blade.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/resources/views/admin/referrals/index.blade.php).
  6. [x] Update tests in `tests/Feature/ReportProgressUpdateTest.php`.
- **Verification**: `./vendor/bin/pint --test` passes, `ReportProgressUpdateTest` passes (5 tests, 48 assertions), and `on_hold` is completely absent from active status sets.

---

## Task 2: Replace `out_of_scope` & `closed` with `no_action` and Reason Codes [Completed]
- **Status**: Completed (2026-11-19)
- **Goal**: Unify non-actionable end states into `no_action` with mandatory reason codes and data migration.
- **Steps**:
  1. [x] Create a database migration (`database/migrations/2026_11_19_000002_replace_out_of_scope_and_closed_with_no_action.php`):
     - Added nullable indexed column `no_action_reason` to `reports`.
     - Added nullable string column `no_action_reason` to `status_logs`.
     - Data migration:
       - `reports.status = 'out_of_scope'` $\rightarrow$ `status = 'no_action'`, `no_action_reason = 'not_barangay_matter'`.
       - `reports.status = 'closed'` $\rightarrow$ `status = 'no_action'`, `no_action_reason = 'cannot_resolve'`.
       - Updated corresponding rows in `status_logs`.
  2. [x] Update [`ReportStatus.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/app/Support/ReportStatus.php):
     - Removed `OUT_OF_SCOPE` and `CLOSED`.
     - Added `NO_ACTION = 'no_action'`.
     - Added reason constants: `REASON_NOT_BARANGAY`, `REASON_EMERGENCY_REDIRECTED`, `REASON_CANNOT_RESOLVE`, `REASON_INVALID_REPORT`, `REASON_RESIDENT_UNREACHABLE` and `noActionReasons()` helper.
  3. [x] Update [`ReportStatusService.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/app/Services/ReportStatusService.php):
     - Replaced `out_of_scope` and `closed` with `no_action`.
     - Enforced valid `no_action_reason` and required explanation note when transitioning to `no_action`.
  4. [x] Update [`Report::UPDATE_TYPES`](file:///C:/githubFiles/Barangay-Reporting-System-1/app/Models/Report.php): Routed `'cannot_resolve'` to `'no_action'`.
  5. [x] Update [`ReferralService.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/app/Services/ReferralService.php) and review referral action fallback to route to `no_action`.
  6. [x] Update UI:
     - Added `no_action` badge style in [`status-badge.blade.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/resources/views/components/status-badge.blade.php).
     - Updated review modal in [`review-workflow.blade.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/resources/views/admin/reports/review-workflow.blade.php) to prompt for reason code and explanation note.
     - Displayed `no_action` reason and explanation card on [`resident/show.blade.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/resources/views/resident/show.blade.php).
  7. [x] Updated test assertions across Feature tests and added `tests/Feature/NoActionStatusWorkflowTest.php`.
- **Verification**: Zero occurrences of `out_of_scope` or `closed` remain in status sets/logic, `./vendor/bin/pint --test` passes, and all 60 feature tests pass.

---

## Task 3: Add Public `canTransition()` Method to `ReportStatusService` [Completed]
- **Status**: Completed (2026-11-19)
- **Goal**: Provide a clean public helper method to validate state transitions before invoking database mutations.
- **Steps**:
  1. [x] In [`ReportStatusService.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/app/Services/ReportStatusService.php), implemented `canTransition(string $fromStatus, string $toStatus): bool`.
  2. [x] Refactored `transition()` to use `$this->canTransition($report->status, $toStatus)`.
  3. [x] Added unit test suite in [`tests/Unit/ReportStatusServiceTest.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/tests/Unit/ReportStatusServiceTest.php) testing valid, invalid, allowed self-transitions, and disallowed self-transitions (4 tests, 36 assertions).
- **Verification**: `./vendor/bin/pint --test` passes, unit tests pass, and `canTransition()` is available across the system.

---

## Task 4: Implement New Recovery & Direct Transitions [Completed]
- **Status**: Completed (2026-11-19)
- **Goal**: Implement the approved edge-case transitions from Phase B requirements (`merged -> under_review`, `emergency_flagged -> in_progress`, `no_action -> in_progress`, and `no_action -> reopened`).
- **Authorization & Security Requirement**:
  - Staff-only transitions require role enforcement in the controller or a policy (`ReportStatusService::ALLOWED` validates only status pairs and does not know the actor).
  - Every staff-only transition requires a non-empty explanatory note.
  - Every staff override must record an audit log entry in `audit_logs`.
  - Feature tests must explicitly prove that a resident or unauthenticated guest cannot trigger these transitions.
- **Steps**:
  1. [x] In [`ReportStatusService.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/app/Services/ReportStatusService.php):
     - `merged` $\rightarrow$ `['under_review']` (staff-only recovery with note).
     - `emergency_flagged` $\rightarrow$ added `'in_progress'` (for emergencies handled directly on-site by Tanods).
     - `no_action` $\rightarrow$ `['reopened', 'in_progress']` (staff-only resumption with note).
  2. [x] In [`Admin\ReportController.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/app/Http/Controllers/Admin/ReportController.php), added `unmerge()` action (`reports.unmerge`), handled `emergency_handled_internally`, and logged audit logs with role enforcement (`role:admin,staff`).
  3. [x] Added UI unmerge dialog and emergency internally handled modal in [`review-workflow.blade.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/resources/views/admin/reports/review-workflow.blade.php).
  4. [x] Added feature tests in [`tests/Feature/RecoveryAndDirectTransitionsTest.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/tests/Feature/RecoveryAndDirectTransitionsTest.php) verifying unmerging, emergency resolution, resuming `no_action` cases, mandatory notes, audit logs, and negative tests proving residents/guests receive 401/403.
- **Verification**: `./vendor/bin/pint --test` passes and all 67 unit and feature tests pass.

---

## Task 5: End-to-End Verification of Code and Documentation [Completed]
- **Status**: Completed (2026-11-19)
- **Goal**: Ensure absolute parity between documentation and live codebase.
- **Steps**:
  1. [x] Run `php artisan test` to verify complete test suite passes (132 tests, 132 passed, 1,041 assertions).
  2. [x] Run `./vendor/bin/pint --test` to confirm strict formatting compliance across all modified and new files.
  3. [x] Run `php artisan route:list` to verify route bindings (all 103 routes clean).
  4. [x] Perform a text search across the codebase to ensure zero orphaned status strings remain (purged `out_of_scope` reference in `routes/engine.php` in favor of `Report::CLOSED_STATUSES`).
- **Done When**: Test suite is 100% green, Pint is clean, and docs in `docs/` reflect the exact code state.

---

## Task 6: Phase 0 — Foundation & Infrastructure [Completed]
- **Status**: Completed (2026-11-22)
- **Branch**: `feature/phase0-foundation`
- **Goal**: Implement complete foundation layer across schema, services, middleware, route stubs, navigation, shared UI components, and test harness without building feature screens beyond stubs.
- **Implemented Scope**:
  1. [x] **Dependencies**: `barryvdh/laravel-dompdf` (^3.1), `openspout/openspout` (^5.15).
  2. [x] **Database Migrations**: 6 sequential migrations establishing 19 new tables, foreign keys, and indexes.
  3. [x] **Models & Factories**: 20 new Eloquent models with typed casts, relations, and corresponding factories; `HasConfirmation` and `BelongsToFiscalYear` concerns.
  4. [x] **Core Services**: `SettingsService` (with `setting()` helper), `FiscalYearService`, `FiscalYearContext`, `FiscalYearGuard`, `NumberingService`, `PermissionService`, `AuditLogger`, `PersonnelService`, and stubs for future workstreams.
  5. [x] **Middleware & Gates**: `SetupCompleted`, `CheckPermission`, `EnsurePasswordChanged`, `EnsureAgencyActive`, and updated `EnsureRole` with testing harness extensions (`actingAsAdmin`, `actingAsStaff`, `actingAsEncoder`, `actingAsAgency`).
  6. [x] **Routes & Controller Stubs**: 7 route files (`routes/setup.php`, `settings.php`, `people.php`, `reports_center.php`, `cases.php`, `case_actions.php`, `agency.php`) with 35 new routes returning structured stub views.
  7. [x] **Navigation & Shared UI Components**: `config/navigation.php`, updated `layouts/admin.blade.php`, `layouts/agency.blade.php`, `layouts/print.blade.php`, and 11 Blade components in `resources/views/components/ui/` with interactive showcase at `/admin/_ui`.
  8. [x] **Seeders & Commands**: `RequiredDataSeeder` (all placeholders unconfirmed), `DemoDataSeeder` (active FY, realistic Calarian cases, demo accounts), `app:empty`, and `app:demo`.
  9. [x] **Configuration & Presets**: `config/features.php`, `config/documents.php`, `config/permission_presets.php`, and staff preset migration.
  10. [x] **Verification**: All 184 tests pass (1,374 assertions), Pint is 100% clean.

---

## Upcoming Team Workstreams (Ready to Start)

All workstreams branch from `feature/phase0-foundation` or develop on designated feature branches as specified in `docs/TEAM_WORKSTREAMS.md`:

| Workstream | Scope / Objective | Status | Target Branch |
| :--- | :--- | :--- | :--- |
| **WS1: Setup Wizard & Administration** | Complete 5-step interactive wizard at `/setup`, position/signatory management, fiscal year lifecycle UI, numbering configuration, general settings. | **READY TO START** | `feature/ws1-setup-admin` |
| **WS2: Reports Center & Analytics** | Report generation engine (PDF via DomPDF, Excel/CSV via OpenSpout), template runner, signatory block integration, DILG/KP compliance exports. | **READY TO START** | `feature/ws2-reports-center` |
| **WS3: Case Module (Blotter & Conciliation)** | Case filing, party profiles & residency tracking, 3-summons issuance limit, hearing scheduler, amicable settlement repudiation, court escalation (CFA). | **READY TO START** | `feature/ws3-case-module` |
| **WS4: External Partner Agency Portal** | Agency registration & admin approval flow, external referral inbox, acceptance, progress milestone updates, agency team management. | **READY TO START** | `feature/ws4-agency-portal` |
| **WS5: Navigation, Polish & Integration** | Global cross-entity search bar, year switcher dropdown persistence, pending placeholder audit badges, mobile responsiveness audit. | **READY TO START** | `feature/ws5-navigation-polish` |

