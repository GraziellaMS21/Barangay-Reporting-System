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
- **Status**: Accepted
- **Date**: 2026-11-19
- **Context**: Early academic project drafts referenced voice call reporting, softphone integration, call recording, and speech-to-text.
- **Options**:
  1. Maintain active planning and placeholder tables for voice call ingestion.
  2. Officially defer all voice and telephony features to future phases.
- **Decision**: Formally defer UR-04, FR-22, proposal feature 18 ("Report by Phone"), softphone integration, audio retention, speech-to-text, and SMS broadcasting.
- **Rationale**: The pilot deployment for Barangay Calarian focuses strictly on mobile web reporting, Leaflet geo-tagging, and Gemini text/photo triage.
- **Consequences**: AI agents will not generate or scaffold voice/SMS features until explicitly re-opened.

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
