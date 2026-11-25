# First-Run System Findings & Fresh Installation Audit

**Barangay Problem Reporting & Katarungang Pambarangay System**  
*Evaluation Report of Initial Deployment, Fresh Installation Simulation, and Day-One Administrator Friction*  
*Date of Audit: November 25, 2026*  
*Methodology: Direct execution and trace against empty database seeded strictly with `RequiredDataSeeder` (0 demo users, 0 demo cases, 0 demo reports).*

---

## 1. Executive Summary

A clean installation simulation was conducted starting from an empty database running Laravel 13 on PHP 8.4 with SQLite/MySQL. The baseline seed loaded 21 permissions, 5 positions, 35 settings (30 unconfirmed), 16 signatory assignments, 6 case types, 6 field configs, 4 escalation destinations, 3 report templates, 6 categories, 7 puroks, 6 offices, 6 routing rules, 6 emergency contacts, 6 response templates, and 1 active fiscal year (FY 2026). Zero users or personnel records were present on day one.

While the primary first-run workflow (Setup Wizard -> Admin Sign-in -> Setup Locking -> Resident Portal Activation) succeeds cleanly on SQLite, several friction points, missing UI capabilities, and ordering traps were identified that impact a first-time barangay secretary.

---

## 2. Comprehensive Findings Matrix

| ID | Severity | Page / Route | Action Attempted | Actual Result | Expected Result | Suggested 1-Line Fix |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **F-01** | **CRITICAL** | CLI (`migrate:fresh`) | Run `php artisan migrate:fresh` on MySQL | Fails with `SQLSTATE[42S22]: Unknown column 'first_meeting_on' in 'cases'` during migration `2026_11_24_222928` | Migration completes cleanly regardless of database engine | Move `mediation_started_at` definition after column `is_confidential` or remove the rigid `->after('first_meeting_on')` clause. |
| **F-02** | **HIGH** | `/` (Public Homepage) | Resident visits portal before wizard is complete | Redirects to `/setup` wizard without explanation | Show a friendly "Barangay System Initialization in Progress" maintenance page to the public | In `SetupCompleted.php`, redirect unauthenticated public visitors to a maintenance screen while reserving `/setup` for local/intranet administrators. |
| **F-03** | **HIGH** | `/setup` (Setup Wizard) | Any network visitor reaches server IP on day one | Anyone can complete the wizard and become root admin; no master key required | First-time setup should require an installation token from console or `.env` | Add `SETUP_INSTALLATION_KEY` verification check in `SetupController` Step 1. |
| **F-04** | **HIGH** | `/admin/emergency-contacts` & Resident Modal | Resident clicks "Emergency Hotlines" | Modal only displays 911 button; local police, fire, health, and barangay hotlines are invisible | Local numbers should display if configured, or show placeholder instruction to staff | Change `EmergencyContactSeeder` to use valid local LGU format placeholders or prompt admin during wizard Step 3 to provide emergency hotlines. |
| **F-05** | **MEDIUM** | Operations > Personnel | Fresh admin visits `/admin/personnel` | Table is completely empty (0 rows); no staff or officials exist | Setup wizard should automatically convert Step 2 Admin into Punong Barangay personnel | In `SetupController@finish`, automatically create a `Personnel` record for the admin user mapped to Position #1 (`punong_barangay`). |
| **F-06** | **MEDIUM** | Operations > Signatories | Print KP Form 9 (Summons) or Form 8 (Notice) before adding personnel | Document prints blank underlines (`____________________`) with no warning | System should display an alert in Cases & Blotter if required signatories have no incumbent personnel | In `CasePrintableController`, flash a warning banner if `SignatoryResolver` returns unassigned incumbents. |
| **F-07** | **MEDIUM** | Administration > Settings Pending | Admin navigates to `/admin/settings/pending` to resolve 64 items | No single "Confirm All" button; user must click 6 separate section buttons | A primary "Confirm All Baseline Defaults" action should be available at the top | Add `POST /admin/settings/pending/confirm-all` endpoint and button in `pending.blade.php`. |
| **F-08** | **MEDIUM** | Administration > Puroks / Geographic Scope | Admin needs to configure 10 Puroks or custom Sitios | No Purok menu, no route, and no UI exists; puroks are hardcoded to Purok 1–7 | Admin should be able to create, rename, and sort barangay puroks/zones from the UI | Create `PurokController` and view under `admin/settings/puroks` with standard CRUD actions. |
| **F-09** | **LOW** | Operations > User Accounts | Admin creates Staff account for Secretary or Desk Officer | Role field only offers "staff", "admin", "agency"; no role labeled "officer" | User creation form should clarify that Tanods and Desk Officers use Role: `Staff` with role-specific presets | Add helper text under the Role selector: *"Select Staff for all barangay employees, then select their permission preset below."* |
| **F-10** | **LOW** | Administration > Case Types | Admin checks Case Types on fresh install | Case types show `is_confirmed = false` | Wizard Step 5 should automatically mark baseline case types as confirmed | In `SetupController@finish`, mark seeded `CaseType` and `Position` records as `is_confirmed = true`. |

