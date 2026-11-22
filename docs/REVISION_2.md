# REVISION 2: Secretary's answers, Phase 0 reconciliation, team decisions

Date: 2026-11-22. Applies to the Barangay Problem Reporting and Management System (Calarian, Zamboanga City).

**Precedence (highest first):** `REVISION_2.md` > `SPEC_ADDENDUM.md` > `TEAM_WORKSTREAMS.md` > `PHASE0_FOUNDATION_TASK.md` (historical). If the repository contradicts a document, the repository wins, and the document gets fixed. Items tagged **[V]** must be checked in the repo before anyone relies on them, because these documents were written without opening the code.

**Tags used below**

| Tag | Meaning |
| :--- | :--- |
| **[A]** | The Barangay Secretary said it (question number given) |
| **[D]** | The team decided it (this round) |
| **[P]** | Proposal by the documentation review. Needs lead sign-off, and in several cases the barangay's blank forms (collected tomorrow) |
| **[V]** | Verify in the repo or database before use |

---

## 0. What is in this package

| File | Action |
| :--- | :--- |
| `REVISION_2.md` | New. This file |
| `PLACEHOLDERS.md` | **Replaced.** Question numbers now match the real 43-question form; values and status updated |
| `WORKFLOW.md` | Section 7 follow-up default fixed; section 8 case lifecycle replaced |
| `SPEC_ADDENDUM.md` | Patched (sections 2, 3, 4.2, 5.5, 5.6, 6, 7, 8.1, 10.1, 10.3, 10.4, 10.6, 10.8, 11) |
| `TEAM_WORKSTREAMS.md` | Patched (assignment, ownership, contracts, WS1/WS3/WS4/WS5 briefs, new WS6, schedule, demo order) |
| `FOUNDATION_HANDOFF.md` | Counts corrected; workstream section replaced; route map caveats added |
| `PHASE0_FOUNDATION_TASK.md` | Marked as executed and historical; two stale rules flagged |
| `ARCHITECTURE.md` | Section numbers, slug count, service descriptions, numbering example fixed |
| `DATABASE.md` | `users` table brought up to date; planned changes listed |
| `PROJECT.md` | Roles and scope note updated (Lupon, administrators, adviser status) |
| `CURRENT-STATE.md` | Phase 0 row and test totals added |
| `FOUNDATION_DISCOVERY.md` | Open verification items appended |

Not in the upload, so not revised: `ACADEMIC_DOCS_UPDATE.md`, `UI_UX_SPEC.md`, `CONVENTIONS.md`, `TESTING.md`, `API.md`, `TASKS.md`. Section 11 lists what each of them needs from this revision.

---

## 1. Decisions made this round

| # | Decision | Effect |
| :--- | :--- | :--- |
| D1 | **Lupon members get accounts.** [D] | Q3 left them out of the login list, but Q15 says they record walk-ins. New position `lupon_member`, preset `case_encoder`. See section 5.6 |
| D2 | **Import of old files is included, as a stretch item.** [D] | New workstream **WS6** (section 6.6). Scope: the last 4 record years (Q8). Starts only after the guaranteed deliverables |
| D3 | **Phase 0 is already built.** [D] | Documents that described it from the plan are corrected to the built state (section 4) |
| D4 | **Blank forms are collected tomorrow (Mon 2026-11-23).** [D] | Official wording and layouts replace the drafts after that. Collection checklist in section 9 |
| D5 | Case module scope still needs the adviser. | `cases.enabled` stays the master switch. The secretary's answers make the case module central to the barangay's daily work (Q15 to Q27), which is the evidence to show the adviser |

---

## 2. What the answers changed (design changes)

The old design-change triggers were Q16, Q21/Q26, Q39 and Q1 (old numbering). Reading the real answers: Q15 does not fire (walk-ins are recorded by the Secretary and Lupon members, no other office owns cases), Q38 does not fire (internet is stable), Q1 fires partly (import, D2). **The escalation-route trigger did fire**, and four more design points surfaced that the drafts did not anticipate.

