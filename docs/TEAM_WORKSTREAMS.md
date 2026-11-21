# TEAM WORKSTREAMS: 5 members, no overlap

Barangay Problem Reporting and Management System, Barangay Calarian, Zamboanga City.
Group 3 (SynohDev). This file is the **single source of truth for who builds what**.

---

## 1. How the work is split

```
PHASE 0 (lead + AI, first)     FOUNDATION: schema, services, routes, layout, UI kit, seeders, tests
        |
        v   merged to main, then everyone branches from it
+-----------+-----------+-----------+-----------+-----------+
|   WS1     |   WS2     |   WS3     |   WS4     |   WS5     |
| Setup &   | People &  | Cases:    | Cases:    | Agencies  |
| Fiscal    | Access &  | intake &  | summons,  | & Referral|
| Year &    | Reports   | parties   | hearings, | updates   |
| Settings  | Center    |           | escalation|           |
+-----------+-----------+-----------+-----------+-----------+
        |
        v
 Integration sprint -> wait for secretary answers -> swap placeholders -> demo
```

**Why this split avoids overlap:** every workstream owns its own route file, controllers, views folder, test folder, and migration timestamp range. Everything shared (schema, services, layout, UI components, nav, config) is built once in Phase 0 and is **read-only** for the workstreams.

### Assignment (fill in names)

| WS | Name | Member |
| :--- | :--- | :--- |
| Phase 0 + WS1 | Foundation, Setup, Fiscal Year, Settings | Lead (you) |
| WS2 | People, Access, Reports Center | ______ |
| WS3 | Cases: intake and parties | ______ |
| WS4 | Cases: summons, hearings, escalation | ______ |
| WS5 | Agency accounts and referral updates | ______ |

WS3 and WS4 are the heaviest. Give them to the two members most comfortable with Laravel. WS4 can start on day one because Phase 0 ships the case show-page skeleton and demo cases.

---

## 2. Ownership matrix (who may edit what)

| Area | Owner | Others |
| :--- | :--- | :--- |
| `database/migrations/2026_11_22_0001*` (Phase 0 schema) | Lead | Read only |
| New migrations after Phase 0 | The WS that needs them, using its **timestamp range** below | Never edit another WS's migration |
| `app/Services/` Phase 0 services (Settings, Numbering, FiscalYear*, Permission, AuditLogger) | Lead | **Call only.** Request changes via the lead |
| `config/*`, `bootstrap/app.php`, `routes/web.php`, `layouts/*`, `components/ui/*`, `config/navigation.php` | Lead | Read only |
| `RequiredDataSeeder`, `DemoDataSeeder` | Lead | Ask the lead to add seed rows, or add your own seeder class `Ws{n}ExtraSeeder` and tell the lead to register it |
| `docs/PROJECT.md`, `DATABASE.md`, `API.md`, `WORKFLOW.md`, `ARCHITECTURE.md`, `TASKS.md` | Lead | Write your notes in `docs/modules/ws{n}-*.md` instead |
| `routes/setup.php`, `routes/settings.php` | WS1 | |
| `routes/people.php`, `routes/reports_center.php` | WS2 | |
| `routes/cases.php` | WS3 | |
| `routes/case_actions.php` | WS4 | |
| `routes/agency.php` | WS5 | |

**Migration timestamp ranges** (prevents ordering clashes): WS1 `2026_12_01_10NNNN_`, WS2 `2026_12_01_20NNNN_`, WS3 `2026_12_01_30NNNN_`, WS4 `2026_12_01_40NNNN_`, WS5 `2026_12_01_50NNNN_`.

**Folders per workstream**

| WS | Controllers | Views | Tests | Services |
| :--- | :--- | :--- | :--- | :--- |
| WS1 | `Http/Controllers/Setup`, `Admin/Settings` | `views/setup`, `views/admin/settings` | `tests/Feature/Setup` | `app/Services/Setup/` |
| WS2 | `Admin/People`, `Admin/ReportsCenter` | `views/admin/personnel`, `admin/positions`, `admin/signatories`, `admin/users`, `admin/reports-center`, `views/account` | `tests/Feature/People` | `app/Services/People/`, `app/Services/Reporting/` |
| WS3 | `Admin/Cases` | `views/admin/cases`, `admin/parties` | `tests/Feature/Cases` | `app/Services/Cases/` |
| WS4 | `Admin/CaseActions` | `views/admin/case-actions` | `tests/Feature/CaseActions` | `app/Services/CaseActions/` |
| WS5 | `Agency`, `Admin/Agencies` | `views/agency`, `views/admin/agencies` | `tests/Feature/Agency` | `app/Services/Agency/` |