---

## 3. Unconfirmed Settings Inventory

The `RequiredDataSeeder` leaves **30 system settings** unconfirmed (`is_confirmed = false`). Below is the complete inventory, source mapping, default value, and safety assessment:

| Setting Key | Default Value | Questionnaire Source | Group | Safe to Confirm As-Is? | Recommendation for Admin |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `barangay.name` | `Barangay Calarian` | Q1: Official Barangay Name | General | ✅ Safe | Updated during Wizard Step 3. |
| `barangay.city` | `Zamboanga City` | Q2: City / Municipality | General | ✅ Safe | Updated during Wizard Step 3. |
| `barangay.province` | `Zamboanga del Sur` | Q3: Province | General | ✅ Safe | Updated during Wizard Step 3. |
| `barangay.region` | `Region IX (Zamboanga Peninsula)` | Q4: Region | General | ✅ Safe | Updated during Wizard Step 3. |
| `barangay.hall_address` | `Calarian, Zamboanga City, 7000` | Q5: Hall Physical Address | General | ✅ Safe | Updated during Wizard Step 3. |
| `barangay.contact_number` | `0917-123-4567` | Q6: Barangay Contact No. | General | ⚠️ Review | Replace with real landline/mobile number. |
| `barangay.email` | `info@calarian.gov.ph` | Q7: Official Email | General | ⚠️ Review | Replace with active government email. |
| `numbering.referral.format` | `REF-{YEAR}-{SEQ:4}` | Q10: Referral Format | Numbering | ✅ Safe | Standard statutory format `REF-2026-0001`. |
| `numbering.case.format` | `{MM}-{SEQ:3}-{YEAR}` | Q11: Case Docket Format | Numbering | ✅ Safe | Standard court format `11-001-2026`. |
| `numbering.blotter.format` | `{MM}-{SEQ:3}-{YEAR}` | Q12: Blotter Format | Numbering | ✅ Safe | Matches docket sequence format. |
| `numbering.summons.format` | `SUM-{YEAR}-{SEQ:4}` | Q13: Summons Notice Format | Numbering | ✅ Safe | Standard notice format `SUM-2026-0001`. |
| `calendar.holidays` | `[]` | Q16: Local Holidays | Calendar | ⚠️ Action Required | Admin should populate upcoming non-working holidays. |
| `cases.mediation_deadline_days` | `15` | Q19: PB Mediation Period | Cases | ✅ Safe (Statutory) | Mandatory RA 7160 Sec. 410(b) period (15 working days). |
| `cases.hearing_interval_days` | `7` | Q20: Hearing Interval | Cases | ✅ Safe | Reasonable operational interval between conciliations. |
| `cases.settlement_repudiation_days` | `10` | Q21: Repudiation Period | Cases | ✅ Safe (Statutory) | Mandatory RA 7160 Sec. 418 period (10 calendar days). |
| `cases.enabled` | `true` | Q22: Enable Case Module | Cases | ✅ Safe | Enables Katarungang Pambarangay features. |
| `records.lookback_years` | `4` | Q23: Records Retention | Cases | ✅ Safe | Preserves historical dispute files for 4 years. |
| `voice.enabled` | `true` | Q24: Voice Recording | Voice | ✅ Safe | Enables hotline and resident audio dictation. |
| `voice.max_seconds` | `60` | Q25: Audio Limit | Voice | ✅ Safe | Caps voice messages to 1 minute to prevent large uploads. |
| `voice.audio_retention_days` | `90` | Q26: Audio Purge Window | Voice | ✅ Safe | Automatically purges audio files after 90 days. |
| `voice.demo_sample_enabled` | `false` | Q27: Demo Samples | Voice | ✅ Safe | Must remain `false` in production. |
| `voice.extract_contact_from_audio` | `true` | Q28: Phone Audio Parsing | Voice | ✅ Safe | Transcribes hotline caller numbers. |
| `voice.resident_extract_contact` | `true` | Q29: Resident Audio Parsing | Voice | ✅ Safe | Parses resident phone/name from audio. |
| `voice.resident_recording_enabled` | `true` | Q30: Public Mic Input | Voice | ✅ Safe | Allows voice reports on mobile resident form. |
| `voice.daily_analyze_limit` | `100` | Q31: Daily AI Audio Quota | Voice | ✅ Safe | Protects API quota from runaway abuse. |
| `priority.urgency` | `20` | Q33: Urgency Score Weight | Priority | ✅ Safe | Standard weighted scoring algorithm. |
| `priority.category` | `10` | Q34: Category Weight | Priority | ✅ Safe | Standard risk weighting. |
| `priority.sensitive_place` | `25` | Q35: School/Hospital Buffer | Priority | ✅ Safe | Boosts reports near sensitive public landmarks. |
| `priority.supporting_reports` | `8` | Q36: Corroboration Weight | Priority | ✅ Safe | Boosts score when multiple residents report same issue. |
| `priority.age_days` | `2` | Q37: Aging Score Weight | Priority | ✅ Safe | Increases priority daily to prevent neglected backlog. |

