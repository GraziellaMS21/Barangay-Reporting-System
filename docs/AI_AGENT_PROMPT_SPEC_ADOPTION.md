# Prompt for the AI Agent (copy everything below the line)

---

## Task: Adopt the New Master Specification and Update Existing Documentation (READ-ONLY on code)

You are working on the **Barangay Problem Reporting and Management System** (Laravel, Blade, Leaflet, Gemini). I have finalized a consolidated specification: **`BARANGAY_WORKFLOW_MASTER_SPEC.md`**.

**From now on this specification is the source of truth for intended workflows, terminology, scope, and the roadmap.** It supersedes earlier decision summaries, chat-derived roadmaps, and any conflicting documentation.

### How the spec is organized
- **Part 1: Resident Side** (what the resident flow must do: Report a Concern, AI-assisted Step 1, automatic location Step 2, preserved Step 3, safeguards, Purok boundary preparation).
- **Part 2: Admin / Barangay Staff Side** (2-A Concern Intake and Handling, 2-B Blotter/Complaint/Barangay Case, 2-C System Administration, 2-D Agency and Referrals, 2-E Monitoring, 2-F Roles, 2-G Cross-cutting).
- **Part 3: Implementation Roadmap by TR** (TR1–TR9, each with objective, dependencies, a clean task table, scope, and exit criteria). **Part 3 is the build order. Parts 1 and 2 are the requirements.**

### Authority rules
1. **Spec = intent. Code = current reality.** Where they disagree, do **not** silently choose one and do not change code to match. Record the mismatch in the Open Items and Discrepancy Log (end of Part 3) and report it to me.
2. **[AUDIT FIRST]** items must be verified in the codebase before anyone designs or changes anything.
3. **[CONFIRM WITH BARANGAY]** items are legal or procedural rules. Do not automate them or present them as settled.
4. Never invent facts about the codebase. If you did not inspect it, say so.
5. TR6–TR9 task lists are **proposals** (marked "Proposed"). Do not treat them as approved scope.

### Hard restrictions (this task)
- **No code edits** to application files, views, controllers, services, routes, or tests.
- **No migrations, seeders, database changes, commits, or pushes.**
- **Preserve all uncommitted TR2.4.6-FIX changes** exactly as they are. Do not stash, reset, or reformat them.
- Documentation files (`.md` / docs folder) are the **only** things you may create or modify, and only as described below.

### Steps

**Step 1: Install the spec.** Place `BARANGAY_WORKFLOW_MASTER_SPEC.md` in the project's documentation folder (use the existing docs location; if none exists, propose one and ask before creating it).

**Step 2: Inventory existing docs.** List every documentation file (README, roadmap, TR1/TR2 audit reports, AI architecture, GIS/boundary notes, resident/admin workflow docs, referral docs, permissions, test plans, changelog). For each: path, topic, and whether it conflicts with the spec.

**Step 3: Conflict report (present before editing).** Table: `Document | Section | Existing statement | Spec statement | Proposed change`. Pay particular attention to:
- "Report a Community Problem" vs **Report a Concern** (resident-facing only; internal route/table/model/controller/field names do NOT change).
- Roadmap numbering. TR2 is resident-side only. **Admin-side geographic verification is TR3.4, not TR2.5.** The staff audit is **TR3.1-R0** (formerly "TR2.5.1-R0"). TR2.4.x sub-IDs are history only (see the TR2 history map).
- **Concern Intake (TR3)** vs **Concern Management (TR4)**.
- Blotter, Complaint, and Barangay Case are three distinct linked records; none is created automatically from a submission.
- AI is advisory only; it never creates blotters, accepts complaints, or creates cases.
- Pre-submission AI endpoint vs post-submission queued `ProcessNewReport`.
- Contact details are never sent to Gemini; only the description is.
- Provisional vs official Purok geometry (TR2.4 official import deferred; tooling arrives in TR6.2/TR6.3).

**Stop and wait for my approval of the conflict report before Step 4.**

**Step 4: Update the documentation** (after approval).
- Keep the TR1–TR9 structure and the task tables exactly as in Part 3; do not renumber.
- Update statuses per Part 3.
- For each flow (R1–R5, S1–S6, C1–C5, B1–B4, A1) make sure the related doc contains: purpose, required information, steps, flow, frontend expectations, backend notes, rules, acceptance criteria.
- Remove contradictions and outdated terminology. Keep historical audit findings, marked as historical where superseded.
- Add a changelog entry summarizing what changed and why.
- Work through the **Documentation Update Checklist** at the end of the spec and report which items you completed.

**Step 5: Verify the [AUDIT FIRST] items (read-only; inspect and report, do not fix).**
1. Do Blotter, Complaint, and `BarangayCase` functionality already exist? How do they relate to Community Reports? Is complaint intake separate from the case stage?
2. Referral closure: trace `ReferralService::close()` and its notifications. Confirm or refute that agency closure can leave the parent report in `referred` without notifying the resident.
3. Existing statuses, transitions, roles, and permissions for reviewing, assigning, closing, and referring reports.
4. What location fields staff currently see; whether staff Purok/location corrections are possible and logged.
5. Whether jurisdiction or staff decision reasons exist in the database.
6. Existing admin navigation labels and routes (do not rename anything).

### Output format
1. **Summary** (what you did and did not do)
2. **Doc inventory** table
3. **Conflict report** table (then stop for approval)
4. After approval: **files changed**, one line each
5. **Audit findings** per Step 5, each marked `Confirmed`, `Refuted`, or `Could not determine`, with file paths as evidence
6. **Updated Discrepancy Log**
7. **Questions for me** (e.g., who may decide jurisdiction, existing role names, numbering formats, approval of proposed TR6–TR9 tasks)

### Standing rules for all later work (TR3 onward)
- Reuse and extend existing modules; do not rebuild working features.
- Next implementation task after this one: **TR3.1-R0** (read-only audit), then **TR3.2** design for my approval, **before** any TR3 code. Never start a later TR's work early (see each TR's scope table).
- Preserve the resident's original description and location. Staff corrections are logged with actor, time, old value, new value, and reason.
- Preserve duplicate-review safeguards (ranked suggestions, dismiss, merge with mandatory note, merge controls hidden for emergency reports).
- Do not change referral lifecycle behavior before tracing existing rules and notifications.
- If anything in the spec is ambiguous or conflicts with the code, ask instead of guessing.
