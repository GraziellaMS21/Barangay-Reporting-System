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

### Gap 2: State Machine Integrity & Public `canTransition()` Method
- **In Code**: [`ReportStatusService`](file:///C:/githubFiles/Barangay-Reporting-System-1/app/Services/ReportStatusService.php) contains only an internal `private const ALLOWED` array checked during `transition()`.
- **Target Design**: Expose a clean, public helper `canTransition(string $from, string $to): bool` to allow UI components and controllers to validate transitions before execution (Task 3).

### Gap 3: Database Schema & Migration for `no_action_reason` [Resolved]
- **Status**: Completed in Task 2 (`database/migrations/2026_11_19_000002_replace_out_of_scope_and_closed_with_no_action.php`).
- **In Code**: Dedicated indexed column `no_action_reason` exists on `reports` and string column on `status_logs`. Historical data was migrated to `no_action` with respective reason codes.

### Gap 4: Update Types & Progress Updates [Resolved]
- **Status**: Completed in Task 1 (`waiting_materials` $\rightarrow$ `in_progress`) and Task 2 (`cannot_resolve` $\rightarrow$ `no_action` with `reason: cannot_resolve`).

### Gap 5: UI Badges & Action Modals [Resolved]
- **Status**: Completed in Task 2.
- **In Code**: [`status-badge.blade.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/resources/views/components/status-badge.blade.php) renders `no_action`; [`review-workflow.blade.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/resources/views/admin/reports/review-workflow.blade.php) has the `#no-action-modal` with reason code dropdown and required explanation; [`resident/show.blade.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/resources/views/resident/show.blade.php) displays the No Action notice card.

### Gap 6: New Recovery & Direct Transitions
- **In Code**: `no_action -> in_progress` and `no_action -> reopened` are implemented in `ReportStatusService`.
- **Target Design Remaining (Task 4)**:
  - Add staff-only recovery: `merged -> under_review` (requires note and audit log).
  - Add staff-only internal emergency resolution: `emergency_flagged -> in_progress`.
