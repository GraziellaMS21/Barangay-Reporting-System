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
| 2026-11-17 | Bulk confirm shown as sticky bar after selection. | Prevents accidental bulk action. || 2026-11-17 | Shared form pattern with stacked labels, regular-weight values, one action card by status, merged cards into tabs, required category and urgency before routing, removal of duplicate recommended-office displays. | Review page redesign. |