---

## 4. Placeholder & Dummy Seed Data Audit

The table below lists every database entity seeded with default placeholder values and the action required by an administrator to replace it:

| Table | Column / Record | Seeded Placeholder | Why It Exists | Admin Action Required to Replace |
| :--- | :--- | :--- | :--- | :--- |
| `emergency_contacts` | `number` (IDs 2 to 6) | `'N/A'` | Ensures standard categories (Police, Fire, Health, CDRRMO, Barangay) exist on install without hardcoding incorrect local numbers. | Go to **Administration > Emergency Contacts** and edit each row with the official local precinct/substation number. |
| `signatory_assignments` | `personnel_id` (All 16 rows) | `null` | Pre-maps statutory document roles to positions (e.g. `summons` -> `Punong Barangay`) before real human officers are hired or enrolled. | Go to **Operations > Personnel**, add Alvarez and Saavedra, then save **Operations > Signatories**. |
| `positions` | `is_confirmed` (All 5 rows) | `false` | Indicates standard titles (Punong Barangay, Secretary, Lupon Member, Tanod, Kagawad) are advisory defaults. | Click **`[Confirm Positions]`** in Administration > Pending Confirmations. |
| `case_types` | `is_confirmed` (All 6 rows) | `false` | Allows barangays to customize or add local dispute types before locking in. | Click **`[Confirm Case Types]`** in Administration > Pending Confirmations. |
| `escalation_destinations` | `is_confirmed` (All 4 rows) | `false` | Pre-loads standard court and agency venues (MTC, PNP, CSWDO, City Prosecutor). | Click **`[Confirm Venues]`** in Administration > Pending Confirmations. |
| `report_templates` | `is_confirmed` (All 3 rows) | `false` | Pre-loads statutory reporting templates (DILG Monthly, Lupon Accomplishment, Peace & Order). | Click **`[Confirm Templates]`** in Administration > Pending Confirmations. |
| `offices` | `code`, `status` | `null` | Pre-loads 2 internal maintenance units and 4 external partner agencies with generic directory entries. | Functional as-is, but admin can edit office titles in database if municipal agency names differ. |
| `calendar.holidays` | Setting `calendar.holidays` | `[]` (Empty JSON array) | Prevents assuming a fixed city holiday calendar. | Go to **Administration > Official Calendar Holidays** and enter local non-working dates. |

---

## 5. Day-One Navigation & Page Health Audit

What an administrator sees when clicking each menu item in a fresh, empty installation:

