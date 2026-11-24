# Decisions

| Date | Decision |
|------|----------|
| 2026-11-16 | Phase 9 resident form redesigned to 4 steps; category_id required; preferred_contact derived from provided email/phone; emergency contacts filtered (no N/A/duplicate 911). |
| 2026-11-17 | Single-card resident report form with no stepper or review step. | Reduces visual clutter on the busy landing page. |
| 2026-11-17 | Replaced Yes/No/Not sure safety toggles with one "Someone is in danger right now" checkbox. | Simpler; emergency panel appears only when checked. |
| 2026-11-17 | Other situation questions hidden under collapsed "More details (optional)" and unset by default. | Keeps required path short. |
| 2026-11-17 | Map centered and bounded on Barangay Calarian. | Previous Yubengco Starmall center was wrong. |
| 2026-11-17 | Nearby reports shown as one line under the map. | Compact disclosure. |
| 2026-11-17 | Consent checkbox sets consent_at; privacy notice opens in a small modal. | Required consent without a full review step. |
| 2026-11-17 | Dashboard simplified to 4 action tiles, one needs-attention list, and collapsed more charts. | Reduces staff workload. |
| 2026-11-17 | Single Decision panel on review page with one save. | Replaces multiple separate forms. |
| 2026-11-17 | Status transition map enforced in ReportStatusService. | Officials decide allowed transitions. |
| 2026-11-17 | Resident message optional, template default when blank. | Faster review. |
| 2026-11-17 | Bulk confirm runs confirm-and-route per report for ready reports only. | Saves staff time without skipping review. |
| 2026-11-17 | Audit logs only staff overrides of prefilled category, urgency, office. | Avoids noise. |
| 2026-11-17 | Queue uses five fixed columns with no horizontal scroll. | Improves visual hierarchy. |
| 2026-11-17 | KPI tiles ordered Overdue, High/critical, Needs review, Awaiting resident with All clear state. | Clarifies priority. |
| 2026-11-17 | Needs attention merged list beside Gemini insights. | Reduces clutter. |
| 2026-11-17 | Review page uses workflow progress strip and single action card. | Guides staff step by step. |
| 2026-11-17 | Review page uses single-column stacked cards (Resident report → Review AI suggestions → Route and assign). | Mobile-first; no side-by-side columns to break at 375px. |
| 2026-11-17 | Resident/AI/Final comparison table with per-field badges (Matches, Differs, AI only) and final-state badges (AI suggested, Accepted, Edited, Resident). | Staff always know the source of each final value at a glance. |
| 2026-11-17 | Accept all AI suggestions plus a review gate that counts reviewed fields and blocks Confirm and route until required fields are reviewed. | Nothing routes until staff have reviewed it; Accept all keeps it fast. |
| 2026-11-17 | One Confirm and route button; "Save review without routing" moved into the More menu. | Single primary action removes ambiguity. |
| 2026-11-17 | Late AI results never overwrite staff edits; poll ends after 24 attempts and never changes final values automatically. | Protects manual review while AI runs in background. |
| 2026-11-17 | Override-only audit (only AI-suggested value changes or office change). | Avoids noise. |
| 2026-11-17 | AI states: ready, low confidence, pending, failed/unavailable. | Layer 1 works without AI. |
| 2026-11-17 | Bulk confirm shown as sticky bar after selection. | Prevents accidental bulk action. |
| 2026-11-17 | Activity merges information requests and replies with newest-first ordering. | One timeline instead of scattered lists. |
| 2026-11-17 | Additive migration adds nullable resident_category_id, resident_purok_id, resident_landmark snapshot columns instead of new tables. | Preserves resident's original values on first review with zero impact on existing queries. |
| 2026-11-17 | Shared form pattern with stacked labels, regular-weight values, required category and urgency before routing, removal of duplicate recommended-office displays. | Review page redesign. |
| 2026-11-17 | Photo-mismatch alert rendered as its own amber card between the Resident report card and the review form; sticky footer bar removed and actions (More / Accept all / Confirm and route) placed right-aligned at the bottom of the Review AI suggestions card with a reviewed-count progress pill in its header. | Actions live next to the content they act on; no fixed bar overlays page content. |
| 2026-11-17 | "Reported by" row shows "Not stated (anonymous report)" for anonymous or unnamed reporters; empty table cells show italic muted "Not provided" instead of an em dash. | Consistent, explicit empty states. |
| 2026-11-17 | Long resident/AI descriptions clamp to 2 lines with a bold View More/View Less toggle rendered only when text is actually truncated; diff highlighting is preserved in both states; Final textarea has fixed height with its own scroll. | Keeps table rows uniform without hiding content behind a modal. |
| 2026-11-17 | Review location map gets a stacking context (relative, z-index 0, isolation) with fixed height and overflow hidden; More menu opens downward. | Prevents Leaflet panes overlaying menus; works in normal document flow. |
| 2026-11-17 | Review page actions split per step: step 2 footer has Ask Reporter for More Info (modal), Accept all, and Confirm review; step 3 footer has the review gate hint and Confirm and route next to the message to the resident. | Each step ends with its own confirmation; the resident message is no longer separated from the routing action. |
| 2026-11-17 | "Ask Reporter for More Info" opens a native <dialog> modal posting to the info-request endpoint; refer/duplicate/out-of-scope/escalate live in a compact "Other actions" list inside Route and assign. | One clear primary way to ask the reporter; override actions stay reachable without hiding them in a footer dropdown. |
| 2026-11-17 | phase8_foundation down() drops the assigned_to foreign key (dropConstrainedForeignId) before its index; full migrate:rollback now completes from a clean seed. | MySQL reuses the explicit index for the FK constraint, so dropping the index first fails with error 1553. |
| 2026-11-17 | DemoReportSeeder seeds four AI states: done/high confidence (0.92), done/low confidence (0.45, photo mismatch), pending, and failed with error. | Staff can exercise every review-page AI state from seeded data alone; no manual Gemini call needed. |
| 2026-11-18 | Duplicate and out-of-scope moved into card 2 (Review AI suggestions) as a "More actions" row with native dialogs; removed from card 3. | Each decision lives in one place; card 3 is only routing/referral. |
| 2026-11-18 | Card 3 (Route and assign) has two jurisdiction-based modes from routing_rules.within_authority: Mode A (within barangay: office, assignee, resident message, Confirm and route) and Mode B (beyond authority: external agency, editable referral letter, Prepare referral). | Staff see the rule's decision up front with a mode banner and jurisdiction note; override links switch modes. |
| 2026-11-18 | Jurisdiction override audit logs only when the chosen mode differs from the routing rule (jurisdiction_mode_override in audit_logs plus a status log). | Override-only audit keeps logs meaningful. |
| 2026-11-18 | Referrals are tracked by admin-logged updates with REF-YYYY-NNNN numbers, sent dates, follow-up dates (default sent_at + 7 days from settings), and statuses prepared/sent/acknowledged/action_taken/closed; agencies have no accounts. | Referral tracking runs inside the barangay office, not the agency. |
| 2026-11-18 | Closing a referral as resolved transitions the report to resolved through ReportStatusService; declined/no_response keeps the report open for the next action; referred reports may be re-routed internally. | The report stays monitored by the barangay until someone acts. |
| 2026-11-18 | Proof photos at Work/Resolve allow multiple images (max 5, 5 MB each, jpeg/png/webp) stored in report_proof_photos with thumbnail previews, per-file removal before submit, deletion of saved photos, and limits shared from config/reports.php between client and server. | One photo was not enough to document a fix; legacy single-column proofs are migrated and kept. |
| 2026-11-18 | Referral updates carry visible_to_resident (default true); the resident tracking page shows only agency name, status, dates and visible notes, never staff names, emails or letter text, and emails the resident on send/update when an email exists. | Privacy-first per project.md; residents stay informed without exposing internals. |
| 2026-11-18 | Unified destination directory on `offices` with additive columns (`description`, `default_assignee_id`, `is_active`) and `type` (`internal`/`external`); admin CRUD supports deactivation and blocks deleting offices linked to reports or referrals. | Reuses the single `offices` table without breaking existing FKs, routing rules, or REF numbers. |
| 2026-11-18 | Referrals and routing page lists both internal barangay routed reports (derived directly from `reports` with SLA `due_at` and `assigned_to`) and external agency referrals (with `follow_up_owner_id` defaulting to `prepared_by`) with All / Within barangay / External agencies filters. | Gives staff one place to monitor all routed work and overdue follow-ups without duplicating referral records. |
| 2026-11-18 | Route and assign card filters for active internal offices (Mode A) and active external agencies (Mode B), renames "Priority: Unassigned" to "Urgency: not set", adds helper text under Office and Assigned to, and allows Unassigned without blocking Confirm and route. | Clarifies urgency vs. staff assignment and safely handles deactivated offices with open reports. |

