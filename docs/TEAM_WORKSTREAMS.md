# TEAM WORKSTREAMS: 5 members, no overlap

Barangay Problem Reporting and Management System, Barangay Calarian, Zamboanga City.
Group 3 (SynohDev).

> [!NOTE]
> **Implementation Status (Verified 2026-11-24)**:
> All six workstreams (WS1 through WS6) and the Phase 0/0.5 foundations have been **fully implemented, tested (288 tests, 100% green), and merged into the `dev` integration branch**. This file is maintained as the authoritative historical record of scope, feature ownership, and architectural partitioning.

---

## 1. How the work is split

```
PHASE 0 (lead + AI, first)     FOUNDATION: schema, services, routes, layout, UI kit, seeders, tests
        |
        v   merged to main, then everyone branches from it
+-----------+-----------+-----------+-----------+-----------+--------------------+
|   WS1     |   WS2     |   WS3     |   WS4     |   WS5     |   WS6 (Stretch)    |
| Setup &   | People &  | Cases:    | Cases:    | Agencies  | Legacy Import      |
| Fiscal    | Access &  | intake &  | summons,  | & Referral| (Last 4 years of   |
| Year &    | Reports   | parties   | hearings, | updates   | Excel/CSV records) |
| Settings  | Center    |           | escalation|           |                    |
+-----------+-----------+-----------+-----------+-----------+--------------------+
        |
        v
 Integration sprint -> wait for secretary blank forms -> swap placeholders -> demo
```

**Why this split avoids overlap:** every workstream owns its own route file, controllers, views folder, test folder, and migration timestamp range. Everything shared (schema, services, layout, UI components, nav, config) is built once in Phase 0 and is **read-only** for the workstreams.

### Assignment

| WS | Focus Area | Owner / Member |
| :--- | :--- | :--- |
| Phase 0 + WS1 | Foundation, Setup, Fiscal Year, Settings, Global Search, Backups | Lead |
| WS2 | People, Access, Personnel, Signatories, Reports Center | Team Member |
| WS3 | Cases: intake, blotter vs complaint, parties, duplicate checks, merge | Team Member |
| WS4 | Cases: summons, hearings, deadlines, settlement, CFA, escalation | Team Member |
| WS5 | Agency accounts, portal, referrals, attachments | Team Member |
| WS6 (Stretch) | Legacy Excel/CSV import (last 4 record years) [D2, A Q8] | Whoever finishes first among WS2/WS5 |

WS3 and WS4 are the heaviest core case flows. WS4 starts against the Phase 0 skeleton and demo cases. WS6 starts only after WS3 merges (needs `PartyMatchingService` and case models).

---

## 2. Ownership matrix (who may edit what)

| Area | Owner | Others |
| :--- | :--- | :--- |
| `database/migrations/2026_11_22_0001*` (Phase 0 schema) | Lead | Read only |
| New migrations after Phase 0 | The WS that needs them, using its **timestamp range** below | Never edit another WS's migration |
| `app/Services/` Phase 0 services (Settings, Numbering, FiscalYear*, Permission, AuditLogger) | Lead | **Call only.** Request changes via the lead |
| `config/*`, `bootstrap/app.php`, `routes/web.php`, `layouts/*`, `components/ui/*`, `config/navigation.php` | Lead | Read only |
| `RequiredDataSeeder`, `DemoDataSeeder` | Lead | Ask the lead to add seed rows, or add your own seeder class `Ws{n}ExtraSeeder` |
| `docs/PROJECT.md`, `DATABASE.md`, `API.md`, `WORKFLOW.md`, `ARCHITECTURE.md`, `TASKS.md` | Lead | Write notes in `docs/modules/ws{n}-*.md` instead |
| `routes/setup.php`, `routes/settings.php` | WS1 | |
| `routes/people.php`, `routes/reports_center.php` | WS2 | |
| `routes/cases.php` | WS3 | |
| `routes/case_actions.php` | WS4 | |
| `routes/agency.php` | WS5 | |
| `routes/imports.php` | WS6 | |

**Migration timestamp ranges** (prevents ordering clashes):
- WS1: `2026_12_01_10NNNN_`
- WS2: `2026_12_01_20NNNN_`
- WS3: `2026_12_01_30NNNN_`
- WS4: `2026_12_01_40NNNN_`
- WS5: `2026_12_01_50NNNN_`
- WS6: `2026_12_01_60NNNN_`