| Sidebar Navigation Item | Route | Day-One Visual State | Errors / Warnings Observed | Health Verdict |
| :--- | :--- | :--- | :--- | :--- |
| **Dashboard** | `admin.dashboard` | Displays clean 0 counts across all 8 metric cards (*Awaiting Review: 0*, *In Progress: 0*, *Resolved: 0*). Empty state charts for category and purok. | Amber banner in header: *64 Pending Items*. No NaN, no division by zero. | ✅ Excellent |
| **Secretary Dashboard** | `admin.secretary` | Displays zero-case empty state illustration with message: *"No active mediation or conciliation cases."* | Clean empty state; quick action buttons visible. | ✅ Excellent |
| **Community reports** | `admin.reports.index` | Empty table with message: *"No reports matching your criteria."* | Clean table; search and category filters function without crash. | ✅ Excellent |
| **Cases & Blotter** | `admin.cases.index` | Empty case list with message: *"No blotter or complaint records on file."* | `[New Case / Blotter]` button prominently displayed. | ✅ Excellent |
| **Case Parties** | `admin.parties.index` | Empty parties directory: *"No individuals or juridical parties recorded."* | Search box and role filters render cleanly. | ✅ Excellent |
| **Conciliation Hearings** | `admin.hearings.index` | Empty hearing calendar and list: *"No hearings scheduled."* | Status filters (Scheduled, Held, Reset) function properly. | ✅ Excellent |
| **Referrals & Routing** | `admin.referrals.index` | Empty referrals table: *"No outbound external referrals recorded."* | Status tabs (Prepared, Transmitted, Accepted) display count `0`. | ✅ Excellent |
| **Staff Map** | `admin.map` | Leaflet map centers on Barangay Calarian coordinates. No pins rendered. | Map tiles load smoothly; filter bar shows 0 active incidents. | ✅ Excellent |
| **Personnel** | `admin.personnel.index` | Empty roster: *"No barangay personnel have been enrolled."* | Prominent `[Add Personnel]` button guides the administrator. | ⚠️ Empty (Action needed) |
| **Positions** | `admin.positions.index` | Lists 5 pre-loaded positions (Punong Barangay, Secretary, Lupon, Tanod, Kagawad). | Amber "Unconfirmed" badges next to each position. | ✅ Operational |
| **Signatories** | `admin.signatories.edit` | Displays 8 document cards with role assignments. Names show as `____________________`. | Amber badges indicate unconfirmed matrix. | ⚠️ Blank until Personnel added |
| **User Accounts** | `admin.users.index` | Shows 1 row: the Administrator created in Wizard Step 2. | Role badge shows `ADMIN`. No other staff members. | ✅ Operational |
| **Partner Agencies** | `admin.agencies.index` | Empty directory: *"No partner agency registrations on file."* | Status tabs (Pending, Approved, Suspended) show `0`. | ✅ Operational |
| **Reports Center** | `admin.reports-center.index` | Displays template cards: DILG Summary, KP Accomplishment, Category Analysis. | Generates empty zero-data summary cleanly without error. | ✅ Operational |
| **Audit Log** | `admin.audit-log.index` | Displays initial setup actions: setting updates, admin creation, FY activation. | Timestamps, IP addresses, and JSON old/new diffs render accurately. | ✅ Operational |
| **Emergency Contacts** | `admin.settings.emergency-contacts.index` | Lists 6 contacts with phone number `N/A`. | `[Edit]` buttons work properly to update phone numbers. | ⚠️ Requires Data Entry |
| **General Settings** | `admin.settings.general.edit` | Displays barangay contact info and coordinates entered during wizard. | Pre-populated and fully editable. | ✅ Operational |
| **Fiscal Years** | `admin.fiscal-years.index` | Lists **FY 2026** as Active. Form allows creating FY 2027 as planned. | Activation toggle and close year actions functional. | ✅ Operational |
| **Numbering Schemes** | `admin.numbering.edit` | Previews: `REF-2026-0001`, `11-001-2026`, `SUM-2026-0001`. | Validates syntax variables `{YEAR}`, `{MM}`, `{SEQ:N}` cleanly. | ✅ Operational |
| **Response Templates** | `admin.templates.index` | Lists 6 pre-loaded resident messaging templates. | Editable and functional. | ✅ Operational |
| **Case Types** | `admin.case-types.index` | Lists 6 dispute categories with descriptions and icon pickers. | Unconfirmed badges present. | ✅ Operational |
| **Escalation Destinations**| `admin.escalation-destinations.index` | Lists 4 external venues (MTC Branch 1, CSWDO, City Prosecutor, PNP). | Fully editable. | ✅ Operational |
| **Legacy Imports** | `admin.imports.index` | Empty import history. Upload form for CSV/Excel historical docket files. | Dropzone and template download links operational. | ✅ Operational |
| **Backups** | `admin.backups.index` | Empty backup list. Manual `[Create Backup]` button operational. | Creates SQLite snapshot and zipped storage archive cleanly. | ✅ Operational |
| **System Preferences** | `admin.settings.index` | Categorized preference cards for Priority Scoring, AI Triage, Voice Dictation. | Save actions work and flash success messages. | ✅ Operational |