---

## Detailed Architectural Decisions (Decisions 47–57)

### Decision 47: Documentation Authority Follows Requirements Blueprint
- **Status**: Accepted
- **Date**: 2026-11-19
- **Context**: Discrepancies and drift accumulated across `project.md`, `phases.md`, `AGENTS.md`, and `SKILL.md`.
- **Options**:
  1. Continue updating fragmented markdown files across repository root.
  2. Unify documentation under `docs/` using the approved requirements blueprint as the authoritative source of truth.
- **Decision**: Adopt the unified `docs/` suite as the sole authoritative specification. Any intentional departure from the blueprint must be logged as a formal decision here.
- **Rationale**: Eliminates contradictions, prevents token waste, and provides unambiguous guidance for coding agents.
- **Consequences**: Legacy root files (`project.md`, `phases.md`, `DECISIONS.md`) are replaced with pointers to `docs/`.

### Decision 48: Voice Call Reporting Deferred
- **Status**: Accepted (Partially Superseded)
- **Date**: 2026-11-19
- **Context**: Early academic project drafts referenced voice call reporting, softphone integration, call recording, and speech-to-text.
- **Options**:
  1. Maintain active planning and placeholder tables for voice call ingestion.
  2. Officially defer all voice and telephony features to future phases.
- **Decision**: Formally defer UR-04, FR-22, proposal feature 18 ("Report by Phone"), softphone integration, audio retention, speech-to-text, and SMS broadcasting.
- **Rationale**: The pilot deployment for Barangay Calarian focuses strictly on mobile web reporting, Leaflet geo-tagging, and Gemini text/photo triage.
- **Consequences**: AI agents will not generate or scaffold voice/SMS features until explicitly re-opened.