---

## 3. Contracts between workstreams

These are the only places two workstreams touch. Each side codes against the **signature**, so nobody waits for the other.

| Provider | Contract | Consumer | Until the provider finishes |
| :--- | :--- | :--- | :--- |
| Phase 0 | `SettingsService`, `NumberingService`, `FiscalYearService`, `PermissionService`, `AuditLogger` | Everyone | Fully working from day one |
| WS2 | `SignatoryResolver::forDocument($type, $asOf)` | WS4 (summons, escalation letter), WS5 (referral letter), WS2 reports | Returns `[]`; views print blank signature lines |
| WS3 | `CaseStatusService::transition($case, $to, $note, $by)` | WS4 (escalation, settlement) | Phase 0 stub throws `LogicException`; WS4 develops against the demo case and unit-mocks the service |
| WS4 | `OverdueService::forCase($case)` | WS3 (list badge) | Stub returns not overdue |
| WS5 | `ReferralService::dueForFollowUp()` | WS4 (secretary dashboard) | Stub returns an empty collection |
| WS1 | `GlobalSearchService::search($q, $user)` | Header search box (Phase 0) | Stub returns empty groups |
| Phase 0 | `BarangayCase::visibleTo($user)` scope | WS2, WS3, WS4 | Real from day one; **every case query must use it** |
| WS3 | `BarangayCase` model, case show page skeleton, `case_field_configs` reads | WS4 | Already exists from Phase 0 |
| WS4 | `SummonsCounterService::forCase($case)` | WS3 (list badge, case header) | Stub returns zeros |
| WS5 | `ReferralService::createFromCase($case, $destination, $by)` | WS4 (escalation "send through system") | Hidden behind `config('features.case_referral_handoff')`; WS4 flips the flag only after WS5 merges |
| WS1 | `FiscalYearGuard`, year switcher, year filter conventions | WS2 reports, WS3 lists | Provided by Phase 0 |

**Rules for contracts**
1. Never change a signature without the lead's approval. If you need more data, add an **optional** parameter.
2. Never reach into another WS's tables with raw queries. Use the model relationships created in Phase 0.
3. If you are blocked by another WS, use the stub or demo data and continue. Do not wait.

---

## 4. Git and merge rules

- Branches: `feature/ws1-setup`, `feature/ws2-people-reports`, `feature/ws3-cases`, `feature/ws4-case-actions`, `feature/ws5-agency`. Branch from `main` **after** Phase 0 is merged.
- `main` is protected. Merge by pull request only, **at least one other member reviews**.
- Small pull requests (one page or one feature each). Rebase on `main` at least once a day.
- Before each PR: `php artisan test`, `./vendor/bin/pint --test`, no edits outside your owned paths (the PR template checklist below enforces this).
- **Merge order if two PRs conflict:** WS1, WS2, WS3, WS4, WS5.
- Integration sprint: after each WS merges its main features, the lead runs the full demo script and files bugs per WS.

**PR checklist (copy into every PR)**
- [ ] Only files in my owned paths changed (list any exceptions and who approved)
- [ ] Tests added in my test folder; full suite green
- [ ] Pint clean
- [ ] Every create, update, delete, approval, and status change writes an audit log through `AuditLogger`
- [ ] Every placeholder value is read from settings or seed tables, not hardcoded
- [ ] Draft forms and print views show `<x-ui.draft-watermark>` while their `is_confirmed` is false
- [ ] Empty states, validation errors, and a 403 for missing permission all checked by hand
- [ ] Screens checked at 375 px and 1280 px wide

---

## 5. Rules every workstream follows

1. **Permissions:** protect every route with `permission:{slug}`. Never check `role === 'staff'` in new code.
2. **Fiscal year:** every list defaults to the viewing year (`FiscalYearContext::viewing()`); every new record calls `FiscalYearService::forNewRecord()`; edits to finalized records call `FiscalYearGuard::assertWritable()`.
3. **Numbers:** use `NumberingService::next()`. Never concatenate numbers yourself.
4. **Confirmation:** show `<x-ui.pending-badge>` next to any value that is unconfirmed.
5. **Privacy:** parties and case data are sensitive (Data Privacy Act, RA 10173). Never send party data to Gemini or in emails. Emails contain only case number, date, and a link or instruction.
6. **UI:** use the `x-ui.*` components, FontAwesome icons, Tailwind tokens. Buttons use the standard labels listed in each workstream below.
7. **AI stays advisory:** nothing in these modules lets the AI create, route, or close anything.
8. **Thin controllers:** logic in services, validation in Form Requests, transactions around multi-table writes.

