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

### Task 7: Voice Reporting Prototype (WS7) — Record-First Intake
- **Goal**: Implement "record first, AI fills the form" intake for residents and hotline desk officers without telephony infrastructure.
- **Phases**:
  - [x] **Phase A: Shared Backend (No UI)**:
    - [x] Inspection & discovery (clarified PII isolation amendment, consent tracking, empty description root cause).
    - [x] Added `consent_recorded_by` nullable foreign key to `reports` table (migration `2026_12_02_800001_add_consent_recorded_by_to_reports_table.php`).
    - [x] Configured settings: `voice.extract_contact_from_audio` (default true), `voice.resident_extract_contact` (default false), `voice.resident_recording_enabled` (default true), `voice.daily_analyze_limit` (default 100), `voice.audio_retention_days` (default 90, counted from report closure for attached audio).
    - [x] Added `GeminiService::analyzeVoice()` single-call multimodal extraction returning transcript, description without PII, and structured form fields in strict JSON with prompt-injection defense.
    - [x] Built `VoiceAnalyzeService` with MIME validation, duration capping (120s), daily limit fallback, Philippine phone normalization (`09XXXXXXXXX`), purok/category/urgency validation, and mock scenarios (`full`, `no_contact`, `partial`, `unclear`, `emergency`).
    - [x] Built endpoints: `POST /report/voice/analyze` (resident) and `POST /admin/reports/phone/analyze` (staff).
    - [x] Updated audio retention purge: attached audio retention counted from report closure date; unattached keeps 24h expiry.
    - [x] Comprehensive test suite: `tests/Feature/Voice/VoiceAnalyzeTest.php` (13 tests, all passing).
    - [x] Regenerated `docs/ROUTES.snapshot.json` and updated documentation suite.
  - [x] **Phase B: Staff "Log phone report" UI**:
    - [x] Record-first intake layout (`resources/views/admin/reports/phone.blade.php`) with secondary "Fill in manually instead" bypass link.
    - [x] Verbal consent gate strictly ABOVE recorder with read-aloud script card; recorder disabled until checked.
    - [x] Staff audio mode support ("Record call (speakerphone) or dictate a summary") without optional labeling.
    - [x] Audio player preview and collapsible staff-only transcript panel ("What was said") with raw transcript.
    - [x] Pre-fill engine with "From recording" badges (dynamic badge removal on input edit), "Not provided" placeholder styling, and preservation of manually typed text across re-recordings.
    - [x] Clarity handling (partial banner and unclear verification banner; unclear sets `needs_verification` with empty fields).
    - [x] Emergency 911 prompt and pre-checked emergency confirmation.
    - [x] Form submission saving `consent_recorded_by = auth()->id()` alongside `consent_at`.
    - [x] Comprehensive test suites: `tests/Feature/Voice/StaffPhoneReportIntakeUiTest.php` (8 tests) and updated `tests/Feature/Voice/PhoneReportIntakeTest.php`.
  - [x] **Phase C: Resident form UI**:
    - [x] "Quick report by voice" strip at top of single-card resident form with secondary "or fill in the form yourself below" link.
    - [x] Explicit consent line directly above the strip's record button ("My recording is processed by AI and seen by barangay staff only") with button label "I agree, start recording" (setting `voice_consent_at`; Data Privacy checkbox never auto-ticked).
    - [x] Category made optional on resident form ("Choose a category (optional)"), nullable validation, and graceful unclassified handling.
    - [x] Dynamic pre-fill engine: PII-free description, category chip selection with deselect ability, emergency checkbox pre-tick with "From recording, please confirm", purok, landmark, and contact inputs.
    - [x] "From recording" badge placement and dynamic removal on user edit.
    - [x] Auto-expanding collapsed contact sections ("More details", "Get updates") when contact details are extracted.
    - [x] Strict anonymity: immediate UI clearing of AI-filled contact inputs and server-side drop when anonymous is checked.
    - [x] Clarity handling (friendly banners for clear, partial, and unclear/failed audio).
    - [x] Provenance JSON and original AI description persistence on `report_audios` (never storing contact values in provenance).
    - [x] Admin review page provenance chips ("From recording", "Edited after recording", "Edited by resident", "Not chosen by resident").
    - [x] Comprehensive test suite: `tests/Feature/Voice/ResidentVoiceIntakeTest.php` (7 tests, all passing) and updated `tests/Feature/Voice/VoiceAnalyzeTest.php`.
  - [x] **Phase D: Review page + final docs pass**:
    - [x] "Not provided" (muted italic: `.review-not-provided`) rendered on review page (`/admin/reports/{report}`) for unstated caller contact (name, phone, email) and location details on voice and phone-log reports.
    - [x] Source badges ("Voice report", "Phone intake") displayed prominently in review page header.
    - [x] End-to-end `needs_verification` flow: prominent amber banner, audio player preview, verification form (`POST /admin/reports/{report}/audio/verify`), unverified draft tag, client-side route button disabling, and server-side transition blocking.
    - [x] Verification action: staff verify audio, update report description, log audit trail, clear unverified draft status, and unblock forward routing.
    - [x] Clean data exports: CSV exports (`/admin/reports/export`, `/admin/cases/export/csv`) and printable tracking slips emit empty strings (`""`) for unstated/missing fields and never output UI placeholder "Not provided".
    - [x] Accuracy-test checklist and benchmark evaluation protocol documented in `docs/ACADEMIC_DOCS_UPDATE.md` for Chapter 6 academic evidence.
    - [x] Comprehensive test suite: `tests/Feature/Voice/VoiceReviewAndVerificationTest.php` (7 tests, all passing).
    - [x] Full test suite and Pint linting verification.