> [!NOTE]
> Partially superseded by Decision 77 on 2026-11-24 for the voice reporting prototype (record-and-transcribe without telephony). Real telephony, softphone integration, live call recording, SMS gateway broadcasting, and dialect-specific speech models remain deferred.

### Decision 49: Elimination of `on_hold` Status in Favor of Progress Notes
- **Status**: Accepted
- **Date**: 2026-11-19
- **Context**: `on_hold` was introduced for "waiting on materials/equipment" but lacked distinct resident UI, distorted SLA overdue counts, and was excluded from all dashboard KPI tiles.
- **Options**:
  1. Add dedicated dashboard KPI tiles, queue filters, and SLA pause logic for `on_hold`.
  2. Eliminate `on_hold` as a top-level lifecycle status; record "waiting on materials" as a progress update note under `in_progress`.
- **Decision**: Remove `on_hold` from the core status list. Represent waiting on materials as a progress update note under `in_progress`.
- **Rationale**: Avoids status explosion and keeps the report actively visible in operational queues without creating dead-end status states.
- **Consequences**: `on_hold` is removed from `ReportStatus.php`, `ReportStatusService.php`, and database rows are migrated to `in_progress`.

### Decision 50: Unification of `out_of_scope` and `closed` into `no_action`
- **Status**: Accepted
- **Date**: 2026-11-19
- **Context**: The codebase had overlapping non-actionable end states: `out_of_scope` (for jurisdictional or invalid issues) and `closed` (for unresolved active/referral issues).
- **Options**:
  1. Maintain both `out_of_scope` and `closed` with separate UI paths.
  2. Unify both into a single `no_action` status with mandatory standardized reason codes.
- **Decision**: Merge `out_of_scope` and `closed` into `no_action`. Require one of five reason codes (`not_barangay_matter`, `emergency_redirected`, `cannot_resolve`, `invalid_report`, `resident_unreachable`) and a free-text explanation.
- **Rationale**: Streamlines the state machine, provides structured data on why cases cannot proceed, and gives residents a consistent explanation.
- **Consequences**: Existing records in `reports` and `status_logs` will be migrated; UI modals will collect the reason code. Staff may reopen or resume cases via `reopened` or `in_progress`.

### Decision 51: Emergency Incident End State Protocol
- **Status**: Accepted
- **Date**: 2026-11-19
- **Context**: The system is not a dispatch system and does not directly alert the Philippine National Police or Bureau of Fire Protection.
- **Options**:
  1. Allow emergency reports to follow normal routing.
  2. Standardize emergency exit to `no_action` (`reason: emergency_redirected`) while permitting staff-only transition to `in_progress` if Tanods respond directly.
- **Decision**: Standardize emergency handling: default exit is `no_action` with reason `emergency_redirected`. If Barangay Tanods handle the incident locally on-site, authorized staff may move `emergency_flagged -> in_progress` to document work to resolution.
- **Rationale**: Protects citizen life safety by guiding them to 911 while providing flexibility for barangay peacekeeping units.
- **Consequences**: Documented in state machine; UI provides clear 911 hotline notices.

### Decision 52: Multilingual Text Support Scope
- **Status**: Accepted
- **Date**: 2026-11-19
- **Context**: Residents of Barangay Calarian communicate in English, Filipino (Tagalog), Bisaya (Cebuano), and Chavacano (Zamboangueño Creole).
- **Options**:
  1. Restrict report text strictly to English and Filipino.
  2. Support text understanding across English, Filipino, Bisaya, and Chavacano via Gemini LLM parsing, with UI toggle in English and Filipino.
- **Decision**: Support resident incident submissions in English, Filipino, Bisaya, and Chavacano. UI form labels remain English and Filipino.
- **Rationale**: Gemini LLM handles regional Philippine languages effectively without requiring heavy localized UI translation catalogs.
- **Consequences**: Gemini prompts are configured to process mixed Taglish/Chavacano/Bisaya descriptions; ambiguous cases are marked `unclassified` for manual review.

### Decision 53: Canonical Status Definition in `docs/WORKFLOW.md` Only
- **Status**: Accepted
- **Date**: 2026-11-19
- **Context**: Different files (`phases.md`, `project.md`, `SKILL.md`) previously defined divergent status sets.
- **Options**:
  1. Document statuses across multiple architectural and phase files.
  2. Restrict the authoritative definition of statuses and allowed transitions to a single document.
- **Decision**: Designate `docs/WORKFLOW.md` as the sole canonical source of truth for all lifecycle statuses and transitions.
- **Rationale**: Prevents specification drift and guarantees consistency across models, seeders, and views.
- **Consequences**: AGENTS.md instructs all agents to verify and update `docs/WORKFLOW.md` first before modifying status constants.