| # | Change | Evidence | Old draft | New design |
| :--- | :--- | :--- | :--- | :--- |
| C1 | **No summons limit. The clock is days.** | Q20, Q25 | 3 summons, then "eligible to escalate" | 15 working days of mediation, then endorsement to the Pangkat, then 15 more working days (up to 30 with extension). The system flags deadlines. Summons are counted for information only |
| C2 | **The complainant decides the next step; the Pangkat Chairman signs the CFA.** | Q22, Q23, Q5 | Punong Barangay decides; escalation is a letter | Escalation records who requested it and when; the Certification to File Action (CFA) is a separate printable signed by the Pangkat Chairman; then the case is endorsed to PNP, prosecutor or court (Q18) |
| C3 | **Two record types: blotter-only and formal complaint.** | Q18 | One kind of case | `cases.record_type` = `blotter` or `complaint`. Blotter-only entries have no summons, deadlines or lifecycle, and can be upgraded to a complaint |
| C4 | **Number format `MM-NNN-YYYY`, restarting each year.** | Q9 | `CASE-{YEAR}-{SEQ:4}` | `{MM}-{SEQ:3}-{YEAR}`, for example `11-001-2026`. Needs a new `{MM}` token |
| C5 | **Referral follow-up is 3 days.** | Q12 | 7 days | `referral.follow_up_days = 3` |
| C6 | **Confidentiality follows people and case types.** | Q7 | Manual checkbox only | Case types BCPC and VAWC pre-tick "confidential"; `cases.view_confidential` goes to the people Q7 lists |
| C7 | **Agencies receive case papers, not just referrals.** | Q29 | Agencies see referrals only | A case endorsement reaches the agency as a referral with downloadable attachments (still no direct access to `cases`) |
| C8 | **Reports: what is actually submitted.** | Q32, Q33, Q34 | Seven draft reports, monthly KP report | KP accomplishment is **yearly** to DILG; summary by case type is **monthly** to DILG; output is **printed**; add a purok by problem matrix |
| C9 | **Trimmed party form.** | Q16, Q17 | Many optional fields on | Full name, alias, age or birth date, contact number, complete address; non-residents add home barangay, city and reason for being in the barangay. Other fields off by default |
| C10 | **Backups and old-record search are the secretary's priorities.** | Q34 | Extras | Backups (WS1) and global search across all years are raised to guaranteed deliverables |

Also from Q2: "same case recorded twice" is direct evidence for **duplicate flagging** and **party merging**. Both move from stretch to expected, and a case-level duplicate check is added (section 6.3).

---

## 3. Answer register (all 43 questions)

Status: **Applied** = reflected in `PLACEHOLDERS.md` v2. **Follow-up** = needs a question tomorrow. **Note** = no system change.

| Q | Topic | Answer (short) | Status | Lands in |
| :--- | :--- | :--- | :--- | :--- |
| 1 | Records today | Logbook, Excel/Word, "a computer program", loose papers | Applied (D2) | WS6 import |
| 2 | Hardest part | Too much handwriting, hard to find old records, same case twice | Note + C10 | Proposal evidence, WS3 duplicates, WS1 search |
| 3 | Logins | Captain, Secretary, Kagawad (Peace & Order), Tanod/desk officer. Lupon added by D1 | Applied | Positions, presets |
| 4 | Who decides | Captain, Secretary | Applied | Preset design (5.6) |
| 5 | Who signs | Captain signs referral letter, summons, monthly/yearly reports. CFA: Pangkat Chairman | Applied | Signatory matrix |
| 6 | System administrator | Both (Captain and Secretary) | Applied | Two `admin` users |
| 7 | Sensitive records | Captain, Secretary, Kagawad (Peace & Order), kagawads handling minors' cases, VAW focal person | Applied; Follow-up (Lupon) | `cases.view_confidential` |
| 8 | Record year | Calendar year; old records boxed; last 4 years searched | Applied | Fiscal year, import scope |
| 9 | Numbers | Restart every year; `month-case number (001)-year` | Applied; Follow-up (blotter, referral) | Numbering |
| 10 | Handled by barangay | Drainage/canal, garbage/dumping, streetlight, stray animals/noise, peace and order | Applied | Categories |
| 11 | Referred out | Water, electric, fire hazard, illegal construction, serious crime/threats, medical emergency | Applied | Categories, routing |
| 12 | Outside offices; follow-up | City Engineer, CDRRMO, water, electric, CENRO, PNP, BFP, CSWDO; follow up after **3 days** | Applied | Offices, `referral.follow_up_days` |
| 13 | After a report | Secretary/desk records; kagawad or tanod inspects; Captain informed; letter and follow-up; resident told | Note | Matches the existing flow. See 6.3 for "Captain informed" |
| 14 | Guest reporting | **Not sure** | Follow-up | Design stays: no resident accounts (settled scope) |
| 15 | Who records walk-ins | Secretary, Lupon members | Applied (D1) | No design change |
| 16 | Resident check | Barangay ID or certificate, household masterlist, ID with Calarian address, purok leader | Applied | "How verified" options |
| 17 | Party details | Full name, alias, age/birth date, contact, complete address; non-resident: home barangay and city, reason | Applied | Field configs |
| 18 | Complaint steps | Details, classify, summons, hearing, agreement, CFA if unsettled, endorse to police/prosecutor/court; blotter-only kept separate | Applied (C2, C3) | Lifecycle |
| 19 | Case types | Ten, including "BCPC and VAWC cases" | Applied; Follow-up (split) | Case types |
| 20 | Summons count | Not counted; days until endorsement to Pangkat (15 working days) | Applied (C1) | Deadlines |
| 21 | Summons record | Date issued, hearing date/time, who delivered, result (received, refused, not found) | Applied | Summons fields, enum |
| 22 | If not settled | CFA is issued | Applied (C2) | Escalation |
| 23 | Who decides | The complainant | Applied (C2) | Escalation form |
| 24 | Settlement record | Signed agreement, date settled, terms or amount | Applied | Settlement modal |
| 25 | Deadlines | 15 working days mediation; another 15 to 30 days conciliation | Applied; Follow-up (start date, day type) | Settings |
| 26 | Report to case | Sometimes | Applied | "Convert to case" stays |
| 27 | Reminders | Hearing dates, undelivered summons, cases near deadline, unsettled cases ready for next step | Applied | Secretary dashboard |
| 28 | Agencies | PNP, CSWDO, water/electric utility | Applied | Pilot agency accounts |
| 29 | Agency actions | View only their cases, download papers, mark received and add updates | Applied (C7); Follow-up (decline/close) | WS5 |
| 30 | Approves agency accounts | Secretary | Applied | `agencies.approver_position` |
| 31 | Verify agency person | Official letter or ID | Applied | ID or letter upload stays required |
| 32 | Reports submitted | KP accomplishment (yearly, DILG); summary by case type (monthly, DILG); blotter summary "Not submitted" | Applied (C8); Follow-up (blotter recipient shows "Other") | Reports Center |
| 33 | Output | Printed on paper | Applied | `reports.default_format = print` |
| 34 | Priorities | Quick walk-in entry; find old records; monthly and yearly reports; no data loss; problems per purok | Applied (C10) | Demo order, WS1/WS2 |
| 35 | Privacy notice | **Not sure** | Follow-up | Draft notice stays pending |
| 36 | Retention | 5 years | Applied | `privacy.case_retention_years` |
| 37 | Devices | Desktop at the hall, laptop | Note | Staff screens desktop first (already the rule) |
| 38 | Internet | Stable most of the time | Note | No offline mode |
| 39 | Anything else | Blank | Note | |
| 40 | Documents offered | Complaint form, summons form, settlement form, CFA, monthly/quarterly KP report, report sent to DILG/City Hall/PNP, officials and committees list, purok list, offices and agencies with numbers, emergency hotlines, certificate of residency (layout) | Collect tomorrow | Section 9 |
| 41 | How to share | Email, or physical copies (we visit the hall) | Note | |
| 42 | May we contact you again | Blank | Follow-up | Ask when collecting |
| 43 | Contact details | Blank | Follow-up | Ask when collecting |

