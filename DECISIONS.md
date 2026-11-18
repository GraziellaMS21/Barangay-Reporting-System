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