### Decision 54: Deprecation of Root `SKILL.md`
- **Status**: Accepted
- **Date**: 2026-11-19
- **Context**: Root `SKILL.md` contained obsolete specifications (resident registration/login, 5-status lifecycle, absence of AI triage).
- **Options**:
  1. Attempt to maintain `SKILL.md` in parallel with project documentation.
  2. Mark `SKILL.md` as obsolete and replace its contents with a short pointer to `AGENTS.md` and `docs/PROJECT.md`.
- **Decision**: Retain `SKILL.md` in the repository marked as OBSOLETE with pointers to `AGENTS.md` and `docs/PROJECT.md`.
- **Rationale**: Prevents accidental reliance on outdated guidance while preserving file structure for CLI skill discovery mechanisms.
- **Consequences**: Agents will ignore legacy text in `SKILL.md` and use the canonical `docs/` suite.

### Decision 55: Staff-Only Recovery Transition `merged -> under_review`
- **Status**: Accepted
- **Date**: 2026-11-19
- **Context**: `merged` was strictly terminal, leaving no recovery mechanism if staff accidentally merged an incident or subsequent investigation revealed it was distinct.
- **Options**:
  1. Keep `merged` strictly terminal; require manual database intervention for unmerging.
  2. Allow an authorized staff-only recovery transition `merged -> under_review` with mandatory explanation and security audit logging.
- **Decision**: Add staff-only recovery transition `merged -> under_review`. Require a mandatory explanatory note and an audit log entry in `audit_logs`.
- **Rationale**: Provides operational resilience against accidental duplicate merges without weakening duplicate integrity.
- **Consequences**: Enforced via role checks in controller/policy; unlinking clears `reports.merged_into_id`.

### Decision 56: Self-Transitions Kept for Progress Notes
- **Status**: Accepted
- **Date**: 2026-11-19
- **Context**: Staff need to record milestone progress updates (e.g. "Work started", "Inspection scheduled", "Waiting on materials") or additional clarification inquiries without changing the core status.
- **Options**:
  1. Disallow self-transitions in the state machine; build a separate progress update mechanism bypassing status transitions.
  2. Retain self-transitions (`in_progress -> in_progress`, `needs_info -> needs_info`) in the state machine, defining them as progress-note events that record to `status_logs` without mutating `reports.status`.
- **Decision**: Retain self-transitions in `ReportStatusService::ALLOWED`. Explicitly document that a self-transition appends a `status_logs` row and note but does not alter `reports.status`.
- **Rationale**: Reuses the transactional `status_logs` pipeline for all milestone events, keeping status history unified.
- **Consequences**: `ReportStatusService::transition()` handles identical from/to statuses by logging the note and dispatching events without invalid state change exceptions.

### Decision 57: Dual Storage of `no_action_reason` in Reports Column and Status Logs
- **Status**: Accepted
- **Date**: 2026-11-19
- **Context**: When a report transitions to `no_action`, the system requires both immediate query filtering by reason and a complete historical audit trail.
- **Options**:
  1. Store the reason code solely as text/JSON within `status_logs.note`.
  2. Store the reason code solely in an indexed column on `reports`.
  3. Store `no_action_reason` in BOTH a dedicated indexed column on `reports` and in the corresponding `status_logs` entry.
- **Decision**: Adopt dual storage: add `reports.no_action_reason` (indexed nullable string) and `status_logs.no_action_reason` (nullable string).
- **Rationale**: Enables fast SQL filtering, queue grouping, and CSV export without string parsing, while preserving an immutable history if the report is reopened and subsequently re-closed with a different reason.
- **Consequences**: Migrations and `ReportStatusService::transition()` will accept and populate `no_action_reason` across both tables.


---

## Revision 2 Decisions & Architectural Changes (2026-11-22)

### Decision 58 (D1): Lupon Members Receive System Accounts
- **Status**: Accepted
- **Date**: 2026-11-22
- **Context**: The Barangay Secretary clarified in Question 15 that both the Barangay Secretary and Lupon members record walk-in complaints at the barangay hall.
- **Decision**: Grant Lupon members system logins under position `lupon_member` and permission preset `case_encoder`.
- **Rationale**: Enables direct digital intake of disputes and recording of service results without administrative bottleneck.
- **Consequences**: Preset grants `cases.view`, `cases.create`, `cases.edit`, `cases.summons`, and `dashboard.view`. Lupon members do not receive `cases.view_confidential` unless they were the recording encoder.

### Decision 59 (D2): Historical Excel/CSV File Import Included as Stretch Workstream (WS6)
- **Status**: Accepted
- **Date**: 2026-11-22
- **Context**: The barangay currently maintains past records in logbooks, Excel/Word documents, and a legacy computer program across the last 4 record years (Question 1, 8).
- **Decision**: Introduce Workstream 6 (WS6, stretch) to provide a bulk import wizard for Excel and CSV files.
- **Rationale**: Prevents data loss during migration to the new web system and meets the Secretary's priority to search past cases.
- **Consequences**: Strictly no AI or OCR on uploaded files (protects party PII). Blank downloadable template provided; chunked commit with complete batch rollback capability.