Two things the answers do not cover and the secretary did not offer: the **letterhead and seal file** and **standard reply wording** (response templates). Ask for both tomorrow.

---

## 4. Phase 0 reconciliation (documented vs built)

Source of truth for "built" is `FOUNDATION_HANDOFF.md`, `FOUNDATION_DISCOVERY.md` and `DATABASE.md` section 3, all written after the code. Each row needs a one-time check in the repo **[V]**.

| # | Topic | What the documents said | What was built | Action |
| :--- | :--- | :--- | :--- | :--- |
| R1 | Table count | Handoff: "19 tables" | Its own inventory lists 22 (9 + 2 + 11, including the `permission_user` pivot) | Handoff corrected to 22. Verify with `php artisan db:table`/migrations |
| R2 | Model count | "20 models" | 21 listed | Corrected to 21 |
| R3 | Route count | "35 stub routes" | The handoff's own route table has **55** rows | Corrected. Regenerate from `php artisan route:list --json` and store as `docs/ROUTES.snapshot.json` |
| R4 | Missing routes | Briefs say "routes already exist, do not add" | Many routes in the briefs are not in the handoff table (section 4.1) | Each workstream may add the missing routes in its own route file, names per its brief. Existing names are never renamed |
| R5 | Workstream guide | Handoff section 6 defined WS1 to WS5 differently (WS4 = agency portal, WS5 = navigation, WS3 = all case work, "3-summons rule") | `TEAM_WORKSTREAMS.md` is the source of truth | Handoff section 6 replaced |
| R6 | Fiscal year status | Briefs: `upcoming/active/closed` + `is_locked` | Built: `active/inactive/closed/locked` + `is_locked` | Docs follow built. UI badge for `inactive` = grey (was `upcoming`) |
| R7 | Sequence key | Brief: `fiscal_year_id` nullable | Built: required, unique (`fiscal_year_id`, `type`) | `numbering.reset = never` is **not supported**. Remove the option from the numbering page |
| R8 | Permission presets | WS1 wizard lists `staff_full, case_encoder, referral_officer, viewer` | Built: `staff_full`, `case_encoder`, `reports_analyst` | Wizard uses the built presets plus the new `case_reviewer` (5.6) |
| R9 | Case source values | WS3 brief: walk-in, online report, other | Built: `direct`, `report_escalation` | Docs follow built |
| R10 | Summons service result | Secretary (Q21): received, refused, not found | Built: `served_personal`, `served_substituted`, `refused`, `unserved` | Migration (section 8): map to `received/refused/not_found/other` |
| R11 | Attendance and hearing status | Badge table: `no_show`, `rescheduled`, `held`, `reset` | Built: attendance `appeared/failed_to_appear`; hearing `scheduled/in_progress/concluded/rescheduled/cancelled` | Docs follow built. Badge table updated |
| R12 | Escalation destination | Brief: has `office_id` | Built: no `office_id` (contact fields instead) | Migration adds nullable `office_id` (needed for the referral handoff) |
| R13 | Residency values | Three values | Built has four (`transient` added) | Docs follow built; the form offers Resident, Non-resident, Unverified; `transient` unused unless the secretary asks |
| R14 | Case lifecycle | Three different versions: `WORKFLOW.md` (9 statuses), SPEC_ADDENDUM and brief (7 statuses), code (`CaseStatus`) | **[V]** which constants `CaseStatus` actually holds | One lifecycle in `WORKFLOW.md` section 8 (new). Align `CaseStatus` to it |
| R15 | Slug count | Architecture: 18 | Brief seeds 22 | Corrected to 22; `imports.manage` added by this revision |
| R16 | Preset contents | Architecture: `staff_full` covers cases | Brief: `staff_full` is report and referral abilities only | **[V]** `config/permission_presets.php` |
| R17 | Demo accounts | Brief: `@barangay.test`, password `password` | Handoff: `admin@calarian.gov.ph`, `staff@`, `clerk@`, `cswdo@zamboanga.gov.ph`, password `password123` | Handoff wins. Update `TESTING.md` |
| R18 | Settings table | Discovery: Phase 0 alters `settings` and `response_templates`; `referral_updates` reused | Handoff migration list shows none of these | **[V]** columns exist (`type, group, label, is_confirmed, source_question`; `title, kind`) |
| R19 | Referral follow-up | `WORKFLOW.md` and `DATABASE.md`: 7 days from `config/reports.php` | Secretary: 3 days | Setting `referral.follow_up_days`. Update existing tests that assert 7 days **[V]** |
| R20 | Scope wording | `PROJECT.md`: case module "fully in scope" | Handoff and addendum: adviser still to confirm | `PROJECT.md` now says "pending adviser confirmation" |
| R21 | Counter stub | Brief and handoff: `SummonsCounterService` enforces a limit | Obsolete (C1) | Contract change in 8.3 |
| R22 | Stale docs | `CURRENT-STATE.md` ends before Phase 0; `DATABASE.md` `users` still enum | | Patched |
| R23 | Architecture numbering | Duplicate section numbers 4 and 5 | | Fixed |

