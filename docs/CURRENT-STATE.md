# Current Implementation State & Code Gap Analysis

This document provides a realistic, code-verified audit of what is currently implemented, partially built, and pending in the repository.

> [!NOTE]
> **Documentation as Target Design**: The documentation suite in `docs/` defines the **target design** (12 statuses, `no_action` unification, progress-note `waiting_materials`, etc.). The sections below outline the exact delta between this target design and the existing codebase.

---

## 1. Feature Implementation Matrix

| Phase / Module | Verified Code State | Summary of Existing Implementation |
| :--- | :---: | :--- |
| **Phase 0: Setup** | **Done** | Laravel 13, SQLite/MySQL, Vite, Tailwind CSS v4, Leaflet.js, FontAwesome installed and functional. |
| **Phase 1: Guest Reporting & Track** | **Done** | Single-card resident submission form, Leaflet map pin selection, tracking code generator (`BRG-XXXXXX`), public tracking view. |
| **Phase 2: Staff Auth & Basic Queue** | **Done** | Session auth, `role` middleware (`staff`, `admin`), report queue with pagination and basic filters. |
| **Phase 3: Gemini AI Triage** | **Done** | `GeminiService` with structured JSON output, photo description, emergency detection, error resilience (`ai_status = failed`). |
| **Phase 4: Routing & Referrals** | **Done** | `RoutingService` and `ReferralService`, jurisdiction checks, external referral tracking, formal letter generation. |
| **Phase 5: Emergency & Duplicates** | **Done** | `emergency_flagged` status, 911 banner, `DuplicateService` (Haversine distance matching), `PriorityService` scoring. |
| **Phase 6: Maps & Analytics** | **Done** | Staff `/admin/map`, public `/map` (excluding emergencies), KPI tiles, `HotspotService`, and aggregate Gemini insights. |
| **Phase 7: Admin Settings & Auditing** | **Done** | Settings CRUD (categories, offices, routing rules, weights, emergency contacts), security `audit_logs` tracking overrides. |
| **Phase 8: Foundation Improvements** | **Done** | Modular route files (`resident.php`, `staff.php`, `admin.php`, `engine.php`), domain events, database structure. |
| **Phase 9: Resident Form Redesign** | **Done** | Single-card layout, client-side canvas image compression, single danger checkbox, dynamic category questions. |
| **Phase 10: Case Management** | **Done** | 3-way review card, review gate, assignee filter, internal notes, `info_requests` thread, `resolution_proofs` multi-image upload. |
| **Phase 11: Rules & AI Engine** | **Done** | Inline review comparison, timeout handling (30s), fallback models, `/api/nearby-reports` endpoint. |
| **Phase 12: Admin & Notifications** | **Done** | `NotificationService`, transaction mail dispatch (`ReportReceived`, `ReportStatusChanged`, `EmergencyAlert`), CSV export. |

---

## 2. Verified Code Gaps (Target Design vs. Codebase)