**Folders per workstream**

| WS | Controllers | Views | Tests | Services |
| :--- | :--- | :--- | :--- | :--- |
| WS1 | `Http/Controllers/Setup`, `Admin/Settings` | `views/setup`, `views/admin/settings` | `tests/Feature/Setup` | `app/Services/Setup/` |
| WS2 | `Admin/People`, `Admin/ReportsCenter` | `views/admin/personnel`, `admin/positions`, `admin/signatories`, `admin/users`, `admin/reports-center`, `views/account` | `tests/Feature/People` | `app/Services/People/`, `app/Services/Reporting/` |
| WS3 | `Admin/Cases` | `views/admin/cases`, `admin/parties` | `tests/Feature/Cases` | `app/Services/Cases/` |
| WS4 | `Admin/CaseActions` | `views/admin/case-actions` | `tests/Feature/CaseActions` | `app/Services/CaseActions/` |
| WS5 | `Agency`, `Admin/Agencies`, `Admin/Referrals` | `views/agency`, `views/admin/agencies`, `views/admin/referrals` | `tests/Feature/Agency` | `app/Services/Agency/` |
| WS6 | `Admin/Imports` | `views/admin/imports` | `tests/Feature/Imports` | `app/Services/Imports/` |

---

## 3. Contracts between workstreams

These are the only places two workstreams touch. Each side codes against the **signature**, so nobody waits for the other.

| Provider | Contract | Consumer | Until the provider finishes |
| :--- | :--- | :--- | :--- |
| Phase 0 | `SettingsService`, `NumberingService`, `FiscalYearService`, `PermissionService`, `AuditLogger` | Everyone | Fully working from day one |
| WS2 | `SignatoryResolver::forDocument($type, $asOf, ?BarangayCase $case = null)` | WS4 (summons, CFA, endorsement), WS5 (referral letter), WS2 reports | Returns `[]`; views print blank signature lines |
| WS3 | `CaseStatusService::transition($case, $to, $note, $by)` | WS4 (escalation, settlement) | Phase 0 stub throws `LogicException`; WS4 develops against demo case and unit-mocks the service |
| WS4 | `OverdueService::forCase($case)` | WS3 (list deadline badge) | Stub returns not overdue |
| WS5 | `ReferralService::dueForFollowUp()` | WS4 (secretary dashboard) | Stub returns empty collection |
| WS1 | `GlobalSearchService::search($q, $user)` | Header search box (`admin.search`) | Stub returns empty groups across all years |
| Phase 0 | `BarangayCase::visibleTo($user)` scope | WS2, WS3, WS4 | Real from day one; **every case query must use it** |
| WS3 | `BarangayCase` model, case show page skeleton, `case_field_configs` reads | WS4, WS6 | Already exists from Phase 0 |
| WS4 | `SummonsCounterService::forCase($case)` | WS3 (list badge, case header) | Returns array with `issued`, `limit` (null), `eligible` (false) |
| WS5 | `ReferralService::createFromCase($case, $destination, $by)` | WS4 (escalation handoff) | Hidden behind `config('features.case_referral_handoff')`; WS4 flips flag only after WS5 merges |
| WS1 | `FiscalYearGuard`, year switcher, year filter conventions | WS2 reports, WS3 lists, WS5 referrals | Provided by Phase 0 |

---

## 4. Git and merge rules

- Branches: `feature/ws1-setup`, `feature/ws2-people-reports`, `feature/ws3-cases`, `feature/ws4-case-actions`, `feature/ws5-agency`, `feature/ws6-import`. Branch from `main` **after** Phase 0 is merged.
- Each member pushes **only to their own branch**. Never push to another member's branch.
- **Members never merge into `main`, never push to `main`, and do not open pull requests.** Only the lead checks and merges branches into `main`.
- Commit small and often, with the workstream prefix in the message (for example, `WS2: add personnel list page`). Push at least once a day.
- Before telling the lead a feature is ready: `php artisan test`, `./vendor/bin/pint --test`, no edits outside your owned paths.
- **Merge order if two branches conflict:** WS1, WS2, WS3, WS4, WS5, WS6.

**Ready checklist (member, before telling the lead)**
- [ ] Only files in my owned paths changed (list any exceptions and who approved)
- [ ] Screens match `docs/UI_UX_SPEC.md` (layout, button placement, table columns)
- [ ] Tests added in my test folder; full suite green
- [ ] Pint clean
- [ ] My branch is up to date with `main` and pushed