---

## 6. Workstream briefs

**Extra scope and rules are in `SPEC_ADDENDUM.md`** (permission map, report definitions, draft texts, validation rules, confidential cases, UI colours, email and file storage, extra features). Where it differs from a brief below, the addendum wins. Extra features are assigned there: WS1 global search, case KPIs, backups; WS3 merge duplicate parties; WS4 secretary dashboard and overdue indicators; WS5 agency team and invitations. The Requirements Blueprint and proposal updates are assigned in `ACADEMIC_DOCS_UPDATE.md` section 3.

Each brief can be handed to that member's AI assistant as-is, after this header:

> *You are building workstream {WS} of the Barangay Problem Reporting and Management System. Read `PROJECT.md`, `ARCHITECTURE.md`, `DATABASE.md`, `CONVENTIONS.md`, `docs/PLACEHOLDERS.md`, `docs/SPEC_ADDENDUM.md`, `docs/FOUNDATION_HANDOFF.md`, and sections 1 to 5 of `docs/TEAM_WORKSTREAMS.md`. Edit only the paths owned by your workstream. The routes and stub controllers already exist; replace the stub bodies. Do not rename routes or change service signatures. Build the pages, buttons, and rules below, with tests, and finish with a summary of what changed.*

---

### WS1: Setup, Fiscal Year, Settings (Lead)

**Goal:** answer the adviser's "the system is empty" criticism. A fresh install guides the administrator through setup, then the system runs scoped to the active fiscal year, with every unconfirmed assumption visible.

#### 1.1 Setup wizard (`/setup`, no login required until step 2 completes)

A 7-step wizard with a progress bar ("Step 3 of 7"). Buttons on every step: **Back**, **Save and continue**. Steps are resumable (progress stored in settings key `system.setup_step`).

| Step | Page | Fields | Rules |
| :--- | :--- | :--- | :--- |
| 1 | Welcome | none. Shows checklist: database connected, storage writable, mail driver | Button **Start setup**. Warn (not block) if mail driver is `log` |
| 2 | Administrator account | name*, email*, password* (min 10), confirm password* | Creates the first `admin` user and logs them in. Only possible while no admin exists |
| 3 | Barangay profile | barangay name*, city*, address*, contact number, email, seal image (PNG/JPG, max 2 MB, preview) | Saves `barangay.*` settings |
| 4 | Fiscal year | year* (default current year), start month (default January, from `fiscal_year.start_month`), computed start and end dates shown read-only | Creates and activates the year. Help text: "Records and numbers are grouped by this year" |
| 5 | Positions and personnel | Repeating rows: position* (select from `positions`), full name*, signatory checkbox, "Create login" checkbox, email (required if login). Buttons **Add another person**, **Remove** | Uses `PersonnelService::create()`. At least one signatory is recommended (warning, not block). Includes link "Edit the list of positions" |
| 6 | Access | Table of staff with login: preset select (`staff_full`, `case_encoder`, `referral_officer`, `viewer`, custom) and a permission checklist for custom | Uses `PermissionService`. Temporary passwords generated; the page shows them **once** with a copy button and sets `must_change_password` |
| 7 | Review and finish | Read-only summary of steps 3 to 6, plus the count of items pending confirmation | Button **Finish setup** sets `system.setup_completed = true`, writes an audit entry, redirects to login with a success message |

Validation errors appear under each field. After completion `/setup` is closed (handled by Phase 0 middleware). Provide an admin-only "Run setup checks again" link in `/admin/settings/general` (no data reset).

#### 1.2 Fiscal years (`/admin/settings/fiscal-years`)

Table: Year, Start, End, Status (badge `Upcoming/Active/Closed`), Locked icon, Records count (problem reports, referrals, cases). Buttons: **Add fiscal year**, **Set active** (confirm dialog), **Close year**, **Reopen** (admin only).
- **Close year** opens a modal that lists unfinished items ("12 open reports, 3 open cases will carry over"), requires a note, then closes and locks per the Phase 0 lock rule. The modal must say plainly what locking does.
- **Reopen** requires a note and writes an audit entry.
- Only one active year; the active year cannot be closed unless another is activated first.

#### 1.3 Settings pages