The following specific gaps exist between the current codebase and the canonical design in [**`docs/WORKFLOW.md`**](file:///C:/githubFiles/Barangay-Reporting-System-1/docs/WORKFLOW.md):

### Gap 1: Status Enum Discrepancy [Resolved]
- **Status**: Completed in Task 1 (`on_hold` removed) and Task 2 (`out_of_scope` & `closed` merged into `no_action`).
- **In Code**: [`ReportStatus.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/app/Support/ReportStatus.php) and [`Report.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/app/Models/Report.php) now define the exact 12 canonical statuses and 5 reason code constants.

### Gap 2: State Machine Integrity & Public `canTransition()` Method [Resolved]
- **Status**: Completed in Task 3.
- **In Code**: [`ReportStatusService::canTransition(string $from, string $to): bool`](file:///C:/githubFiles/Barangay-Reporting-System-1/app/Services/ReportStatusService.php) is implemented, used internally by `transition()`, and covered by unit tests in [`tests/Unit/ReportStatusServiceTest.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/tests/Unit/ReportStatusServiceTest.php).

### Gap 3: Database Schema & Migration for `no_action_reason` [Resolved]
- **Status**: Completed in Task 2 (`database/migrations/2026_11_19_000002_replace_out_of_scope_and_closed_with_no_action.php`).
- **In Code**: Dedicated indexed column `no_action_reason` exists on `reports` and string column on `status_logs`. Historical data was migrated to `no_action` with respective reason codes.

### Gap 4: Update Types & Progress Updates [Resolved]
- **Status**: Completed in Task 1 (`waiting_materials` $\rightarrow$ `in_progress`) and Task 2 (`cannot_resolve` $\rightarrow$ `no_action` with `reason: cannot_resolve`).

### Gap 5: UI Badges & Action Modals [Resolved]
- **Status**: Completed in Task 2.
- **In Code**: [`status-badge.blade.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/resources/views/components/status-badge.blade.php) renders `no_action`; [`review-workflow.blade.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/resources/views/admin/reports/review-workflow.blade.php) has the `#no-action-modal` with reason code dropdown and required explanation; [`resident/show.blade.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/resources/views/resident/show.blade.php) displays the No Action notice card.

### Gap 6: New Recovery & Direct Transitions [Resolved]
- **Status**: Completed in Task 4.
- **In Code**:
  - Staff-only recovery `merged -> under_review` implemented with required explanation note, duplicate detachment, and `audit_logs` tracking.
  - Staff-only internal emergency resolution `emergency_flagged -> in_progress` implemented with required note and `audit_logs` tracking.
  - Staff-only direct resumption `no_action -> in_progress` and `no_action -> reopened` implemented with required reason, `audit_logs` tracking, and reason reset.
  - Role enforcement (`role:admin,staff`) verified with negative tests.

---

## 3. End-to-End Verification Status (Task 5)

- **Test Suite**: 100% green (`132 tests, 132 passed, 1,041 assertions`).
- **Code Style**: Strictly compliant with `./vendor/bin/pint --test` across all created and touched files.
- **Routing**: All 103 routes verified cleanly via `php artisan route:list`.
- **Status Cleanliness**: Zero orphaned status references (`on_hold`, `out_of_scope`, `closed`) remaining in application code, views, or routing.
- **Parity**: Live codebase matches canonical target documentation in `docs/WORKFLOW.md`, `docs/DATABASE.md`, and `docs/API.md`.

---

## 4. UI/UX Refactor Progress

### Batch 0: Staff Bugs and Leftover Wording [Completed]
- **Status**: Completed (2026-11-19)
- **Delivered**:
  - **Queue Kebab Menu `no_action` Reason Code**: Added dynamic `no_action_reason` selection to row kebab status forms in [`index.blade.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/resources/views/admin/reports/index.blade.php), ensuring reason code and explanation note are submitted together.
  - **Comprehensive "Take No Action" Availability**: Updated [`review-workflow.blade.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/resources/views/admin/reports/review-workflow.blade.php) so the action button and modal are available on all statuses where `ReportStatusService::canTransition($status, 'no_action')` allows it (`routed`, `in_progress`, `needs_info`, `awaiting_confirmation`, etc.).
  - **Purged Leftover Legacy Wording**: Removed all user-facing "out of scope", "closed", and "on hold" phrases from `index.blade.php`, `review-workflow.blade.php`, `resident/show.blade.php`, and `Report.php` update templates.
  - **Reopen Reason Validation**: Enforced minimum 10-character explanation (`min:10`) in [`TrackController.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/app/Http/Controllers/TrackController.php) and [`show.blade.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/resources/views/resident/show.blade.php) for residents disputing case resolution.
  - **Documentation Alignment**: Synchronized [`docs/DATABASE.md`](file:///C:/githubFiles/Barangay-Reporting-System-1/docs/DATABASE.md) and [`docs/API.md`](file:///C:/githubFiles/Barangay-Reporting-System-1/docs/API.md) to reflect active statuses, active endpoints, and payload schemas.

### Batch 1: Resident Screens [Completed]
- **Status**: Completed (2026-11-19)
- **Delivered**:
  - **Submit Button Loading State & Double-Click Protection**: `#report-form` disables submit button upon submission and displays spinner with bilingual text ("Submitting report..." / "Isinusumite ang ulat...") in [`resident/create.blade.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/resources/views/resident/create.blade.php).
  - **Server-Side Duplicate Submission Protection**: Enforced in [`ReportController.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/app/Http/Controllers/ReportController.php); rapid identical re-submits (same description and category within 20s) gracefully redirect to the existing report success page without creating duplicate records.
  - **Touch Target Accessibility ($\ge$ 44px)**: Photo remove button enlarged to 44x44px in [`resources/css/app.css`](file:///C:/githubFiles/Barangay-Reporting-System-1/resources/css/app.css); emergency contact telephone chips enlarged to min 44px height in [`resident/success.blade.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/resources/views/resident/success.blade.php).
  - **Bilingual Confirmation Screen & Copy Feedback**: Added full English and Filipino translations dynamically reflecting `localStorage.calarian_lang`, added clipboard fallback (`execCommand`) and `aria-live="polite"` confirmation announcement in [`resident/success.blade.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/resources/views/resident/success.blade.php).
  - **Keyboard-Accessible Star Rating**: Converted rating stars to `<button type="button">` with `role="radiogroup"`, `role="radio"`, `aria-checked`, arrow key navigation, Space/Enter selection, and screen reader announcements in [`resident/show.blade.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/resources/views/resident/show.blade.php).
  - **"What Happens Next?" Banner**: In [`resident/show.blade.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/resources/views/resident/show.blade.php), clear status-specific next action guidance and SLA response target derived strictly from the report category's `sla_hours`.
  - **Balanced Confirm and Reopen Choices**: Upgraded resident resolution review in [`resident/show.blade.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/resources/views/resident/show.blade.php) to equal side-by-side cards ("Yes, Issue is Fixed" vs. "No, Still Needs Work").
  - **Category Required with "Other" Fallback**: Removed "(optional)" badge, enforced required validation with clear error message, and sorted categories so "Other" appears as fallback.
  - **Feature Tests**: Added comprehensive test coverage in [`tests/Feature/ReportPrivacyAndValidationTest.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/tests/Feature/ReportPrivacyAndValidationTest.php). Total test suite: 137 tests, 1,069 assertions, 100% green.

### Batch 2: Staff Queue and Review Page [Completed]
- **Status**: Completed (2026-11-19)
- **Delivered**:
  - **Status Shown as Text Badge in Queue**: Replaced the icon-only status cell with `<x-status-badge :status="$report->status" />` in [`index.blade.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/resources/views/admin/reports/index.blade.php), showing full text pills with status icon and readable label across all 12 canonical statuses. Retained modifier icons (Overdue, Escalated, Reopened) alongside the badge.
  - **Emergency Queue Row & Badge Distinction**: Added `.is-emergency-row` with subtle red accent border (`border-left: 3.5px solid var(--red)`) and red "Emergency" pill badge in table rows in [`index.blade.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/resources/views/admin/reports/index.blade.php) and [`app.css`](file:///C:/githubFiles/Barangay-Reporting-System-1/resources/css/app.css).
  - **Gate Hints Shown Next to Category and Urgency in Card 2**:
    - Added contextual field-level gate hint pills directly adjacent to the Category and Urgency controls in Card 2 (`review-workflow.blade.php`), clearly marking fields as `Required to route` (when unselected) or `Ready to route` (when selected).
    - Added Card 2 header/action bar gate summary pill (`[data-gate-reason-card2]`) summarizing review gate requirements without needing to scroll to Card 3.
    - Updated `updateGate()` in JavaScript to dynamically keep both Card 2 inline gate hints and Card 3 gate reason in sync upon selection, AI suggestion acceptance, or manual edit.
  - **Feature Tests**: Added tests in [`tests/Feature/StaffCaseManagementTest.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/tests/Feature/StaffCaseManagementTest.php) verifying text status badge, emergency row distinction, and Card 2 gate hints. Total test suite: 139 tests, 1,079 assertions, 100% green.