**Review checklist (lead, before each merge)**
- [ ] Only files in the member's owned paths changed
- [ ] Screens match `docs/UI_UX_SPEC.md` (layout, button placement, table columns)
- [ ] Tests added in their test folder; full suite green
- [ ] Pint clean
- [ ] Every create, update, delete, approval, and status change writes an audit log through `AuditLogger`
- [ ] Every placeholder value is read from settings or seed tables, not hardcoded
- [ ] Draft forms and print views show `<x-ui.draft-watermark>` while their `is_confirmed` is false
- [ ] Empty states, validation errors, and a 403 for missing permission all checked by hand
- [ ] Screens checked at 375 px and 1280 px wide

---

## 5. Rules every workstream follows

1. **Permissions:** protect every route with `permission:{slug}`. Never check `role === 'staff'` in new code.
2. **Fiscal year:** every list defaults to viewing year (`FiscalYearContext::viewing()`); every new record calls `FiscalYearService::forNewRecord()`; edits to finalized records call `FiscalYearGuard::assertWritable()`.
3. **Numbers:** use `NumberingService::next()`. Never concatenate numbers yourself.
4. **Confirmation:** show `<x-ui.pending-badge>` next to any value that is unconfirmed.
5. **Privacy:** parties and case data are sensitive (Data Privacy Act, RA 10173). Never send party data to Gemini or in emails. Emails contain only case number, date, and a link or instruction.
6. **UI:** use the `x-ui.*` components, FontAwesome icons, Tailwind tokens. Follow `docs/UI_UX_SPEC.md`.
7. **AI stays advisory:** "The AI suggests, the rules route, the officials decide." No automatic status changes.
8. **Thin controllers:** logic in services, validation in Form Requests, transactions around multi-table writes.

---

## 6. Workstream briefs

UI reference: `docs/UI_UX_SPEC.md` section 4.

---

### WS1: Setup, Fiscal Year, Settings (Lead)

UI reference: `docs/UI_UX_SPEC.md` section 4 (WS1, pages 4.1 to 4.9)

**Goal:** fresh install guides admin through setup, runs scoped to active fiscal year, guaranteed backups and cross-year search.

#### 1.1 Setup wizard (`/setup`)
- 7 steps: Welcome, Administrator account, Barangay profile, Fiscal year, Positions & personnel, Access presets, Review & finish.
- Step 6 uses built presets (`staff_full`, `case_encoder`, `reports_analyst`) plus `case_reviewer` [R8, 5.6].
- Resumable via `system.setup_step`.

#### 1.2 Fiscal years (`/admin/settings/fiscal-years`)
- Table with built statuses: `active`, `inactive`, `closed`, `locked` [R6].
- Close year modal lists carry-overs and requires a note. Locked year enforces immutability via `FiscalYearGuard`.
- Year switch view route: `admin.fiscal-years.switch-view`.

#### 1.3 Settings pages
- `/admin/settings/general`: profile, `cases.enabled`, `agencies.registration_open`, default print format.
- `/admin/settings/numbering`: formats with `{YEAR}`, `{YY}`, `{MM}`, `{SEQ:n}` [C4]. Case preview `11-001-2026` [A Q9]. Reset policy is strictly per fiscal year (`never` option removed) [R7].
- `/admin/settings/calendar`: holiday management table for `calendar.holidays` (excludes weekends & holidays from working day calculations).
- `/admin/settings/pending`: pending confirmation register with real 43-question v2 numbering [Section 3].
- `/admin/settings/templates`: response templates CRUD (`title`, `kind`, `created_by` [R18]).

#### 1.4 Guaranteed deliverables (C10)
- **Global search (`admin.search`)**: Searches across **all fiscal years** by case number (with/without hyphens), tracking code, party name, personnel [A Q8, C10].
- **Backups (`admin.backups.*`)**: `php artisan app:backup` archives DB + private/public storage to private disk; UI offers manual run and download.
- **Admin dashboard case KPIs**: Cards Open cases, Cases filed, Settled, Ready for next step, Avg days to settle, Overdue cases.

---

### WS2: People, Access, Reports Center

UI reference: `docs/UI_UX_SPEC.md` section 4 (WS2, pages 4.10 to 4.20)

