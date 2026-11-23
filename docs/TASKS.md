# Implementation Roadmap & Pending Tasks

Active, outcome-driven tasks for **Barangay-Reporting-System-1**.

---

## 1. Summary of Completed Milestones

All foundation and core functional modules have been implemented and verified on the `dev` integration branch (288 tests, 1,955 assertions, 0 failures, 100% green):

- [x] **Tasks 1–6: Core Lifecycle & Phase 0 Foundation**: Canonical 12 report statuses, `no_action` with mandatory reason codes, `ReportStatusService::canTransition()`, recovery transitions (`merged -> under_review`), 22 foundation tables, and core services.
- [x] **Phase 0.5: Foundation Alignment**: Schema alignment for Katarungang Pambarangay cases (working days engine via `App\Support\WorkingDays`, CFA numbering, blotter record type, and 3-day referral follow-up).
- [x] **WS1: Setup, Fiscal Years & Settings**: 7-step onboarding wizard (`/setup`), fiscal year lifecycle (`active`, `closed`, `locked`), numbering configuration (`{MM}` token), calendar holidays, automated backups (`app:backup`), and multi-year global search.
- [x] **WS2: People, Access & Reports Center**: Official personnel directory, positions, signatory matrix (`SignatoryResolver`), 20 permission slugs, 4 presets, and official DILG compliance reports (`/admin/reports-center`).
- [x] **WS3: Case Intake & Parties**: Direct dispute docketing (`record_type`: blotter vs complaint), party directory, residency verification, duplicate party warnings, and party merge (`/admin/parties/{id}/merge`).
- [x] **WS4: Summons, Hearings & Deadlines**: Sequential summons tracking, working-day statutory deadlines (15-day mediation + 15-day conciliation), hearings scheduler, amicable settlement recording, and CFA printables.
- [x] **WS5: Agency Portal & Referrals**: Partner agency self-registration, admin verification, isolated Agency Portal (`/agency/referrals`), referral case attachments on private storage, and 3-day follow-up tracking.
- [x] **WS6 (Stretch): Legacy File Import**: Ingestion wizard for historical records (last 4 years) from CSV/Excel without AI, interactive column mapping, chunked commit, and batch rollback (`/admin/imports`).
- [x] **Users, Roles, Positions & Seeders Rework**: Canonical access tiers (`admin`, `staff`, `agency` via `App\Support\UserRole`), `User::position()` relation via `Personnel`, production reference data isolation (`RequiredDataSeeder` with 0 users/reports), and clean demo dataset (`DemoDataSeeder`).

---

## 2. Active Pending Tasks

### Task 1: Merge `dev` Branch into `main` (Release PR)
- **Goal**: Bring `main` (currently at Phase 0/WS1) up to parity with `dev` which contains WS1 through WS6 and the Users/Roles rework.
- **Scope**: Perform release merge or pull request from `dev` into `main`. Ensure CI/CD passes, Pint is clean, and 288 tests are green.

### Task 2: Staff Review Page Redesign (Review Workspace)
- **Goal**: Streamline the staff review workspace to align with the status-to-action matrix in `docs/WORKFLOW.md` § 11.
- **Scope**:
  - **Status-Driven Action Panel**: Display exactly one clear primary action per lifecycle status (e.g. `Confirm and route` on `submitted`, `Propose resolution` on `in_progress`), moving secondary/override actions into a contextual menu.
  - **Unified Timeline**: Merge the **Activity Timeline** (`status_logs` + `info_requests`) and **Internal Notes** (`internal_notes`) into a single chronologically descending thread.
  - **Comparison Matrix**: Ensure resident input vs. Gemini AI suggestion vs. final value comparison is instantly readable on a 375px viewport without side-by-side overflow.

### Task 3: Staff Queue Redesign (Report Queue)
- **Goal**: Re-architect `/admin/reports` for high operational throughput.
- **Scope**:
  - **Next Action Column**: Replace vague status columns with a clear, imperative "Next Action" indicator (e.g., *"Needs Clarification"*, *"Ready to Route"*, *"Resolution Awaiting Confirmation"*).
  - **Lifecycle-Based Tabs**: Filter queue into intuitive operational tabs (`Needs Action`, `In Progress`, `Referred`, `Archived`).
  - **Emergencies Pinned**: Ensure emergency-flagged incidents always sort to the absolute top of every tab with high-contrast red alert styling.
  - **Queue Columns**: Remove unnecessary reporter contact column (respecting privacy and screen width); replace with Purok badge, Category chip, and relative age timer.

### Task 4: Resident Submission Form Refinement
- **Goal**: Maximize citizen submission completion rate while preventing priority score distortion.
- **Scope**:
  - **Dynamic Category Questions**: Keep dynamic questions strictly optional (`required: false`) with a maximum of 2 observable questions per category.
  - **Unselected Defaults**: Ensure situational toggles under "More details" (`is_ongoing`, `is_recurring`) default to unset (`""`), preventing pre-selected "No" answers from unintentionally depressing priority scores.
  - **Observable Guidance**: Tailor situational placeholders per category (e.g. Streetlight: *"Which pole or nearest landmark is unlit?"*).

### Task 5: Academic Thesis Documentation Alignment
- **Goal**: Sync physical capstone deliverables with the revised software architecture.
- **Scope**:
  - Apply the requirement updates specified in [**`docs/ACADEMIC_DOCS_UPDATE.md`**](file:///c:/githubFiles/Barangay-Reporting-System-1/docs/ACADEMIC_DOCS_UPDATE.md) to the Requirements Blueprint (PDF) and Project Proposal (Word).
  - Once verified against the academic submissions, delete `docs/ACADEMIC_DOCS_UPDATE.md`.

### Task 6: Official Physical Forms & Templates Ingestion
- **Goal**: Replace draft notice layouts and response templates with official barangay wording.
- **Scope**:
  - Receive the physical blank forms from the Barangay Secretary (Summons notice, CFA form, Amicable Settlement agreement, DILG annual report layout).
  - Confirm pending items at `/admin/settings/pending` and update Blade print views to remove `<x-ui.draft-watermark>`.
