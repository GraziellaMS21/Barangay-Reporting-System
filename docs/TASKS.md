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

## Task 3: Add Public `canTransition()` Method to `ReportStatusService`
- **Goal**: Provide a clean public helper method to validate state transitions before invoking database mutations.
- **Steps**:
  1. In [`ReportStatusService.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/app/Services/ReportStatusService.php), implement:
     ```php
     public function canTransition(string $fromStatus, string $toStatus): bool
     {
         return in_array($toStatus, self::ALLOWED[$fromStatus] ?? [], true);
     }
     ```
  2. Refactor `transition()` to use `$this->canTransition($report->status, $toStatus)`.
  3. Add unit test coverage in `tests/Unit/ReportStatusServiceTest.php` testing valid, invalid, and self-transitions.
- **Done When**: Controllers, Blade components, and tests can call `canTransition()` directly.

---

## Task 4: Implement New Recovery & Direct Transitions
- **Goal**: Implement the approved edge-case transitions from Phase B requirements (`merged -> under_review`, `emergency_flagged -> in_progress`, `no_action -> in_progress`, and `no_action -> reopened`).
- **Authorization & Security Requirement**:
  - Staff-only transitions require role enforcement in the controller or a policy (`ReportStatusService::ALLOWED` validates only status pairs and does not know the actor).
  - Every staff-only transition requires a non-empty explanatory note.
  - Every staff override must record an audit log entry in `audit_logs`.
  - Feature tests must explicitly prove that a resident or unauthenticated guest cannot trigger these transitions.
- **Steps**:
  1. In [`ReportStatusService.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/app/Services/ReportStatusService.php), configure:
     - `merged` $\rightarrow$ `['under_review']` (staff-only recovery with note).
     - `emergency_flagged` $\rightarrow$ add `'in_progress'` (for emergencies handled directly on-site by Tanods).
     - `no_action` $\rightarrow$ add `['reopened', 'in_progress']` (staff-only resumption with note).
  2. In [`Admin\ReportController.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/app/Http/Controllers/Admin/ReportController.php), add controller actions/dialogs for staff to unmerge a duplicate and resume a `no_action` report with role enforcement (`role:staff`).
  3. Add feature tests for unmerging, emergency resolution, and resuming `no_action` cases, including negative tests ensuring residents/guests cannot invoke them.
- **Done When**: Accidental merges can be safely recovered, locally resolved emergencies can be tracked to completion, and role enforcement blocks unauthorized attempts.

---

## Task 5: End-to-End Verification of Code and Documentation
- **Goal**: Ensure absolute parity between documentation and live codebase.
- **Steps**:
  1. Run `php artisan test` to verify complete test suite passes.
  2. Run `./vendor/bin/pint --test` to confirm strict formatting compliance.
  3. Run `php artisan route:list` to verify route bindings.
  4. Perform a text search across the codebase to ensure zero orphaned status strings remain.
- **Done When**: Test suite is 100% green, Pint is clean, and docs in `docs/` reflect the exact code state.
