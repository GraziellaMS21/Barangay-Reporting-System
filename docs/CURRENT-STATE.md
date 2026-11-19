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

### Gap 1: Status Enum Discrepancy
- **In Code**: [`ReportStatus.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/app/Support/ReportStatus.php) and [`Report.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/app/Models/Report.php) define 14 statuses including `on_hold`, `out_of_scope`, and `closed`.
- **Target Design**: 12 statuses. `on_hold` is eliminated (treated as an `in_progress` update note). `out_of_scope` and `closed` are unified into `no_action`.
- **Action Required**: Remove `ON_HOLD`, `OUT_OF_SCOPE`, and `CLOSED` constants; add `NO_ACTION` and reason code constants.

### Gap 2: State Machine Integrity & Public `canTransition()` Method
- **In Code**: [`ReportStatusService`](file:///C:/githubFiles/Barangay-Reporting-System-1/app/Services/ReportStatusService.php) contains only an internal `private const ALLOWED` array checked during `transition()`.
- **Target Design**: Expose a clean, public helper `canTransition(string $from, string $to): bool` to allow UI components and controllers to validate transitions before execution.

### Gap 3: Database Schema & Migration for `no_action_reason`
- **In Code**: `reports` table lacks a dedicated column for `no_action_reason`.
- **Target Design**: Store `no_action_reason` in both an indexed column on `reports` (`reports.no_action_reason`) and within `status_logs` entries.
- **Data Migration Required**:
  - Convert existing `reports.status = 'out_of_scope'` $\rightarrow$ `'no_action'` (`no_action_reason = 'not_barangay_matter'`).
  - Convert existing `reports.status = 'closed'` $\rightarrow$ `'no_action'` (`no_action_reason = 'cannot_resolve'`).
  - Convert existing `reports.status = 'on_hold'` $\rightarrow$ `'in_progress'` (append progress note `"waiting_materials"`).
  - Update historical records in `status_logs` and `audit_logs`.

### Gap 4: Update Types & Progress Updates
- **In Code**: `Report::UPDATE_TYPES` routes `'waiting_materials'` to status `'on_hold'` and `'cannot_resolve'` to `'closed'`.
- **Target Design**: `'waiting_materials'` routes to status `'in_progress'` (with progress note), and `'cannot_resolve'` routes to `'no_action'` with `reason: cannot_resolve`.

### Gap 5: UI Badges & Action Modals
- **In Code**: [`status-badge.blade.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/resources/views/components/status-badge.blade.php) and [`review-workflow.blade.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/resources/views/admin/reports/review-workflow.blade.php) still display badges and modals for `on_hold`, `out_of_scope`, and `closed`.
- **Target Design**: Replace with a standardized `no_action` badge, reason code dropdown modal in review forms, and clear resident-facing reason text on [`resident/show.blade.php`](file:///C:/githubFiles/Barangay-Reporting-System-1/resources/views/resident/show.blade.php).

### Gap 6: New Recovery & Direct Transitions
- **In Code**: `merged` is terminal with zero outgoing transitions; `emergency_flagged` can only exit to `out_of_scope` or `under_review`.
- **Target Design**:
  - Add staff-only recovery: `merged -> under_review` (requires note).
  - Add staff-only internal emergency resolution: `emergency_flagged -> in_progress`.
  - Add staff-only direct resumption: `no_action -> in_progress` and `no_action -> reopened`.