- `/admin/settings/general`: barangay profile fields (same as setup step 3), fiscal-year start month, `cases.enabled` switch, `agencies.registration_open` switch, report default format. Each setting shows its **pending badge** and a **Mark confirmed** button (admin only, audited).
- `/admin/settings/numbering`: one card per type (referral, case, summons): format input with tokens `{YEAR}`, `{YY}`, `{SEQ:n}`, live preview ("REF-2026-0001") from `NumberingService::preview`, reset policy radio (per fiscal year / never), read-only "next number". Validation: the format must contain `{SEQ:n}`. Buttons **Save**, **Reset to default**.
- `/admin/settings/pending`: **Pending confirmation** page. One table grouped by area (Settings, Positions, Case types, Case fields, Escalation destinations, Report templates, Signatories). Columns: Item, Current placeholder value, Questionnaire question (for example "Q22"), **Edit** link, **Confirm** button. Button **Confirm all in this group** (with confirm dialog). Button **Export register (CSV)**. Empty state: "Everything is confirmed".
- `/admin/settings/templates`: response templates CRUD. Table: Title, Kind, Category, Active. Form: title*, kind*, category, body* (supports `{tracking_code}`, `{office}`, `{date}` hints). Buttons **New template**, **Save**, **Preview**, **Deactivate**. Also expose `GET /admin/templates/json?kind=` for other screens to use (no integration into other pages required).

#### 1.4 Year awareness in existing screens

You are the **only** member allowed to touch these existing files, and only for the year filter and the case KPI cards: `Admin\ReportController@index` and `@exportCsv`, `Admin\DashboardController@index`. Add a "Fiscal year" select to their filters (default = `FiscalYearContext::viewing()`, option "All years"). Do not change any other behaviour. Fiscal-year stamping at report and referral creation is done in Phase 0, and the referral list year filter belongs to WS5. Also build the items assigned to WS1 in `SPEC_ADDENDUM.md`: global search (10.2), admin dashboard case KPIs (10.4), and backup and restore (10.5).

#### 1.5 Tests and done-when
Wizard happy path and each validation; resume after refresh; `/setup` closed after finishing; one active year; close/reopen with notes; locking blocks edits to a finalized record; numbering preview and save; pending page counts; year filter on queue and CSV; every action audited. **Done when** `app:empty` then the wizard produces a working system with an admin, profile, active year, personnel, and permissions in under five minutes.

---

### WS2: People, Access, Reports Center

**Goal:** the registry of employees and signatories, per-employee access control, and the official reports generator with signature blocks.

#### 2.1 Positions (`/admin/positions`)
Table: Name, Code, Signatory by default, Active, Pending badge. Buttons **Add position**, **Edit**, **Deactivate**. Delete is blocked when in use (message explains). Form: name*, code*, sort order, signatory by default, active.

#### 2.2 Personnel (`/admin/personnel`)
- **List** filters: search by name, position, status (Active/Inactive/All), "Signatories only". Columns: Name, Position, Term (start to end), Signatory (check), Login (check), Status. Buttons: **Add personnel**, **Edit**, **Deactivate** / **Reactivate**.
- **Form** fields: full name*, position*, contact number, email, term start, term end, signatory checkbox, signature image (PNG, max 1 MB, preview, optional), linked login (select existing user, or checkbox **Create login** with email), notes.
- **Deactivate** modal: term end date* and note*; if a login is linked, also deactivates the login and says so. The record is never deleted so past letters keep their signer.
- **Term of office:** when someone's `term_end` passes, the list shows an "Ended" badge; the same position can have a new person with a new `term_start`.

#### 2.3 Signatories (`/admin/signatories`)
A matrix: rows = document types from `config/documents.php`; columns = Prepared by, Noted by, Approved by; each cell a position select. Beneath each row, a read-only **"Currently resolves to"** column showing the person active today. Date picker "Show as of" re-resolves. Buttons **Save**, **Preview signature block**. Pending badge on unconfirmed rows.
**Implement `SignatoryResolver::forDocument`:** for each configured role, find the active `personnel` in that position whose term covers `$asOf` (latest `term_start` wins); return the array shape defined in Phase 0; return a labelled blank entry when nobody matches.

#### 2.4 Users and permissions (`/admin/users`)
- **List:** name, email, role badge (`admin`, `staff`, `agency`), preset/permission count, status, last login. Buttons **Add user**, **Edit**, **Deactivate**, **Reset password**. Agency users are shown read-only here (WS5 manages them).
- **Form:** name*, email*, role* (admin/staff), preset select (fills the checklist), grouped **permission checklist** (from `permissions.group`), linked personnel (optional), active toggle. New users get a generated temporary password shown once, with `must_change_password = true`.
- **Guards:** you cannot deactivate yourself; at least one active admin must remain; deactivated users cannot log in but their records stay; every change is audited with old and new permission lists.
- **Forced password change page** `/account/password` (`account.password.edit/update`): current password (skipped when forced), new password* (min 10), confirm*. After success, clears `must_change_password`.