**Goal:** registry of personnel and signatories, granular role-preset management, and official DILG/KP reports generator with signature blocks.

#### 2.1 Positions & Personnel (`/admin/positions`, `/admin/personnel`)
- Positions and signatories per `PLACEHOLDERS.md` B1, B2.
- Lupon members get accounts via position `lupon_member` and preset `case_encoder` [D1, A Q15].
- Deactivation preserves audit trail and past signature references.

#### 2.2 Signatories (`/admin/signatories`)
- Matrix for document types from `config/documents.php`.
- `SignatoryResolver::forDocument($type, $asOf, ?BarangayCase $case = null)` [5.4, 5.5]. Resolves case-specific Pangkat Chairman for CFA [A Q5].

#### 2.3 Users and permissions (`/admin/users`)
- Granular permissions with 18 built slugs. Presets: `staff_full`, `case_encoder`, `reports_analyst`, and `case_reviewer`.
- Supports two system administrators (Captain, Secretary) [A Q6].

#### 2.4 Reports Center (`/admin/reports-center`)
- Confirmed reports:
  - `kp_accomplishment`: Yearly to DILG (filter by year, optional month) [A Q32].
  - `case_summary`: Monthly summary by case type to DILG [A Q32].
  - `purok_category_matrix`: Problems per purok and category [A Q34].
  - `problem_reports_summary`, `referral_register`, `summons_log`, `personnel_list`, `year_over_year`.
- Output: Print on paper is default action (`reports.default_format = print`) [A Q33]. Respects `reports.paper_size`.

---

### WS3: Cases, intake and parties

UI reference: `docs/UI_UX_SPEC.md` section 4 (WS3, pages 4.21 to 4.25)

**Goal:** case docketing (blotter vs complaint), reusable parties, duplicate detection, and canonical status engine.

#### 3.1 Case intake (`/admin/cases/create`)
- Record type selection first: **Blotter-only** (`blotter`) or **Formal Complaint** (`complaint`) [A Q18, C3].
- Trimmed party form: Full name, alias, birth date/age, contact, address, purok, residency status (`resident`, `non_resident`, `transient`, `unverified`), verification method [A Q16, Q17]. Extra fields in `parties.extra`.
- Case types with two flags: `is_confidential_default` (pre-ticks confidential for BCPC/VAWC) and `uses_kp_timeline` (skips mediation clock for non-KP cases) [A Q7, C6].
- Case number format `{MM}-{SEQ:3}-{YEAR}` via `NumberingService` [A Q9].

#### 3.2 Duplicate detection & Party merging
- **Party search**: Finds existing persons during entry.
- **Case duplicate warning**: On save, checks if matching complainant and respondent have an open case within 30 days or identical docket number [A Q2, C8]. Warns and allows opening existing or proceeding.
- **Party merge (`/admin/parties/{id}/merge`)**: Expected deliverable. Merges duplicate party records with side-by-side preview and audit logging.

#### 3.3 Case status & List
- List shows deadline state from `OverdueService` ("Day X of 15", gold due soon, red "Overdue X d", grey "Ready for next step") and plain summons count.
- Status transitions follow `WORKFLOW.md` section 8 via `CaseStatusService`.

---

### WS4: Cases, summons, hearings, escalation

UI reference: `docs/UI_UX_SPEC.md` section 4 (WS4, pages 4.26 to 4.30)

**Goal:** track summons and hearings, manage 15-working-day mediation/conciliation deadlines, record settlements, and handle complainant-initiated escalations with CFA printables.

#### 4.1 Summons & Deadlines
- **No summons limit**: Counter is for administrative tracking only [A Q20, C1]. No cutoff or override notes required.
- **Deadlines**: 15 working days for mediation, 15 working days for conciliation (+15d extension) [A Q25]. Calculated using `WorkingDays` engine and `calendar.holidays`.
- Service results: `received`, `refused`, `not_found`, `other` [A Q21, R10]. Attendance: `appeared`, `failed_to_appear`, `pending`.

#### 4.2 Secretary dashboard (`/admin/secretary`)
Operational reminders per Q27: Today's hearings, Hearings in next 7 days, Summons awaiting service, Cases near deadline, Overdue cases, Cases ready for next step, Referrals awaiting follow-up.

#### 4.3 Settlement (`admin.cases.settle`)
Captures `settled_on`, `terms`, optional monetary `settlement_amount`, and uploaded signed agreement PDF [A Q24]. Transitions case to `settled`.

