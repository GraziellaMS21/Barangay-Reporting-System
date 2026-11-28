# Task: Reconcile Existing Project Documentation with the Expanded Master Specification

## Objective

You are working on the **Barangay Problem Reporting and Management System** (Laravel, Blade, Leaflet, Gemini).

The authoritative project specification is:

`BARANGAY_WORKFLOW_MASTER_SPEC_EXPANDED.md`

This document is the source of truth for intended workflows, terminology, requirements, task ownership, dependencies, implementation scope, and roadmap status.

Your task is to inspect the existing project documentation, identify inconsistencies, and propose a controlled documentation update.

**This is a documentation reconciliation task, not an application implementation task.**

---

## 1. Authority and interpretation rules

1. The expanded master specification defines intended system behavior.
2. The application code defines what currently exists.
3. Existing documentation describes previous decisions, implementation history, and recorded findings. It must not override the expanded specification when describing intended behavior.
4. If the specification and actual implementation disagree, record the discrepancy. Do not silently change either one.
5. `[AUDIT FIRST]` items must be verified against the actual codebase before proposing implementation changes.
6. `[CONFIRM WITH BARANGAY]` items concern legal, administrative, or procedural decisions. Do not automate them or present them as approved rules.
7. Never claim a feature exists without inspecting its implementation.
8. Follow the current task statuses, dependencies, ownership assignments, and approval gates in the expanded specification.
9. Do not invent new TR tasks, renumber existing tasks, or change the approved member allocations.
10. Do not explicitly require any particular AI coding assistant, CLI agent, IDE, or development environment. Instructions must remain tool-independent.

**Important current roadmap position:**

- TR1: Complete.
- TR2: Prototype complete; official GIS data remains pending.
- TR3: Reopened for re-audit, corrections, and full re-verification.
- TR3.1-R1 is the immediate next implementation-roadmap task.
- TR3.8-R1 is the required TR3 re-verification task.
- TR4 must not advance beyond permitted preservation, read-only review, or safe preparation until the user approves the TR3 redo exit report.
- TR4.1 and existing TR4.2 work must be preserved and reconciled after TR3 approval.
- TR5–TR9 must follow the dependencies and approval gates in the expanded specification.

Do not revert to the older TR3.1-R0 immediate-next-step instruction.

---

## 2. Hard restrictions

For this task:

- Do not modify application code, views, controllers, services, routes, tests, migrations, seeders, or database records.
- Do not install packages or change application configuration.
- Do not commit or push.
- Do not reset, clean, stash, revert, or overwrite existing work.
- Preserve all current uncommitted changes, including any remaining TR2.4.6-FIX or later TR3/TR4 work.
- Documentation files are the only files eligible for modification.
- Do not overwrite the expanded master specification or change its roadmap decisions without explicit approval.

First inspect the Git branch, working tree, and existing changes. Record the baseline before doing anything else.

---

## 3. Step 1 — Locate and verify the master specification

Locate `BARANGAY_WORKFLOW_MASTER_SPEC_EXPANDED.md`.

Determine whether it is already present in the project's documentation directory.

If it is missing:
- Identify the existing documentation directory.
- Report the missing file and proposed destination.
- Do not create or install another copy until the user approves.

If multiple versions exist:
- Identify their paths and differences.
- Do not assume that the oldest or newest file is authoritative.
- Use the expanded specification as the intended authority only after confirming that the correct version is available.

---

## 4. Step 2 — Inventory existing documentation

Inspect the documentation directory and relevant documentation files throughout the project.

Include, where present:

- README and project overview
- Master roadmap and implementation plans
- TR1 and TR2 audit reports
- TR3 staff workflow audit and implementation reports
- TR4 workflow and implementation documentation
- AI architecture and integration notes
- GIS, boundary, and Purok documentation
- Resident workflow documentation
- Admin and staff workflow documentation
- Referral and agency documentation
- Roles and permissions documentation
- Database and ERD documentation
- Test plans and regression checklists
- Changelog and current-state documentation

For each document, record:

| Field | Description |
|---|---|
| File path | Exact relative path |
| Topic | What the document covers |
| Current status | Current, historical, outdated, or uncertain |
| Specification alignment | Aligned, partially aligned, conflicting, or not covered |
| Proposed action | Keep, update, annotate as historical, or defer |

Do not modify files during this step.

---

## 5. Step 3 — Prepare the discrepancy and conflict report

Compare the documentation against the expanded specification.

Pay particular attention to:

### Terminology and workflow boundaries
- Resident-facing terminology must use **Report a Concern**.
- Internal route, table, model, controller, and field names must not be renamed merely to match resident-facing terminology.
- **Concern Intake (TR3)** and **Concern Management (TR4)** are separate workflows.

### Roadmap and task status
- Preserve the TR1–TR9 structure and existing task identifiers.
- TR2 is resident-side only.
- Staff geographic verification belongs to TR3.4.
- TR3 is reopened and requires TR3.1-R1 through TR3.8-R1.
- Do not describe TR3 as complete until its redo exit criteria are accepted.
- Preserve the continuous four-member allocation:
  - Member 1: TR3
  - Member 2: TR4–TR5
  - Member 3: TR6–TR7
  - Member 4: TR8–TR9

### Formal records
- Blotter, Complaint, and Barangay Case are distinct but potentially linked records.
- None should be created automatically from a general concern submission.
- Existing implementation must be audited before proposing new functionality.
- AI must remain advisory and must not independently accept complaints or create official records.