### 4.1 Routes in the briefs but not in the handoff route map

Verify first with `route:list`. Likely missing: `admin.pending.index/confirm`; template create/store/edit/update/destroy; fiscal-year `reopen`; `account.password.edit/update`; `admin.search`; backups `run/download`; personnel, position and user create/store/edit/update/deactivate/reset-password; `admin.reports-center.history`; `admin.cases.status/assign`; `admin.parties.search/edit/update/merge.*`; `admin.reports.convert-to-case`; case-type CRUD and `admin.case-fields.update`; summons `service/attendance/void/print`; hearing `update/outcome`; escalation `letter`; escalation-destination CRUD; `admin.agencies.show/approve/reject/suspend/reinstate/document`; `admin.referrals.send`; `admin.referrals.messages.store`; `agency.referrals.message`; `agency.invite.*`; `agency.team.revoke/deactivate`; `verification.*`. Already present under different names: `admin.fiscal-years.switch-view` (brief: `year.switch`), `admin.cases.settle` (brief: `admin.cases.settlement.store`), `admin.cases.escalate.*` (brief: `admin.cases.escalations.*`), `agency.referrals.accept/updates.store` (brief: `.update`). **Built names stay.** Update the briefs' names to the built ones when the route snapshot is made.

---

## 5. Design changes in detail

All of this is **[P]** until the blank forms are seen, except where an answer is quoted. Nothing here lets the system decide for an official: it flags, records and prints; people decide.

### 5.1 Case lifecycle (replaces `WORKFLOW.md` section 8 draft)

Full diagram and table are in `WORKFLOW.md` section 8. Summary:

`filed` > `in_mediation` (Punong Barangay, 15 working days) > `in_conciliation` (Pangkat, 15 working days, one extension of 15) > `settled` or `unsettled` > `escalated` (CFA issued and case endorsed). Terminal: `settled`, `escalated`, `dismissed`, `withdrawn` (staff-only reopening with a note).

New status vs the seven in the brief: **`in_conciliation`** only. Blotter-only records (`record_type = blotter`) stay at `filed` and use none of the lifecycle.

### 5.2 Deadlines and working days