#### 4.4 Escalation & CFA (`admin.cases.escalate.*`)
- Complainant decides whether and when to escalate [A Q22, Q23, C2].
- Soft warning if 15 working days not elapsed; officials decide.
- Generates printable Certification to File Action (CFA) signed by Pangkat Chairman [A Q5] and Endorsement Letter signed by Punong Barangay.
- Hands off to agency via `ReferralService::createFromCase` if `case_referral_handoff` is enabled [A Q29, C7].

---

### WS5: Agency accounts and referral updates

UI reference: `docs/UI_UX_SPEC.md` section 4 (WS5, pages 4.31 to 4.38)

**Goal:** portal for partner agencies (PNP, CSWDO, Utilities) to receive referrals, download case papers, and post progress updates.

#### 5.1 Registration & Approvals (`/agency/register`, `/admin/agencies`)
- Registration requires ID document or official endorsement letter [A Q31].
- Approved by Barangay Secretary (`agencies.manage`) [A Q30].
- Pilot agencies: PNP Station 8, CSWDO, water utility, electric utility [A Q28].

#### 5.2 Agency portal (`/agency/referrals`)
- Agencies see only referrals addressed to their office.
- Actions: Acknowledge receipt, Mark action taken, Post updates [A Q29].
- Case handoff documents accessible via `referral_attachments` on private disk [C7].

#### 5.3 Staff referrals management (`/admin/referrals`)
- Follow-up interval defaults to **3 days** (`sent_at + referral.follow_up_days`) [A Q12, C5].
- Formal referral letter with `SignatoryResolver::forDocument('referral_letter')`.

---

### WS6 (Stretch): Legacy File Import

UI reference: `docs/UI_UX_SPEC.md` section 4 (WS6)

**Goal:** import the last 4 record years of historical cases, blotter entries, and parties from Excel/CSV [D2, A Q1, Q8].

- **Owner**: Whoever finishes first between WS2 and WS5. Starts after WS3 merges.
- **Branch**: `feature/ws6-import`, route file `routes/imports.php`, permission `imports.manage`, flag `legacy_import`.
- **Scope**: Excel or CSV files. No AI/OCR (protects party PII). Blank downloadable Excel template provided.
- **Flow**: Upload (private disk) > Column Mapping (saveable) > Validation > Preview with duplicates > Chunked Commit with **Roll Back Batch** option.
- **Integrity**: Retains original docket numbers (never consumes active sequences); auto-creates and locks historical fiscal years.

---

## 7. Schedule

| Timeline | Lead | WS2 to WS5 |
| :--- | :--- | :--- |
| **Phase 0 & Alignment** | Run Phase 0 and Phase 0.5 foundation alignment with AI | Study briefs and `docs/UI_UX_SPEC.md`. Collect official forms [D4] |
| **Week 1** | WS1 Setup wizard, fiscal year, numbering with `{MM}` | Branch from `main`. WS2 personnel/signatories; WS3 case intake; WS4 summons/deadlines; WS5 agency portal |
| **Week 2** | WS1 Settings, calendar holidays, backups, global search | WS2 reports; WS3 party merge & duplicate checks; WS4 settlement & CFA; WS5 referral attachments |
| **Week 3** | Integration sprint, enable `case_referral_handoff` | Complete features, unit tests, printables with blank resolver fallback |
| **Week 4** | WS6 Legacy import (stretch), demo rehearsal | Bug fixes, visual polish per `docs/UI_UX_SPEC.md` |

---

## 8. Demo order for the adviser

1. `app:empty`, open system, complete **setup wizard** (WS1).
2. Show fiscal year, calendar holidays, personnel, signatories, permissions (WS1, WS2).
3. File a problem report and route it (existing system).
4. File a blotter entry, then file a formal complaint with resident complainant and non-resident respondent (WS3).
5. Issue summons, view deadline panel ("Day X of 15"), record hearing attendance (WS4).
6. Settle case or record complainant escalation, generate CFA signed by Pangkat Chairman (WS4).
7. Dispatch escalated case to agency portal; agency logs in, downloads papers, and posts update (WS5).
8. Open Reports Center: export DILG yearly KP accomplishment and monthly case summary with real signature blocks (WS2).
9. Test cross-year global search and run system backup (WS1).
10. Preview blank Excel template and historical import tool (WS6).