### Decision 60 (D3): Foundation Documentation Reconciled to Built Phase 0 Implementation
- **Status**: Accepted
- **Date**: 2026-11-22
- **Context**: Phase 0 completed with 22 tables, 21 models, and 244 registered routes, differing from early planning sketches.
- **Decision**: Update all documentation to reflect the actual verified code, database schema, and test suite.

### Decision 61 (D4): Official Blank Forms Collection Protocol
- **Status**: Accepted
- **Date**: 2026-11-22
- **Context**: The Secretary offered official physical forms (complaint, summons, settlement, CFA, DILG reports).
- **Decision**: All draft printables and response templates display `<x-ui.draft-watermark>` and unconfirmed badges until physical forms are collected and values confirmed.

### Decision 62 (D5): Case Module Scope Kept Switchable via Feature Flag
- **Status**: Accepted
- **Date**: 2026-11-22
- **Context**: Clarification with the academic adviser regarding whether disputes and conciliation are required alongside public reporting.
- **Decision**: Keep `cases.enabled` as the master configuration switch.
- **Rationale**: Secretary answers (Q15–Q27) prove disputes are central to barangay daily operations, while the switch protects thesis scope if needed.

### Decision 63 (C1): Working-Day Countdown Replaces Artificial 3-Summons Limit
- **Status**: Accepted
- **Date**: 2026-11-22
- **Context**: The Secretary clarified that summonses are not limited to 3; rather, mediation is bounded by 15 working days (Q20, Q25).
- **Decision**: Remove the 3-summons cap and override note. Implement working-day tracking via `App\Support\WorkingDays` and `calendar.holidays`.
- **Rationale**: Aligns system behavior with Katarungang Pambarangay administrative practice while maintaining advisory deadlines.

### Decision 64 (C2): Complainant Escalation Authority and Pangkat Chairman CFA Signatory
- **Status**: Accepted
- **Date**: 2026-11-22
- **Context**: The complainant decides whether and when to escalate upon impasse (Q22, Q23), and the Pangkat Chairman signs the Certificate to File Action (Q5).
- **Decision**: Escalation requires selecting the requesting complainant. System shows a soft warning if 15 working days have not elapsed, but does not block. CFA printable resolves Pangkat Chairman signatory.

### Decision 65 (C3): Two Distinct Case Record Types: Blotter-Only vs Formal Complaint
- **Status**: Accepted
- **Date**: 2026-11-22
- **Context**: Walk-ins include both informational/record-only blotter entries and formal disputes requiring conciliation (Q18).
- **Decision**: Add `cases.record_type` (`blotter` or `complaint`). Blotter entries stay at `filed` without summons, hearings, or deadlines, and can be converted to formal complaints.

### Decision 66 (C4): Case Docket Numbering Format `{MM}-{SEQ:3}-{YEAR}`
- **Status**: Accepted
- **Date**: 2026-11-22
- **Context**: Secretary confirmed case numbers follow `month-case number (001)-year` and restart each year (Q9).
- **Decision**: Add `{MM}` token to `NumberingService`. Format defaults to `{MM}-{SEQ:3}-{YEAR}` (e.g. `11-001-2026`).

### Decision 67 (C5): External Referral Follow-Up Interval Set to 3 Days
- **Status**: Accepted
- **Date**: 2026-11-22
- **Context**: Secretary stated external agency follow-ups occur after 3 days (Q12).
- **Decision**: Update `referral.follow_up_days` default from 7 to 3 days in dynamic settings and `config/reports.php`.

### Decision 68 (C6): Sensitive Case Types Pre-Tick Confidentiality
- **Status**: Accepted
- **Date**: 2026-11-22
- **Context**: BCPC (child protection) and VAWC cases require restricted access (Q7, Q19).
- **Decision**: Add `case_types.is_confidential_default`. When selected during intake, the case automatically pre-ticks `is_confidential = true`.

### Decision 69 (C7): Escalated Cases Dispatched to Agencies as Referrals with Attachments
- **Status**: Accepted
- **Date**: 2026-11-22
- **Context**: Partner agencies (PNP, CSWDO) need to receive case documents upon endorsement (Q29).
- **Decision**: Agencies access only the referral portal. Escalated cases generate an external referral carrying downloadable case documents stored in `referral_attachments` on the private disk.

### Decision 70 (C8): Alignment of Reports Center with Mandatory DILG Submissions
- **Status**: Accepted
- **Date**: 2026-11-22
- **Context**: Secretary identified specific mandatory reports: yearly KP Accomplishment to DILG, monthly summary by case type to DILG, and problem frequency by purok (Q32, Q33, Q34).
- **Decision**: Prioritize `kp_accomplishment` (yearly), `case_summary` (monthly), and `purok_category_matrix` with paper print layout as the default action (`reports.default_format = print`).