| Setting | Value | Source |
| :--- | :--- | :--- |
| `cases.mediation_deadline_days` | 15 | Q25 |
| `cases.conciliation_deadline_days` | 15 | Q25 |
| `cases.conciliation_extension_days` | 15 | Q25 ("another 15 to 30") |
| `cases.deadline_day_type` | `working` | Q20, Q25 |
| `cases.deadline_warning_days` | 3 | Team assumption for Q27 "close to their deadline" |
| `calendar.holidays` | JSON list of dates | New; working days exclude weekends and these dates |

Start dates are a **follow-up question**: the mediation clock runs from `filed_on` by default; the conciliation clock from `endorsed_to_pangkat_on`. A new helper `App\Support\WorkingDays` (add, count between) is built once in Phase 0.5 so WS3 and WS4 share it. Wording check: statutory timelines for the Katarungang Pambarangay should be compared with the secretary's "working days" before the final defense. This revision has not verified the law.

### 5.3 Blotter-only vs complaint

New `cases.record_type`. The new-case form asks it first. Blotter-only: parties, narrative, notes, no summons or hearings tabs, never overdue, action **Convert to formal complaint** (same record, same number, status `filed`, clock starts at the conversion date). Reports Center counts both types separately. "Convert to case" from a problem report also asks which type.

### 5.4 Escalation and the CFA