#### 2.5 Reports Center (`/admin/reports-center`)
**Index:** cards for each active `report_templates` row (title, description, source, DRAFT badge if unconfirmed). Button **Open**. **History** link to `/admin/reports-center/history` (table of `generated_reports`: date, report, filters, format, generated by; **Download again** when a file was stored).
**Report page** `/admin/reports-center/{key}`: filter panel (Fiscal year defaulting to the viewing year, date range, plus the filters relevant to the report), buttons **Preview**, **Export PDF**, **Export Excel**, **Print**, **Reset filters**. Preview is an HTML page using the print layout: letterhead (barangay name, city, address, seal), title, period and fiscal year, the data table, totals, and the **signatory block** from `SignatoryResolver` using the template's `signatory_document_type`. Print and PDF show the draft watermark while the template is unconfirmed.
Build one base `ReportBuilder` class and one small class per report. **Seven reports** (keys): `problem_reports_summary` (by category, purok, status), `referral_register`, `case_summary` (by case type and status, resident vs non-resident counts), `summons_log`, `kp_accomplishment` (monthly, **draft layout**), `personnel_list`, `year_over_year` (compares fiscal years side by side). Every export writes a `generated_reports` row and an audit entry. Case reports require `cases.view` as well as `reports_center.view`.
PDF with `barryvdh/laravel-dompdf`; Excel with `openspout/openspout`. Reports must stream, not load everything into memory, and finish within 5 seconds for 5,000 rows.

#### 2.6 Tests and done-when
Personnel CRUD and term logic; deactivation cascades to login; resolver returns the right person by date and blank when none; permission checklist saves and `permission` middleware enforces it; last-admin guard; forced password flow; each report renders with demo data, respects fiscal year filter, exports PDF/Excel; unauthorized users get 403. **Done when** `/admin/reports-center` produces all seven reports with correct signatories for the demo data.

---

### WS3: Cases, intake and parties

**Goal:** a staff-encoded case file (blotter) with reusable person records, residency tagging, and the case lifecycle. Does **not** include summons, hearings, or escalation (WS4).

#### 3.1 Case list (`/admin/cases`)
Permission `cases.view`. Filters: Fiscal year (default viewing year), status, case type, purok, party residency (Resident/Non-resident/Unverified), assigned to, search (case number or party name). Columns: Case number, Filed on, Type, Complainant(s), Respondent(s), Status badge, **Summons counter** badge (for example "2 / 3", gold when 1 remaining, red **Eligible to escalate** when `SummonsCounterService::forCase()['eligible']`), Assigned to. Buttons **New case** (needs `cases.create`), **View**. Pagination 20, sortable by filed date. Empty state "No cases for FY 2026". Hide the module when `cases.enabled` is false or the feature flag is off.