---

## 6. Developer-Required Steps (Gaps Impossible From UI)

The following administrative tasks **cannot be accomplished through the web interface** and currently require technical intervention via code, terminal, or direct database migration:

1. **Adding, Renaming, or Removing Puroks (Geographic Scope):**
   - *Problem:* A barangay with 12 puroks or one that organizes territory into "Sitios" cannot change the 7 seeded puroks (`Purok 1` to `Purok 7`) from the web interface.
   - *Technical Workaround Needed:* Developer must run `php artisan tinker` or execute SQL `INSERT INTO puroks (name) VALUES ('Purok 8');`.
   - *Proposed Solution:* Build a standard `PurokController` with an Administration screen under `/admin/settings/puroks`.

2. **Customizing the Working Days Definition (Statutory Calendar):**
   - *Problem:* While holidays can be added via `/admin/settings/calendar`, the definition of which days of the week are working days (`'working_days' => [1, 2, 3, 4, 5]`) is hardcoded in `config/katarungang_pambarangay.php`. If a barangay operates Saturday conciliation sessions, an admin cannot configure Saturday as a working day from the UI.
   - *Technical Workaround Needed:* Developer must edit `config/katarungang_pambarangay.php`.
   - *Proposed Solution:* Store `cases.working_days` as a comma-separated setting managed in System Preferences.

3. **Configuring the External AI Model Version:**
   - *Problem:* If the barangay obtains a dedicated Gemini API key or wishes to adjust the AI model (`gemini-2.5-flash`), the key and model are only configurable in `.env` (`GEMINI_API_KEY`, `GEMINI_MODEL`).
   - *Technical Workaround Needed:* Server administrator must edit `.env` and run `php artisan config:clear`.
   - *Proposed Solution:* Provide an encrypted API key field in System Preferences.

4. **MySQL Migration Ordering Bug (`F-01`):**
   - *Problem:* A fresh install targeting MySQL fails completely on `php artisan migrate:fresh` because migration `2026_11_24_222928` expects column `first_meeting_on` to exist in `cases` table before migration `2026_12_01_400002` has run.
   - *Technical Workaround Needed:* Developer must edit migration column placement or run on SQLite.
   - *Proposed Solution:* Reorder or remove the `->after('first_meeting_on')` clause in `2026_11_24_222928_add_mediation_started_at_and_summons_superseded.php`.

---

## 7. Recommended Top 5 Fixes for Seamless First-Run

Implementing these 5 targeted fixes will transform the first-run experience from developer-assisted setup to a frictionless 10-minute self-serve onboarding:

1. **Auto-Enroll Admin as Punong Barangay Personnel (Fixes `F-05` & `F-06`):**
   - *Implementation:* In `SetupController@finish`, automatically create a `Personnel` record linking the new Administrator User to Position ID 1 (`punong_barangay`), marking them as an active authorized signatory.
   - *Benefit:* Solves the blank signature lines on day one without requiring the admin to re-type their own name in Operations > Personnel.

2. **Add "Confirm All" Master Button in Pending Settings (Fixes `F-07`):**
   - *Implementation:* Add a prominent primary button **`[Confirm All Baseline Defaults]`** at the top of `/admin/settings/pending` that confirms all 64 pending items in a single transaction.
   - *Benefit:* Eliminates 6 repetitive section clicks and immediately clears the warning banner.

3. **Prompt for Local Emergency Hotlines in Wizard Step 3 (Fixes `F-04`):**
   - *Implementation:* Expand Wizard Step 3 ("Barangay Profile") to include input fields for local Police Station and Fire Sub-Station phone numbers, saving them directly into `emergency_contacts`.
   - *Benefit:* Ensures the resident emergency modal has live local numbers the moment the portal goes public.

4. **Provide Purok Management in Administration Menu (Fixes `F-08`):**
   - *Implementation:* Add `admin/settings/puroks` CRUD interface allowing admins to add, edit, sort, and rename puroks/sitios.
   - *Benefit:* Adapts the system to any barangay's actual territorial subdivisions without database edits.

5. **Protect `/setup` with a Single-Use Installation Token (Fixes `F-03`):**
   - *Implementation:* During `migrate:fresh` or `app:empty`, generate a random 8-character token (e.g., `SETUP-KEY-9281`) and display it in the console. Require entering this key on Step 1 of `/setup`.
   - *Benefit:* Prevents unauthorized intranet or public visitors from hijacking the root administrator account on fresh deployment.