### AI architecture and privacy
- Distinguish pre-submission AI analysis from post-submission queued processing.
- Contact details must not be sent to Gemini.
- Preserve the established privacy and AI failure safeguards.

### Geographic information
- Clearly distinguish provisional geographic data from official Barangay Calarian data.
- Do not describe synthetic or provisional Purok boundaries as official.
- Preserve the documented GIS dependencies and external-data requirements.

### Existing workflows and safeguards
- Preserve the original resident-submitted description and location.
- Staff corrections must be traceable.
- Preserve duplicate-review and emergency-report safeguards.
- Preserve existing referral behavior until its lifecycle and notification rules are verified.

Present the discrepancies in this format:

| Document | Section | Existing statement | Specification requirement | Proposed change |
|---|---|---|---|---|

Separate confirmed conflicts from uncertain findings.

**Stop after presenting the inventory and discrepancy report. Wait for the user's approval before modifying documentation.**

---

## 6. Step 4 — Update documentation after approval

After approval, update only the documentation files included in the approved change list.

Requirements:

1. Preserve historical audit findings, but label superseded findings as historical.
2. Keep the TR1–TR9 structure and task identifiers unchanged.
3. Use the expanded specification's task statuses and dependencies.
4. Do not mark planned, proposed, or externally blocked work as complete.
5. Keep implementation evidence separate from intended requirements.
6. Avoid duplicating the entire master specification across multiple documents.
7. Link related documentation where appropriate.
8. Update `CURRENT-STATE.md` to reflect the verified project state.
9. Update `PROJECT.md` and `API.md` only when their documented behavior is affected.
10. Add a changelog entry describing what changed and why.

For each workflow document, retain the applicable structure:

- Purpose
- Required information
- Steps
- Flow
- Frontend expectations
- Backend notes
- Rules and safeguards
- Acceptance criteria

Do not fabricate missing implementation details to make a document appear complete.

---

## 7. Step 5 — Verify documentation claims against the codebase

This is a read-only verification.

Verify the following claims when relevant to the documentation being reconciled:

1. Existing Blotter, Complaint, and Barangay Case functionality and their relationships with Community Reports.
2. Whether complaint intake is separate from formal case creation.
3. Referral lifecycle behavior, including parent-report status synchronization and resident notifications.
4. Existing statuses, transitions, roles, and permissions for reviewing, assigning, closing, and referring reports.
5. Existing location fields visible to staff and whether staff corrections are supported and logged.
6. Whether jurisdiction decisions and decision reasons are stored.
7. Existing admin navigation labels and routes.
8. Existing duplicate-review safeguards and emergency-report restrictions.
9. Existing AI endpoints, queued processing, and data sent to Gemini.
10. Existing TR3 implementation and the known regression caveats recorded in the current project state.

For each finding, use one of these labels:

- `Confirmed`
- `Refuted`
- `Partially confirmed`
- `Could not determine`
- `Not applicable`

Provide exact file paths and relevant method, class, or section names as evidence.

Do not implement fixes during this task.

**Important:** The TR3 verification here must not be presented as a substitute for the formal TR3.1-R1 audit or TR3.8-R1 regression verification.

---

## 8. Step 6 — Final verification

After documentation updates:

- Review every changed file.
- Check for contradictory task statuses and numbering.
- Check all references to TR3.1-R0 and the old TR3 completion status.
- Verify that the expanded specification remains unchanged unless separately approved.
- Check that historical findings are not presented as current behavior.
- Confirm that no application files were modified.
- Confirm that existing uncommitted work remains untouched.
- Do not claim that tests passed if no tests were run.

---

## 9. Required final report

Provide:

### A. Summary
What was inspected, changed, deferred, and not performed.

### B. Git baseline and safeguards
- Branch
- Initial working-tree state
- Existing changes identified
- Confirmation that no existing work was discarded or overwritten

### C. Documentation inventory
The complete inventory and alignment status.

### D. Discrepancy report
The conflicts found and the user's approved changes.

### E. Files changed
One line per modified file, including the reason for modification.

### F. Code verification findings
Confirmed, refuted, partially confirmed, could not determine, or not applicable, with evidence.

### G. Updated discrepancy log
List unresolved conflicts, external dependencies, and decisions requiring user or Barangay confirmation.

### H. Documentation checklist
Report each applicable checklist item as:
- Completed
- Partially completed
- Deferred
- Not applicable

### I. Final safeguards
Explicitly confirm:
- No application code was changed.
- No migrations, seeders, or database changes were made.
- No commits or pushes were made.
- No existing uncommitted work was discarded or overwritten.
- The expanded master specification was not silently changed.

---

## 10. Standing rules for subsequent implementation work

After this documentation task is accepted:

1. The next roadmap task is **TR3.1-R1 — Re-audit the current Staff Intake and Classification implementation**.
2. Follow the complete TR3 redo sequence and its exit criteria in the expanded master specification.
3. Correct only confirmed TR3 gaps.
4. Complete TR3.8-R1 re-verification with honest test results and documented caveats.
5. Do not advance dependent TR4 implementation until the user approves the TR3 redo exit report.
6. Preserve existing TR4.1 and TR4.2 work for reconciliation after TR3 approval.
7. Do not rebuild working modules or duplicate existing workflows.
8. Never overwrite original resident-submitted information with staff corrections.
9. Preserve duplicate-review, referral, emergency, privacy, and AI advisory safeguards.
10. If a requirement is ambiguous, a legal decision is unconfirmed, or the specification conflicts with implementation, document the issue and ask before proceeding.

**Begin with Steps 1–3 only. Do not modify any files until the user approves the discrepancy report.**