Escalation form fields: destination (PNP Station, Prosecutor's Office, Court), **requested by** (one of the case's complainants), **requested on**, reason, CFA number (free text, optional), signed by (default the case's Pangkat Chairman), documents to print (CFA, endorsement letter, copy of case summary). On submit, one transaction: `case_escalations` row, status `escalated`, audit entry. No "eligible" gate and no override note: the secretary says the complainant decides, so the system only warns when a deadline has not yet passed ("Mediation period ends on 2026-12-04. Escalate anyway?"), and the official may continue.

CFA signer: `SignatoryResolver::forDocument('certificate_to_file_action', $asOf, ?BarangayCase $case)` returns the case's `pangkat_chairman_personnel_id` if set, otherwise the position holder at that date. **Follow-up:** is the Pangkat Chairman a fixed person or formed per case?

### 5.5 Numbering

`numbering.case.format = {MM}-{SEQ:3}-{YEAR}` (Q9). `{MM}` is the filing month. The sequence restarts each fiscal year, so December can still use `001` after a January that reached `250`. **Follow-up:** confirm the sequence is per year and not per month, because the example only shows `001`. Blotter-only entries use type `blotter`, same format until told otherwise. Referral numbers keep `REF-{YEAR}-{SEQ:4}` (existing tests depend on it) until the secretary confirms that Q9 covers referral letters. Imported cases keep their **original** numbers and never consume the sequence (6.6).

### 5.6 People, positions, presets and confidentiality

| Person | Login | Preset (built or new) | Extra permissions |
| :--- | :--- | :--- | :--- |
| Barangay Captain | yes | role `admin` | everything (Q6) |
| Barangay Secretary | yes | role `admin` | everything (Q6); approves agencies (Q30) |
| Kagawad (Peace & Order) | yes | `case_reviewer` (new) | `cases.view`, `cases.view_confidential`, `reports.view`, `reports_center.view` |
| Other kagawads handling minors' cases; VAW focal person | yes | `case_reviewer` (new) | same; grant per person |
| Lupon member | yes (D1) | `case_encoder` | records walk-ins and summons; **no** `cases.view_confidential` |
| Tanod / desk officer | yes | `staff_full` **[V]** | report work only. Q4 reserves review, approve and close for the Captain and Secretary, so decide whether `reports.review` and `reports.update_status` stay in this preset |

Visibility rule from `SPEC_ADDENDUM.md` section 7 stays: a Lupon member keeps access to a confidential case **they encoded**. **Follow-up:** is that acceptable, since Q7 does not list Lupon? Case types BCPC and VAWC pre-tick "Mark as confidential" through `case_types.is_confidential_default`. Possible legal point to ask about: BCPC and VAWC matters may follow special procedures, so they may need `case_types.uses_kp_timeline = false` (no mediation clock). Not verified here.

### 5.7 Agency portal and case papers

Q29 asks that an agency can view only what was sent to it, download case papers, mark received and add updates. Design: an escalated case is handed to the agency as a **referral** created by `ReferralService::createFromCase` (flag `case_referral_handoff` turned on), carrying attachments (CFA, endorsement letter, case summary PDF) on a new `referral_attachments` table on the private disk. Agency users still never read `cases`. The attachments contain party personal data, so they go out only through the portal and the privacy notice must cover it. **Follow-up:** should agencies also be able to decline or close, or only receive and update?

### 5.8 Party form

Required: full name. Shown: alias, birth date or age, contact number, address (with purok for residents), residency status, how verified (Q16 options plus Other). Non-residents: home barangay, home city, reason for being in the barangay. Hidden by default (not asked): sex, civil status, occupation, ID type and number, email. Age and reason are stored in `parties.extra` (no migration). Field visibility stays configurable.

### 5.9 Reports Center

Confirmed by Q32 to Q34: `kp_accomplishment` (yearly, DILG), `case_summary` as the summary by case type (monthly, DILG), printed output. New `purok_category_matrix` (problems per purok and category, reports and cases). The other five draft reports stay as optional, unconfirmed. Paper size is a follow-up (`reports.paper_size`, placeholder `a4`). Layouts come from the DILG and KP samples collected tomorrow.

### 5.10 Old-record search

`GlobalSearchService` searches **all fiscal years** by name, case number and date, and shows a year badge on each result. Case list gets an "All years" quick toggle. Case numbers such as `11-001-2026` must be searchable with and without hyphens.

---

## 6. Workstream changes (apply on top of `TEAM_WORKSTREAMS.md`)

### 6.1 WS1 (lead): setup, fiscal year, settings
- Wizard step 6 uses built presets plus `case_reviewer`.
- Numbering page: add `{MM}`, remove the "never" reset option, preview `11-001-2026`.
- New page `/admin/settings/calendar` (holidays). Small table plus add and remove.
- Pending page shows the `source_question` using the real numbers.
- **Backups and global search are guaranteed deliverables (C10).** Search spans all years (5.10).
- Case KPIs: replace "Eligible to escalate" with "Ready for next step".
- Add the position seeds and setup defaults from `PLACEHOLDERS.md` v2.

### 6.2 WS2: people, access, Reports Center
- Positions and signatory matrix per `PLACEHOLDERS.md` B1, B2. Resolver gets the optional `?BarangayCase $case` argument (optional parameters are allowed by the contract rules).
- Two administrators (Captain, Secretary): the last-admin guard already supports this.
- Lupon members are created through Personnel with "Create login" and the `case_encoder` preset.
- Reports: items in 5.9. `kp_accomplishment` filter becomes year, with optional month. Paper size setting. Print is the default action.

### 6.3 WS3: case intake and parties
- Record type (5.3), trimmed party form (5.8), case types with the two new flags, `cases.source` built values (`direct`, `report_escalation`).
- Case list badge shows the deadline state from `OverdueService` ("Day 9 of 15", gold near deadline, red "Overdue N d", grey "Ready for next step") plus a plain summons count with no limit.
- `CaseStatusService` implements the table in `WORKFLOW.md` section 8.
- **Duplicates (Q2):** `PartyMatchingService` as briefed, plus a **case duplicate check** on save: same complainant and respondent with a filing or incident date within 30 days, or the same case number. It warns and offers "Open existing case" or "Save anyway". It never blocks and never merges.
- **Party merge** moves from stretch to expected (`SPEC_ADDENDUM.md` 10.6).
- "Convert to case" asks for the record type.
- Optional: notify the Captain when a new report arrives (Q13 "Captain is informed") as a dashboard badge, not email. Confirm with the secretary first.

### 6.4 WS4: summons, hearings, escalation
Replaces `TEAM_WORKSTREAMS.md` WS4 in full (new text is in the patched file). Highlights: no counter limit, deadline panel, `WorkingDays`-based `OverdueService`, service results `received/refused/not_found`, settlement with terms or amount plus signed agreement file, CFA and endorsement printables, escalation form per 5.4, secretary dashboard per Q27, `case_referral_handoff` turned on after WS5.

### 6.5 WS5: agencies and referrals
- Follow-up days read `referral.follow_up_days` (3).
- Approver is the Secretary (permission `agencies.manage`); registration requires ID **or official letter** (Q31).
- `createFromCase` plus `referral_attachments` and an attachments list on both staff and agency referral pages (5.7).
- Pilot accounts: PNP, CSWDO, water utility, electric utility (Q28). Demo CSWDO account already exists.
- Hold the decline and close actions behind the follow-up answer; do not remove the code.

### 6.6 WS6 (new, stretch): legacy import
Owner: whoever finishes first among WS2 and WS5. Starts after WS3 merges (needs `PartyMatchingService` and the case model). Branch `feature/ws6-import`, migrations `2026_12_01_60NNNN_`, route file `routes/imports.php`, permission `imports.manage` (admin only by default), feature flag `legacy_import` (off until ready).

**Scope:** the last 4 record years of cases, blotter entries and parties, from Excel or CSV. Word tables are copied into the Excel template by staff. Handwritten logbooks are **not** read by the system. There is no OCR and no AI on these files, because party data must never go to Gemini. A staff member types them into the template, or into the normal forms.

**Flow:** upload file (private disk) > map columns (mapping can be saved per source) > validate > preview with warnings > commit in chunks > report, with **roll back whole batch** available.

**Tables:** `import_batches` (user, filename, status `uploaded/mapped/previewed/committed/rolled_back`, mapping json, stats json) and `import_rows` (batch, row number, raw json, mapped json, status `pending/valid/warning/error/imported/skipped`, errors json, `case_id`). `cases.import_batch_id` nullable marks imported cases.

**Rules:**
1. Keep the original case number. A number already in use is a conflict row, not an overwrite.
2. Never call `NumberingService::next()`. Sequences stay untouched.
3. Fiscal years for the 4 years are created if missing, imported while open, then closed at the end of the batch.
4. Parties: exact normalized name plus birth date or contact number links to an existing person; "likely" matches go to a review list. Nothing merges by itself.
5. Cases: the same duplicate check as WS3. Possible duplicates are flagged for a person to decide.
6. Legacy status maps to built statuses in the mapping step. Unknown becomes `filed` with a note.
7. Every batch writes audit entries. The source file can be deleted after commit.
8. Provide a **downloadable blank Excel template** with the case and party columns, so encoding can start before the old program's export format is known.

**Definition of done:** a sample of 100 rows from the barangay's files imports with a preview, conflicts are listed, the batch can be rolled back, and the imported cases appear in global search under their original numbers.

**"Last 4 years"** needs a follow-up (2023 to 2026, or 2022 to 2025 plus the current year). The records stay under `records.lookback_years = 4`. Retention is 5 years (Q36), so the data kept is longer than the data searched.

---

## 7. Settings, seeds and documents

All values, statuses and swap owners are in `PLACEHOLDERS.md` v2. Seeds to change: positions, signatory matrix, case types (with two flags), case field configs, escalation destinations, report templates, categories and routing (Q10, Q11, Q12), offices, settings keys.

---

## 8. Phase 0.5: alignment before the workstreams branch (lead)

Phase 0 is built, so these are new, small migrations and edits, done once so no workstream has to touch lead-owned files. Branch `feature/phase0-5-alignment`. Estimated half a day with an AI assistant. Run tests after each step.

### 8.1 Discovery (no code)
Check every **[V]** item: `route:list` snapshot, migrations for `settings`, `response_templates`, `referral_updates`, the contents of `CaseStatus`, `config/permission_presets.php`, existing follow-up tests, the actual `categories`, `routing_rules`, `offices` seeds. Record results in `FOUNDATION_DISCOVERY.md`.

### 8.2 Migrations (prefix `2026_11_23_0002NN_`)
| Table | Change |
| :--- | :--- |
| `summons` | `service_result` to `string(20)`; remap `served_personal` and `served_substituted` to `received`, `unserved` to `not_found`; allowed values `pending`, `received`, `refused`, `not_found`, `other` (validated in code) |
| `cases` | add `record_type string(20) default 'complaint'` (`blotter`, `complaint`); `endorsed_to_pangkat_on date ?`; `conciliation_extension_days unsigned tinyint default 0`; `pangkat_chairman_personnel_id fk personnel ?`; `settlement_amount decimal(12,2) ?` |
| `case_types` | add `is_confidential_default bool default false`; `uses_kp_timeline bool default true` |
| `case_escalations` | add `requested_by_case_party_id fk case_parties ?`; `requested_on date ?`; `cfa_number string(60) ?`; `signed_by_personnel_id fk personnel ?`. The old `decided_by_personnel_id`, `override_note`, `summons_count_at_time` stay, unused, nullable |
| `escalation_destinations` | add `office_id fk offices ?` |
| `referral_attachments` (new) | `referral_id fk cascade`, `label str`, `path str` (private disk), `uploaded_by fk users ?`, timestamps |

Leave `summons.sequence_no`, `summons.override_note` and the existing enums for attendance and hearing status as they are (docs follow built).

### 8.3 Code
- `NumberingService`: token `{MM}`; type `blotter`; refuse a format without `{SEQ:n}`; no `never` reset.
- `App\Support\WorkingDays` plus setting `calendar.holidays`, with unit tests (weekend, holiday, month boundary).
- `CaseStatus`: add `IN_CONCILIATION`; labels; transition table from `WORKFLOW.md` section 8 (constants only; `CaseStatusService` stays a WS3 stub).
- `SummonsCounterService` contract: `forCase()` returns `['issued' => int, 'limit' => ?int, 'remaining' => ?int, 'eligible' => false]`. With `cases.summons_limit` empty the limit is null.
- `config/permission_presets.php`: add `case_reviewer`. `config/features.php`: add `legacy_import` (false). Seed permission `imports.manage`.
- `SignatoryResolver` signature: add optional third argument `?BarangayCase $case = null` (still a stub).
- Seeders updated from `PLACEHOLDERS.md` v2. `DemoDataSeeder`: add a Lupon member, a Pangkat Chairman and a blotter-only entry; replace the "3 summons eligible" demo case with a case at day 14 of 15.

### 8.4 Tests
Migration remap, numbering with `{MM}`, working days, status table, `visibleTo()` still correct for encoder and confidential cases, seeders idempotent, full suite green (184 now), Pint clean.

### 8.5 Docs
Copy this package into `docs/`, regenerate `API.md` from the route snapshot, update `TESTING.md` (credentials, commands).

---

## 9. Tomorrow: collection checklist and questions

### 9.1 Forms to collect (Q40) and who uses them

| Form | Replaces | Owner |
| :--- | :--- | :--- |
| Complaint form | Case intake layout and field order | WS3 |
| Summons form | Printable summons | WS4 |
| Settlement or agreement form | Settlement printable and fields | WS4 |
| Certification to File Action (or similar) | CFA draft in `SPEC_ADDENDUM.md` 5.6 | WS4 |
| Monthly or quarterly KP report sample | `kp_accomplishment`, `case_summary` layouts | WS2 |
| A report sent to DILG, City Hall or PNP | Same | WS2 |
| List of officials and committees | Personnel and positions | WS2 |
| List of puroks or zones | `puroks` seed | WS1 |
| Offices and agencies with contact numbers | `offices`, referral destinations | WS1 |
| Emergency hotline numbers | `emergency_contacts` | WS1 |
| Certificate of residency or barangay ID (layout only) | "How verified" wording | WS3 |

**Ask for as well:** letterhead and seal file (WS1); any standard reply wording (WS1); one sample export or screenshot of "the computer program" and 20 anonymized rows of the Excel files (WS6).

### 9.2 Questions for the Secretary
1. Mediation clock: from the date filed, the first summons or the first hearing? Are the conciliation days working days too? Who grants the extension?
2. Is the Pangkat Chairman one fixed person, or formed for each case? Who prepares the CFA?
3. Please show a real case number. Does the sequence restart per year (as Q9 says) or per month? Do blotter entries, complaints and referral letters share it?
4. Are blotter-only entries and complaints in the same logbook? Can a blotter entry later become a complaint?
5. Are BCPC and VAWC two case types? Do they follow the same 15-day process?
6. May the Lupon member who encoded a confidential case keep viewing it? Are domestic and family disputes confidential?
7. May an agency decline or close a referral, or only mark received and add updates? Which papers go to the PNP and CSWDO?
8. Does the barangay have a privacy notice or consent form (Q35 was "Not sure")?
9. What paper size are printed reports? (A4, letter or long bond.)
10. Q32: the blotter summary says "Not submitted" but lists a recipient. Which is right?
11. Is "Kagawad (Peace & Order)" one person? Which kagawads handle minors' cases?
12. Which years count as "the last 4 years"? Who can type the handwritten logbooks?
13. May we contact you again, and how? (Q42 and Q43 were blank.)
14. Q14 "Not sure": would the Captain want residents to need an account to report? (We keep guest reporting unless told otherwise.)

### 9.3 Questions for the adviser
1. Are the case and summons features required? (Show Q15 to Q27 as the evidence.)
2. Is the legacy import acceptable as a stretch item?

---

## 10. Risks

- **Adviser scope:** the case module plus WS6 is large. Guaranteed: setup, agency portal, Reports Center, backups, search. Case module is core but can be switched off. WS6 is last.
- **Privacy:** respondents never consented and the secretary is unsure about the privacy notice (Q35). Write the RA 10173 position before the case module is demonstrated with real data. Import and agency attachments widen the exposure.
- **Legal wording:** summons, CFA, endorsement and privacy texts are drafts. Replace them with the barangay's own forms.
- **Unverified code facts:** everything tagged **[V]**.
- **Day counting:** working vs calendar days and the start date are not settled. The settings make them changeable, but the demo should not claim legal accuracy.

---

## 11. Notes for documents not revised here

- **ACADEMIC_DOCS_UPDATE.md / proposal:** add evidence from Q2 (duplicates, finding old records) and Q34 (priorities). Rewrite any requirement that mentions a 3-summons limit. Add requirements for: 15 working day tracking, blotter-only records, CFA, confidentiality by case type, agency case papers, legacy import (stretch, pending). Mark each "pending barangay confirmation" until the forms are in.
- **UI_UX_SPEC.md:** replace the summons counter card with a deadline panel; add Pattern for the escalation form and the import wizard.
- **CONVENTIONS.md:** add the new status color and the rule "never auto-transition a case".
- **TESTING.md:** credentials (R17), `app:demo` data changes, import test sample.
- **API.md, TASKS.md:** regenerate routes from the snapshot; add Tasks for Phase 0.5 and WS6.

---

## 12. Next steps (in order)

1. Collect the forms and ask the questions in section 9 (tomorrow).
2. Ask the adviser about the case module and import (section 9.3).
3. Run Phase 0.5 discovery (8.1), then 8.2 to 8.5.
4. Copy this package into `docs/`.
5. Apply the form-based swaps from `PLACEHOLDERS.md` (owners listed there), press **Confirm** on `/admin/settings/pending` as each answer is settled.
6. Branch the workstreams. WS3 and WS4 start first. WS6 starts after WS3 merges.
7. Update the proposal and Blueprint (section 11).