#### 3.2 New and edit case (`/admin/cases/create`, `/admin/cases/{id}/edit`)
One page, four sections. Required/visible fields come from `case_field_configs` (never hardcode):
- **A. Case details:** case type*, filed on* (default today), incident date, incident location, purok, narrative* (min 20 characters), source (Walk-in default, Online report, Other).
- **B. Complainant(s)** and **C. Respondent(s)** (repeatable, at least one each) and **D. Witnesses** (optional). Each section has a **person finder**: type a name, results from `GET /admin/parties/search?q=` show name, age, purok, residency badge; buttons **Use this person** or **Add new person**.
- **New person form** (inline): full name*, alias, birth date, sex, civil status, occupation, contact number, address, purok, **Residency status*** (Resident / Non-resident / Unverified), **How verified** (select: Barangay ID or certificate, Household list, ID with Calarian address, Purok leader confirms, Other) plus a note, and for non-residents: home barangay and city. ID type and number optional.
- **Possible duplicate guard:** `PartyMatchingService` compares normalized name plus birth date or contact number; when it finds a likely match a modal shows "This may be the same person" with buttons **Use existing person** and **Create anyway**. The party's residency at filing is copied into `case_parties.residency_at_filing`.
- Buttons at the bottom: **Save case**, **Save and issue summons** (redirects to WS4's `admin.cases.summons.create`), **Cancel**. The case number comes from `NumberingService::next('case')` and `fiscal_year_id` from `FiscalYearService::forNewRecord()`. Everything saves in one transaction.

#### 3.3 Case page (`/admin/cases/{id}`)
Phase 0 created the skeleton with tabs. **You own the header, Overview, Parties, Notes, Timeline tabs.** WS4 owns Summons, Hearings, Escalation tabs (do not edit their partials).
- **Header:** case number, status badge, case type, fiscal year, confidentiality banner ("Confidential. Authorized staff only"), summons counter card (include WS4's partial).
- **Action bar:** **Edit case**, **Change status** (modal: new status from allowed transitions, note*), **Assign to** (select staff with `cases.view`), **Add note**, **Print case summary** (print layout, draft watermark).
- **Overview:** narrative, incident details, source (link to the original problem report when converted).
- **Parties:** table with role, name, residency badge, contact; button **Edit person** (opens party edit), **Remove from case** (note*).
- **Notes:** staff-only internal notes (`case_notes`).
- **Timeline:** merged list of `case_status_logs` and notable audit events.

#### 3.4 Parties directory (`/admin/parties`)
Search and list persons (name, residency, purok, number of cases). **Party profile** `/admin/parties/{id}` shows details and every case where the person was complainant or respondent (repeat-respondent history). Buttons **Edit**, and **Verify residency** (set status, basis, writes verifier and time, audited). Stretch: **Merge duplicate persons**.

#### 3.5 Convert a problem report to a case
Button **Convert to case** appears on the problem report review page through the hook added in Phase 0 (`admin.cases.partials.convert-button`). It opens the case create form prefilled (narrative, location, purok, `source_report_id`, `source = online_report`). The resident's reporter details are **not** copied automatically; staff re-enter parties. After saving, the original report gets a staff-visible link to the case. This does not change the problem report's status unless staff do so.

#### 3.6 Admin settings you own
- `/admin/settings/case-types`: CRUD with pending badge (name, description, order, active).
- `/admin/settings/case-fields`: table of `case_field_configs` grouped by entity (party, case, summons) with toggles **Required** and **Visible**, plus "applies to" (all/resident/non-resident). Button **Save**. Pending badge.

#### 3.7 CaseStatusService and rules
Implement the Phase 0 contract using the draft transition table in `PHASE0_FOUNDATION_TASK.md` section 9. Every transition requires a note, writes `case_status_logs` and an audit entry, and is staff-only. Recovery transitions (`escalated`, `settled`, `dismissed`, `withdrawn` to `in_mediation`) also require `cases.edit`. Finalized cases of a locked year are read-only through `FiscalYearGuard`.

#### 3.8 Tests and done-when
Validation per `case_field_configs`; person search; duplicate guard; resident tag snapshot; convert-from-report prefill; case numbers reset per fiscal year; every status transition (allowed and refused); permission and confidentiality (a user without `cases.view` gets 403 everywhere, agency users get 403); audit entries. **Done when** an encoder can file a case with a resident complainant and a non-resident respondent in under three minutes.

---

### WS4: Cases, summons, hearings, escalation

**Goal:** track summons per case, flag eligibility after the configured limit, record hearings and outcomes, and let an official escalate a case with a documented decision.

> You start day one against the Phase 0 skeleton and the demo cases. You own everything in `admin/case-actions/`.

#### 4.1 Summons tab and counter
- **Counter card** (partial `summons-counter`): "Summons issued: 2 of 3" with a progress bar; states: normal, "1 more before escalation eligibility", **"Eligible for escalation"** (red). Implement `SummonsCounterService::forCase()` exactly as the Phase 0 comment describes (non-voided summons; limit from `cases.summons_limit`; pending badge when unconfirmed).
- **Summons table:** Seq, Number, Respondent, Issued on, Hearing date/time, Service result, Attendance, Actions. Row buttons: **Record service**, **Record attendance**, **Print**, **Void**.
- **Issue summons** (`admin.cases.summons.create`): respondent* (select from the case's respondents), summons number (auto-preview from `NumberingService::preview('summons')`, assigned on save), issued on* (default today), hearing date and time*, venue (default "Barangay Hall"), delivered by (select active personnel), remarks. Buttons **Issue summons**, **Issue and print**, **Cancel**. If the limit is already reached, show a warning "Limit reached. Further summons need a reason" and require an **override note** (warn, do not block; officials decide). Optional checkbox **Also add to hearing schedule**.
- **Record service** modal: served on*, result* (Received, Refused, Not found, Other), served by, remarks. **Record attendance** modal: Appeared, No-show, Rescheduled, note. **Void** modal: note* (voided summons do not count toward the limit; the row stays visible, struck through).
- **Printable summons** (`.print`): print layout, case number, respondent, hearing details, signature block from `SignatoryResolver::forDocument('summons')`, draft watermark while the template or signatories are unconfirmed.

#### 4.2 Hearings
- **Hearings tab** on the case: list with scheduled time, venue, presided by, status, outcome. Buttons **Schedule hearing**, **Reschedule**, **Record outcome** (held, cancelled, reset + note). Hearing outcome and attendance feed the case timeline.
- **Hearing schedule** `/admin/hearings`: **Today's hearings** panel at the top, then a table of upcoming hearings with filters (date range, status, case type). Each row links to its case. Empty state. Email reminders are **off** (`hearing_email_reminders` flag); build the code path but keep it disabled, and note the privacy rule: only case number and date, to parties with an email.

#### 4.3 Settlement
Button **Record settlement** on the case (modal: settled on*, terms*, optional agreement upload PDF/JPG max 5 MB). Calls `CaseStatusService::transition(..., 'settled')`. If WS3's service is not merged yet, develop against the unit-mocked service.

#### 4.4 Escalation tab
- Card **"Escalation eligibility"** explaining in plain words: "This case has 3 of 3 summons. Staff may escalate. The system never escalates by itself."
- Button **Escalate case** (needs `cases.escalate`): enabled when eligible; otherwise opens the form with a required **override note**. Form fields: destination* (from `escalation_destinations`), decided by* (active personnel; default the person in `cases.escalation_decider_position`), reason*, override note (required below the limit), documents to prepare (checkboxes: Endorsement letter, Copy of blotter, Certificate), **Send through the system as a referral** (visible only when the destination has an office and `case_referral_handoff` is on).
- On submit (one transaction): create `case_escalations` with `summons_count_at_time`, call `CaseStatusService::transition(..., 'escalated')`, optionally `ReferralService::createFromCase`, write audit entry. 
- **Endorsement letter** (`.letter`): print layout, case summary (no unnecessary personal data), summons history, signature block from `SignatoryResolver::forDocument('case_endorsement')`.
- `/admin/settings/escalation-destinations`: CRUD with pending badge (name, linked office, order, active).

#### 4.5 Tests and done-when
Counter math (voided excluded, limit from setting, scope of statuses); issuing with and without override; numbering per fiscal year; service and attendance recording; void; hearing scheduling and outcomes; settlement; escalation guards (eligible, below limit with override note, missing permission 403); transaction rollback if the status transition fails; audit entries; printable views render with blank signature lines when the resolver returns `[]`. **Done when** the demo case with three summons shows "Eligible for escalation" and an official can escalate it end to end.

---

### WS5: Agency accounts and referral updates

**Goal:** let outside agencies (such as the police) register, be approved by an administrator, and see and update only the referrals addressed to them.

#### 5.1 Agency registration (public)
`/agency/register` (throttle 5 per minute). Fields: agency name* (select from external offices, or **Not listed** with a text field), your full name*, position*, official email*, contact number*, **ID or endorsement letter upload*** (JPG/PNG/PDF, max 5 MB, stored on a **private** disk), password* (min 10) and confirm*, consent checkbox* (privacy notice text from `privacy.notice_text`). Button **Submit for approval**. Creates a `users` row (`role = agency`, `is_active = false`), an `agency_registrations` row (`pending`), sends the Laravel email-verification link, and shows `/agency/pending` ("Your request is waiting for approval"). Duplicate emails are rejected. Registration can be closed with `agencies.registration_open`.

#### 5.2 Admin approval (`/admin/agencies`)
Permission `agencies.manage`. Tabs: **Pending**, **Approved**, **Rejected**, **Suspended**, each with a count. Row columns: agency (claimed), contact person and position, email (verified badge), submitted date, **ID document** (button **View document** streams the private file through an authenticated route, never a public URL). Detail page buttons: **Approve** (modal: link to an existing external office or **Create office**, confirm), **Reject** (reason*), **Suspend** (reason*), **Reinstate**, **Reset password**. Approval sets `is_active = true`, `office_id`, reviewer, time, and emails the applicant (no personal data beyond name). Every decision is audited. Multiple approved users may share one office.

#### 5.3 Login and portal
Agency users log in at the common `/login`. Pending, rejected, suspended, or unverified accounts are stopped with a clear message. After login they land on `/agency/referrals`.
- **Referral list** (`agency.referrals.index`): only referrals where `office_id` equals the user's office. Columns: Referral number, Date sent, Category, Status badge, Follow-up date. Filters: status, fiscal year. Empty state.
- **Referral page** (`agency.referrals.show`): referral number and status, the letter text, incident narrative, purok, landmark, and map link, plus an update thread. **Never show** reporter name, contact, email, photos, staff names, internal notes, or other referrals. Accessing a referral of another office returns 404.
- **Agency actions (buttons):** **Acknowledge receipt**, **Mark action taken** (note*), **Decline** (reason*), **Close** (outcome select: Resolved, Declined, No response + note), **Send message to barangay**. Allowed transitions: `sent` to `acknowledged` to `action_taken` to `closed`; Decline closes with outcome `declined`. Each action creates a `referral_updates` row, updates `referrals.status`, and notifies the assigned staff by dashboard badge and email (referral number and link only).
- Every view and action is written to the audit log (`agency.viewed_referral`, and so on). Throttle actions.

#### 5.4 Staff side of referrals (extend, do not rewrite)
You may edit `Admin\ReferralController`, `ReferralService`, `resources/views/admin/referrals/*`, and the referral letter view, **only** for the items below:
- Add the fiscal-year filter to `Admin\ReferralController@index` (same convention as WS1: default = viewing year, option "All years").
- Add **Send to agency portal** button (status `prepared` to `sent`, sets `sent_at` and `follow_up_at`, emails the agency users: referral number and link only, only if the office has approved agency users).
- Show agency updates and messages on the staff referral page; button **Reply**. 
- **Referral letter:** replace the signature area with `SignatoryResolver::forDocument('referral_letter')` output (blank lines if empty), seal and letterhead from settings, and show the draft watermark while the signatories are unconfirmed.
- Implement `ReferralService::createFromCase($case, $destination, $by)`: creates a referral (numbered via `NumberingService`, stamped to the fiscal year) with a limited case summary, status `prepared`. Then tell the lead to enable `case_referral_handoff`.
- Referral columns: add "Agency portal status".

#### 5.5 Hardening checklist (must be demonstrated in tests)
Throttled registration and login; email verification required; private file storage; authorization policy per office; no PII leakage in agency views and emails; suspended users are logged out on their next request; a staff user cannot open `/agency/*` and an agency user cannot open `/admin/*` (403).

#### 5.6 Tests and done-when
Registration validation and duplicate email; approval, rejection, suspension flows; scoped referral visibility (agency A cannot see agency B); each allowed and forbidden status transition; thread messages; emails contain no PII; staff send-to-portal; letter shows signatories. **Done when** the demo police account sees only its referral, acknowledges it, and the staff user sees the update.

---

## 7. Schedule (suggested, 4 weeks)

| When | Lead | WS2 to WS5 |
| :--- | :--- | :--- |
| **While Phase 0 runs (about 3 days)** | Runs `PHASE0_FOUNDATION_TASK.md` with the AI | **No coding on the repo.** Read all docs. WS2: collect sample letterheads and report layouts. WS3: sketch the case form screens. WS4: sketch the summons, hearing, and escalation screens. WS5: draft the agency registration screens and the privacy notice text. Everyone: send the questionnaire to the secretary and ask the adviser whether the case and summons features are required |
| Week 1 | Phase 0 merged by day 3, then WS1 setup wizard | Branch from `main`, build the first page of each workstream |
| Week 2 | WS1 fiscal year and settings pages | Core pages and rules |
| Week 3 | Reviews and integration, flip `case_referral_handoff` after WS5 | Finish features, tests, hand-offs, docs |
| Week 4 | Integration sprint and demo script | Bug fixes from the integration sprint |
| After the secretary answers | Run the placeholder swap (section 8) | Update the items assigned in `docs/PLACEHOLDERS.md` |

---

## 8. When the secretary answers

1. Open `/admin/settings/pending` (the live register) next to `docs/PLACEHOLDERS.md`.
2. For each answered question, change the value (a setting edit, a seed row, or a template swap), then press **Confirm**. The "pending" badge disappears.
3. **Template swaps** (official forms and report layouts) go to the owner listed in `PLACEHOLDERS.md`: WS2 for reports and signatories, WS4 for summons and escalation papers, WS5 for the referral letter.
4. **Design changes** (for example, if another office such as the Lupon owns case handling, or the escalation rule is different) are brought to the lead first. They may change roles, permissions, or the case lifecycle in `WORKFLOW.md`.
5. If the adviser or secretary says the case module is out of scope, set `cases.enabled = false` and hide the nav items. No code removal is needed.

---

## 9. Demo order for the adviser

1. `app:empty`, open the system, complete the **setup wizard** (WS1).
2. Show fiscal year, personnel, signatories, permissions (WS1, WS2).
3. File a problem report and route it (existing system).
4. File a case with a resident complainant and non-resident respondent, issue three summons, see the counter, escalate (WS3, WS4).
5. Register an agency, approve it, send a referral, watch the agency acknowledge it (WS5).
6. Open the Reports Center and export a report with the signatory block (WS2).
7. Show the **Pending confirmation** page to explain what is waiting for the barangay (WS1).