### Decision 71 (C9): Trimmed Party Intake Form
- **Status**: Accepted
- **Date**: 2026-11-22
- **Context**: Secretary listed essential party information (full name, alias, age/birth date, contact, complete address, residency verification) while other fields are rarely collected (Q16, Q17).
- **Decision**: Streamline party form to essential fields. Store secondary attributes (age, reason for visit) in `parties.extra` JSON.

### Decision 72 (C10): System Backups and Cross-Year Global Search Guaranteed Deliverables
- **Status**: Accepted
- **Date**: 2026-11-22
- **Context**: Finding old records and preventing data loss were identified by the Secretary as paramount operational priorities (Q2, Q34).
- **Decision**: Elevate automated daily backups (`app:backup`) and multi-year global search (`admin.search`) to guaranteed core deliverables in WS1.

---

## Users, Roles, Positions & Seeders Rework Decisions (2026-11-24)

### Decision 73: Canonical User Roles Restricted to Admin, Staff, and Agency Only
- **Status**: Accepted
- **Date**: 2026-11-24
- **Context**: Previously, `role` was ambiguously used for access tiers, positions, and permissions. The legacy seeders created users with `role = 'personnel'`, which was not an access tier defined in `docs/PROJECT.md`.
- **Decision**: Restrict `users.role` strictly to `admin`, `staff`, and `agency` using `App\Support\UserRole`. Deprecate `personnel` as a role and treat it purely as an assignable directory model (`Personnel`).
- **Rationale**: Eliminates privilege escalation ambiguities, simplifies role middleware (`role:admin`, `role:staff`, `role:agency`), and aligns code with the project specification.
- **Consequences**: Data migration `2026_12_02_000001_migrate_personnel_role_to_staff` converts legacy personnel users to staff with `reports_center.view`.

### Decision 74: Single Source of Truth for User Position and Agency Office
- **Status**: Accepted
- **Date**: 2026-11-24
- **Context**: Linking positions directly to `users` via a redundant `users.position_id` column would introduce dual-source-of-truth discrepancies with the existing `personnel` table (`personnel.user_id` and `personnel.position_id`).
- **Decision**: Implement `User::position()` as a `HasOneThrough` relationship through `Personnel`. Enforce `users.office_id` strictly for `agency` users (linking to an external office with `is_external = true`), keeping it `null` for `admin` and `staff`.
- **Rationale**: Preserves architectural purity and ensures that user access permissions remain governed strictly by role + preset, independent of position title.
- **Consequences**: Barangay positions do not grant permissions by themselves. Access is determined by role + permission preset (`staff_full`, `case_encoder`, `case_reviewer`, `reports_analyst`).

### Decision 75: Production Reference Data Isolated from Demo Dataset
- **Status**: Accepted
- **Date**: 2026-11-24
- **Context**: Running `DatabaseSeeder` previously executed demo seeders, creating dummy users and mock reports in environments intended for clean setup. Redundant seeders (`AdminSeeder`, `EngineSeeder`, `UserSeeder`, `StaffSeeder`, `ResidentSeeder`) clobbered admin modifications.
- **Decision**: Streamline `DatabaseSeeder` to execute strictly `RequiredDataSeeder` (idempotent reference data: Puroks 1–7, Categories, Offices, Routing Rules, Emergency Contacts, Response Templates, Settings placeholders, Priority Weights, Case Types, Escalation Destinations). Move all demo activity, demo accounts, and case sequences into `DemoDataSeeder` and `DemoReportSeeder`. Use `firstOrCreate` across reference seeders to prevent overwriting admin edits.
- **Rationale**: Guarantees that `php artisan app:empty` and production database seeds result in exactly 0 users, 0 reports, and 0 cases, while `php artisan app:demo` seeds a fully hydrated demo environment.

### Decision 76: Removal of Deprecated Root `SKILL.md`
- **Status**: Accepted
- **Date**: 2026-11-24
- **Context**: The root `SKILL.md` was deprecated in Decision 54 as an obsolete stub pointing to `AGENTS.md`. During the documentation cleanup, maintaining an empty/stub skill file at root introduced clutter and potential confusion with genuine project skills (such as `.agents/skills/ui-ux-pro-max`).
- **Decision**: Remove the root `SKILL.md` completely.
- **Rationale**: `AGENTS.md` is the sole, authoritative execution manual for AI coding agents in this repository. Removing the redundant stub eliminates ambiguity.
- **Consequences**: Agents operating in the repository reference `AGENTS.md` and the canonical files in `docs/`.

---

## Voice Reporting Prototype Decisions (2026-11-24)

### Decision 77: Voice Reporting Prototype Reopens Decision 48 (Record-and-Transcribe Only)
- **Status**: Accepted (Planned Prototype)
- **Date**: 2026-11-24
- **Context**: Resident accessibility is limited if typing is the only intake method, and desk officers manually taking phone calls at the barangay hotline lack a structured voice-intake tool. Decision 48 previously deferred all voice and telephony features.
- **Options**:
  1. *Keep everything deferred*: Continue with mobile web text-only intake; leave phone calls unrecorded.
  2. *UI-only mock*: Add visual recording controls with no transcription or backend audio pipeline.
  3. *Working prototype with record-then-transcribe (chosen)*: Add an optional "Record instead of typing" recorder (with audio file upload fallback) in the resident form and a "Record phone report" desk officer form. Save audio on the private disk, transcribe via Gemini audio input, insert text into the description field for review before submission, and run existing text analysis on submit.
  4. *Real telephony / softphone (rejected)*: Integrate SIP/Twilio telephony and live hotline softphones (rejected due to infrastructure costs, complexity, and network dependency).
  5. *Live streaming transcription (rejected)*: Real-time audio streaming transcription during speech (rejected due to dialect accuracy risks, privacy constraints, and network instability).
- **Decision**: Adopt Option 3: implement a voice reporting prototype restricted strictly to asynchronous record-and-transcribe without telephony. Reopen Decision 48 solely for this scope.
- **Rationale**: Provides immediate spoken-language accessibility for residents and staff while keeping infrastructure zero-cost and lightweight. Telephony and SMS remain deferred.
- **Consequences**: AI agents may build ONLY the prototype scope (resident audio record/upload, desk officer phone intake, Gemini transcription, private disk storage, audio retention purge, mock mode). Real telephony, softphone integration, live phone call recording, SMS gateway broadcasting, dialect-specific speech models, and real-time streaming transcription remain strictly deferred.

### Decision 78: Uncertain or Failed Audio Goes to Staff Verification
- **Status**: Accepted (Planned Prototype)
- **Date**: 2026-11-24
- **Context**: Spoken language in Barangay Calarian includes English, Filipino, Bisaya, and Chavacano. Code-switching, acoustic noise, phone artifacts, and regional place names create transcription uncertainty. Without a strict protocol, the system risks hallucinating incorrect incident details or blocking citizens.
- **Options**:
  1. *Reject uncertain recordings*: Require the user to re-record until confidence is high (rejected: frustrates residents and blocks reporting).
  2. *Best-effort auto-fill*: Insert low-confidence AI guesses into the report description (rejected: violates "the AI suggests, the officials decide" and risks acting on hallucinations).
  3. *Staff verification gate with unverified draft (chosen)*: When `transcript_status` is `needs_verification` or `failed`, never insert guessed text as the report description; accept the report into `submitted` with a system placeholder, flag it for staff review, and block "Confirm and route" until an authorized staff member listens to the audio and confirms or enters what was said.
- **Decision**: Adopt Option 3. Mandate the core rule: **If the audio is uncertain or the AI cannot understand it, nothing is guessed and the decision goes to staff.**
- **Rationale**: Preserves the human-in-the-loop review mandate. Extends the existing review gate without creating new statuses, complex sub-states, or automatic routing.
- **Consequences**: Unverified voice reports are never auto-routed or priority-downgraded. Category and urgency remain human decisions in the 3-way triage. The resident's original description is preserved, and staff corrections are recorded separately with audit timestamps.

### Decision 79: Voice Reports: AI Extracts Form Fields, Including Spoken Caller Contact, from Audio
- **Status**: Accepted
- **Date**: 2026-11-24
- **Context**: Initially, voice reporting was implemented as a form filled manually plus an optional audio recording that only transcribed into the description box. Both residents and hotline desk officers benefit from a "record first, AI fills the form" paradigm where speaking naturally extracts incident details into the form while preserving user review and verification.
- **Options**:
  1. *Keep voice as description-only transcription*: Maintain the recorder below manual fields and only transcribe verbatim text into the description field.
  2. *Full automated intake without review*: Have AI directly create and submit the report from audio (rejected: violates "The AI suggests, the rules route, the officials decide").
  3. *Record-first with structured field extraction and human verification (chosen)*: Place the voice recorder at the top as Step 1. A single Gemini call analyzes the audio and extracts verbatim transcript plus structured candidate fields (`caller_name`, `caller_phone`, `caller_email`, `purok`, `landmark`, `category`, `urgency`, `emergency`, `not_stated`). Pre-fill the form with "From recording" badges while keeping every field editable and showing "Not provided" for unstated items.
- **Decision**: Adopt Option 3. Rework voice reporting across resident intake and staff hotline intake to "record first, AI fills the form".
- **Amendment to PII Isolation Rule (Voice Only)**:
  - This decision formally AMENDS the strict PII isolation rule solely for voice intake: typed reporter fields entered into form inputs are still NEVER sent to external AI APIs. However, spoken audio recordings are sent to Google Gemini under explicit user consent to extract spoken contact information and incident details.
  - References existing uncertain-audio decision (**Decision 78**): if audio is unclear (`clarity = 'unclear'`), empty, or invalid JSON, nothing is guessed (`status = 'needs_verification'`), all fields remain "Not provided", and forward routing remains blocked until staff listen and verify.
  - Strict PII safeguards and operational rules for voice:
    1. *Consent Text and Recorded Consent*: Staff recorder is locked until the mandatory caller verbal consent checkbox is ticked (shown with a short verbal script read aloud); resident recorder displays an explicit consent notice directly above the record button. Consent timestamp is saved in `consent_at` and staff user ID is saved in `consent_recorded_by`.
    2. *Staff-Only Transcript*: Raw `transcript` is staff-only, stored in `report_audios.transcript`, and returned ONLY by the staff analyze endpoint (`/admin/reports/phone/analyze`). The resident endpoint (`/report/voice/analyze`) never returns the raw transcript.
    3. *PII-Free Description for the Form*: The model returns both `transcript` (verbatim speech) AND `description` (the civic problem statement in speaker's language with caller name, phone number, email, and personal contact details completely REMOVED). The form description textarea receives `description`, never the raw transcript.
    4. *No Downstream PII Reuse*: Extracted contact details and raw transcripts are never re-sent to Gemini in downstream text classification, formal executive summary prompts, or duplicate detection. Spoken PII stays out of public views and the anonymous flow.
    5. *Server-Side Anonymous Drop*: If the resident toggles anonymous mode or the speaker explicitly requests anonymity (`wants_anonymous`), `caller_name`, `caller_phone`, and `caller_email` are dropped on the server and never stored. `wants_anonymous` is returned as a hint only.
    6. *Zero PII Logging*: Transcripts and extracted contact details are never logged in application logs or audit logs (log IDs and statuses only).
    7. *Settings Governance*:
       - `voice.extract_contact_from_audio` (default `true`; applies to STAFF channel).
       - `voice.resident_extract_contact` (default `true`, amended in Decision 80; residents may have contact extracted unless anonymous mode is on).
       - `voice.resident_recording_enabled` (default `true`; when false, resident recorder is hidden and form is manual only).
       - `voice.daily_analyze_limit` (integer; when reached, analyze endpoints return a friendly manual entry fallback with no Gemini call).
       - `voice.audio_retention_days` (default `90` days, counted from report closure for attached audio; unattached audio keeps the 24-hour expiry).
       > [!NOTE]
       > **Honest Documentation Note**: Turning contact extraction off stops contact details from being extracted or pre-filled in the form, but the audio file is still sent to Gemini for transcription and incident analysis, so the speech model still hears anything spoken in the recording.
    8. *Abuse Limits & Throttling*: Resident analyze endpoint enforces per-IP throttling (3 requests per 10 minutes), maximum duration of 120 seconds, maximum file size of 10 MB, and server-side real MIME type verification (rejects non-audio files with HTTP 422).
    9. *Human Governance*: Every extracted field is labeled "From recording", fully editable, and unstated fields display "Not provided". The AI outputs remain advisory until manually submitted by the user.

---

### Decision 80: Resident Voice Note Fills Resident Report Form (Phase C)
- **Status**: Accepted
- **Date**: 2026-11-24
- **Context**: Phase A delivered the shared multimodal voice analyze backend, and Phase B delivered the staff "Log phone report" intake UI. Phase C connects resident voice recording directly to form field pre-filling ("record first, AI fills the form") on the public reporting interface (`GET /`).
- **Decisions & Formal Amendments**:
  1. *Category Pre-Selection Amendment*: Formally amends the general "no pre-selected category" rule solely for voice intake: when a resident records a voice note, if the speaker explicitly and clearly describes an active problem type, the matching category chip is pre-selected as an editable suggestion marked "From recording", and can be deselected by the citizen. If unclear or unstated, no category is selected.
  2. *Optional Category on Resident Form*: The category selection on the resident form is made explicitly optional ("Choose a category (optional)"). Database columns `reports.category_id` and `reports.resident_category_id` were already nullable; validation rules now enforce `nullable` instead of `required`. Unclassified reports trigger the downstream text classification AI pipeline on submit, and staff confirm the final category. On the admin review page, unclassified reports display "Not chosen by resident". Forward routing remains gated until staff confirm a category and urgency.
  3. *Contact Extraction Default Amendment*: Amends `voice.resident_extract_contact` default to `true` (still `is_confirmed = false` with `<x-ui.pending-badge>`). Spoken caller name, phone number, and email populate the collapsed contact sections and auto-expand them. Strict anonymity wins: if anonymous mode is toggled, contact details are dropped on the server and cleared in the UI.
  4. *Spoken PII Isolation Maintained*: The raw transcript is server-side only (`report_audios.transcript`, staff-only) and is never returned to the resident endpoint. The incident description textarea receives only the sanitized PII-free `description`. Downstream text analysis and duplicate detection continue receiving only the final description submitted by the citizen, never the raw transcript.
  5. *Audio Provenance & Audit Trail*: `report_audios` stores `extracted_data` (non-contact extraction for unattached audio), `ai_description`, and `provenance` JSON (recording field flags and `edited` booleans). Contact values are never stored in provenance JSON. The admin review page displays "From recording" or "Edited after recording" chips and an "Edited by resident" marker.
  6. *Uncertain Audio Safeguards*: Reaffirms **Decision 78**: unclear, failed, or invalid JSON recordings fill no fields, display a friendly message, and prompt manual entry. Reports submitted without a description require staff verification before routing.
- **Consequences**: Resident report completion is accelerated; spoken details populate fields with visual provenance; unclassified cases are handled safely; privacy and human governance are preserved.
