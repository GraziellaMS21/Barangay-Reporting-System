# Testing Strategy & Verification Guide

Testing standards, CLI commands, test suite organization, and the client demonstration runbook for **Barangay-Reporting-System-1**.

---

## 1. Testing Commands

Tests execute in an isolated SQLite `:memory:` database with dummy mail drivers and mocked Gemini API clients.

```sh
# Run entire test suite (337 tests, 2,253 assertions as of 2026-11-24)
php artisan test

# Run a specific feature test file
php artisan test tests/Feature/StaffCaseManagementTest.php

# Run an isolated test method by name filter
php artisan test --filter=test_staff_can_assign_report_to_office

# Verify code style and formatting
./vendor/bin/pint --test

# Clear cached config prior to running tests
php artisan config:clear

# Foundation Onboarding & Demo Commands
php artisan app:empty                # Wipe DB & seed required baseline placeholders for /setup testing
php artisan app:demo                 # Wipe DB & seed full demo dataset with active FY, cases & accounts

# Backup & Restore CLI Verification
php artisan app:backup               # Dumps DB + archives storage/app/private & public to private backups/
php artisan app:restore {file} --force # Puts app in maintenance, restores DB and files, runs migrations
```

### Test Credentials (from `php artisan app:demo`):
- **Admin (Barangay Captain)**: `admin@calarian.gov.ph` / `password123` (role: `admin`, position: `punong_barangay`)
- **Admin (Barangay Secretary)**: `secretary@calarian.gov.ph` / `password123` (role: `admin`, position: `barangay_secretary`, Secretary Q6)
- **Staff (Desk Officer / Tanod)**: `staff@calarian.gov.ph` / `password123` (role: `staff`, preset: `staff_full`, position: `barangay_tanod`)
- **Encoder (Records Clerk / Lupon Member)**: `clerk@calarian.gov.ph` / `password123` (role: `staff`, preset: `case_encoder`, position: `lupon_member`)
- **Reviewer (Kagawad Peace & Order)**: `reviewer@calarian.gov.ph` / `password123` (role: `staff`, preset: `case_reviewer`, position: `kagawad_peace_order`)
- **Agency (CSWDO Partner Officer)**: `cswdo@zamboanga.gov.ph` / `password123` (role: `agency`, linked to CSWDO external office)

### Testing with Fiscal Years & Seeders
- In `tests/TestCase.php`, `completeSetup()` runs automatically by default, creating an active fiscal year for the current year.
- To test cold-start installation workflows, invoke `$this->withoutSetup()` in your test method, which disables completion flags and triggers `/setup` redirection checks.
- For idempotent seed testing, run `php artisan test tests/Feature/SeederTest.php`.

---

## 2. Test Suite Structure

The test suite extends `Tests\TestCase` and is organized into functional feature suites:

| Test File | Critical Coverage Scope |
| :--- | :--- |
| `tests/Feature/ResidentReportingPhase9Test.php` | Single-card submission, required consent, client photo handling, dynamic question schemas, and tracking code generation. |
| `tests/Feature/StaffCaseManagementTest.php` | Staff authentication, triage queue sorting, 3-way review card comparison, manual status updates, and proof photo uploads. |
| `tests/Feature/ReferralAndProofWorkflowTest.php` | Mode B referral creation, `REF-YYYY-NNNN` generation, printable letter views, and agency update logging. Asserts follow-up date (3 days per Secretary answer [A Q12]). |
| `tests/Feature/ReferralsAndRoutingDirectoryTest.php` | Unified destination directory (`offices`), internal vs external filtering, and deactivation constraints. |
| `tests/Feature/ReportProgressUpdateTest.php` | Milestone updates (e.g. work started, inspection scheduled) and progress update logging. |
| `tests/Feature/ReportQueueAndInfoThreadTest.php` | Two-way `info_requests` clarification threads and resident reply integration. |
| `tests/Feature/ReportPrivacyAndValidationTest.php` | RA 10173 compliance: reporter PII exclusion from public endpoints, maps, and AI prompts. |
| `tests/Feature/GeminiExtractionTest.php` | Structured Gemini JSON extraction, prompt injection defense, emergency flag detection, and API failure fallbacks. |
| `tests/Feature/AdminSettingsTest.php` | Role authorization gates, category question builder updates, priority weight persistence, and audit logging. |

### Implemented Workstream Feature Test Suites
- `tests/Feature/Setup/SetupWizardTest.php` (WS1): Complete 5-step wizard, resume on step, admin creation, fiscal year activation.
- `tests/Unit/WorkingDaysTest.php` (WS1/Phase 0.5): Weekend skips, holiday exclusions from `calendar.holidays`, deadline boundaries.
- `tests/Feature/People/PersonnelAndSignatoriesTest.php` (WS2): Resolves document signers by date, position, and case Pangkat Chairman.
- `tests/Feature/Cases/CaseDocketAndManagementTest.php` (WS3): Blotter vs complaint, trimmed party form, case duplicate detection, party merging.
- `tests/Feature/CaseActions/HearingTest.php` & `SummonsLifecycleTest.php` (WS4): 15-working-day mediation clock, summons without limit, settlement agreement, CFA generation.
- `tests/Feature/Agency/AgencyWorkflowTest.php` (WS5): Registration with ID/letter upload, Secretary approval, scoped referral visibility, attachments on private disk.
- `tests/Feature/Imports/LegacyImportTest.php` (WS6): Sample 100-row Excel import, mapping preview, conflict detection, batch rollback.
- `tests/Feature/Auth/UserRolePositionReworkTest.php`: Canonical role validation, `personnel` role migration, seeder idempotency, confidential case isolation.

### Voice Reporting & Phone Intake Suite (WS7: Record-First Prototype)
- `tests/Feature/Voice/VoiceAnalyzeTest.php`:
  - Staff analyze endpoint (`POST /admin/reports/phone/analyze`): requires `reports.log_phone` permission; full multimodal extraction with Gemini mock/fakes; returns transcript, suggestions (category & urgency), extracted caller contact.
  - Resident analyze endpoint (`POST /report/voice/analyze`): rate-limiting (`throttle:3,10`); never returns raw transcript; never pre-selects category or urgency; strips contact details on anonymous submissions; daily limit fallback.
  - Normalization & validation: Philippine mobile formats (`09XXXXXXXXX`), active purok lookup, active category validation, urgency constraint (`Low`, `Medium`, `High`, `Critical`).
  - Clarity handling: `clear`, `partial` (with notice banner), `unclear` (blocks pre-filling, sets `needs_verification` and `unverified_draft`).
  - Abuse protection: real MIME verification, duration constraint ($\le 120\text{s}$), daily limit check.
  - Audio retention purge: attached audio retention counted from report closure date (90 days).
- `tests/Feature/Voice/VoiceTranscriptionTest.php`:
  - Public transcription endpoint validation: duration limit ($\le 60\text{s}$), file size limit ($\le 10\text{MB}$), MIME type validation (`audio/webm`, `audio/mp4`, `audio/ogg`, `audio/wav`).
  - Rate-limiting verification (`throttle:5,1`).
  - Gemini transcription fakes: success (`done`), uncertain speech (`needs_verification`), unreadable audio/timeout (`failed`).
  - The Uncertainty Rule: asserts that when `transcript_status` is `needs_verification` or `failed`, guessed text is never inserted as the primary description, the report is accepted into `submitted` with a placeholder description, and "Confirm and route" is blocked until verified.
- `tests/Feature/Voice/VoiceAudioSecurityTest.php`:
  - Audio streaming authorization: verifies `GET /admin/reports/{report}/audio` requires `auth`, `role:staff`, and `reports.view`.
  - HTTP Range header verification (206 Partial Content) and `Cache-Control: private, no-store`.
  - Data privacy assertion: verifies voice audio tokens and files are strictly absent from public map endpoints, `/api/nearby-reports`, notification emails, and resident tracking views.
- `tests/Feature/Voice/PhoneReportIntakeTest.php`:
  - Permission enforcement: verifies `/admin/reports/phone` requires `reports.log_phone` permission.
  - Mandatory verbal consent validation (`verbal_consent = true`) and staff recorder tracking (`consent_recorded_by`).
  - Generated tracking code response with copyable payload.
- `tests/Feature/Voice/StaffPhoneReportIntakeUiTest.php`:
  - Staff "Log phone report" UI verification: requires `reports.log_phone` permission.
  - Record-first workflow: verbal consent gate strictly ABOVE recorder, read-aloud spoken script card, recorder locked until verbal consent checked.
  - Secondary manual link ("Fill in manually instead") allows desk officers to skip audio recording.
  - Staff audio reality: displays "Record the call (speakerphone) or dictate a summary" without optional label.
  - Pre-fill markup: "From recording" badge, "Not provided" muted placeholders, and helper prompts for unstated required fields.
  - Clarity banners: partial clarity banner and unclear audio verification banner.
  - Emergency 911 prompt and pre-checked emergency confirmation.
- `tests/Feature/Voice/ResidentVoiceIntakeTest.php`:
  - Resident report intake with optional category: submits successfully with `category_id = null`, verifies null-safety across routing, priority calculation, and status logs.
  - Resident reporting view: verifies "Quick report by voice" strip at top, consent line and button label, "Choose a category (optional)" title without required asterisk, and hidden strip when `voice.resident_recording_enabled = false`.
  - Resident voice analysis: returns suggested category and extracted contact when enabled, strictly excludes raw transcript and urgency, drops contact details on anonymous submissions.
  - Audio provenance and review page: asserts `report_audios.provenance` JSON and `ai_description` storage, and verifies staff review page renders "From recording", "Edited after recording", "Edited by resident", and "Not chosen by resident".
- `tests/Feature/Voice/VoiceReviewAndVerificationTest.php`:
  - Review page rendering: verifies "Not provided" (muted italic: `.review-not-provided`) displays for null contact and location fields; verifies source badges ("Voice report", "Phone intake") in page header.
  - Verification banner & action: verifies prominent amber banner, audio player, verification form, unverified draft tag, and `data-has-unverified-audio="true"`.
  - Routing blocking & unblocking: asserts that forward routing attempts (`PATCH /admin/reports/{report}/route`) are strictly blocked when audio needs verification; asserts that staff verification (`POST /admin/reports/{report}/audio/verify`) updates description, logs audit event, clears unverified draft, and unblocks routing.
  - Provenance badges: verifies review page displays "From recording", "Edited after recording", "Edited by resident", and "Not chosen by resident".
  - Data export cleanliness: verifies `/admin/reports/export` CSV stream outputs empty strings (`""`) for unstated/missing fields and never emits UI placeholder "Not provided".
- `tests/Feature/Voice/AudioPurgeCommandTest.php`:
  - `php artisan app:purge-audio`: verifies audio files older than `voice.audio_retention_days` (counted from report closure for attached audio) and orphaned uploads older than 24h are deleted from the `private` disk while transcript text and report records are preserved.
  - Audit logging: asserts audit log entries are generated for all audio streaming, verification, and purge events.

---

## 3. Client Demonstration Runbook (10-Step Demo Script)

Use this step-by-step walkthrough for thesis defenses, official client demonstrations, and stakeholder reviews:

1. **System Setup & Fresh Installation**:
   - Run `php artisan app:empty`.
   - Open `/setup` in browser. Complete the 5-step onboarding wizard: environment check, create administrator account, enter Barangay Calarian profile, set active fiscal year, and review seeded baseline personnel. (Staff accounts and granular permissions are assigned post-setup at `/admin/users`).
2. **Mobile Resident Submission**:
   - Open `/` in a browser resized to a phone viewport ($375\text{px}$).
   - Highlight the single-card layout, emergency 911 hotline prompt, and data privacy notice.
   - Fill out an incident report (e.g., Taglish drainage complaint, attached photo, map pin in Calarian).
   - *(Optional Planned WS7 Demo)*: Demonstrate the optional "Record instead of typing" recorder. In demo mode with `voice.demo_sample_enabled` or `local` environment, click "Use sample recording" to demonstrate canned transcription without requiring microphone hardware or an active Gemini API key. Show text populating into description for review and editing before submission.
   - Submit and display the generated `BRG-XXXXXX` tracking code.
3. **Staff Triage & AI Comparison**:
   - Log in at `/login` as staff (`staff@calarian.gov.ph` / `password123`).
   - Open `/admin/reports` and show the queue sorted by the numerical priority score.
   - Open the submitted report to demonstrate the **3-way comparative review**: Resident Input vs. Google Gemini Suggestion vs. Final Decision.
4. **Human-in-the-Loop Review Gate**:
   - Demonstrate that "Confirm and route" is blocked until category and urgency have been verified.
   - Edit a field, accept AI suggestions, and confirm review.
5. **Mode A Internal Dispatch & Resolution Proof**:
   - Assign the report to an internal barangay committee with an SLA deadline (`routed`).
   - Log progress note and upload resolution proof photos (`awaiting_confirmation`).
   - On resident tracking page, inspect photo proof, submit 5-star rating, and verify resolution (`resolved`).
6. **Mode B External Agency Referral**:
   - Triage a report outside barangay authority (e.g., major water main rupture).
   - Select external jurisdiction (Mode B) to generate a formal `REF-YYYY-NNNN` referral letter with a scheduled **3-day follow-up** [A Q12].
   - Dispatch referral to external agency portal.
7. **Agency Portal Login & Handoff**:
   - Log in as agency officer (`cswdo@zamboanga.gov.ph` / `password123`).
   - View only referrals addressed to CSWDO (`/agency/referrals`). Acknowledge receipt and post a progress note.
   - Show how staff immediately see the agency's update on `/admin/referrals`.
8. **Dispute Intake (Blotter & Formal Complaint)**:
   - Log in as clerk (`clerk@calarian.gov.ph` / `password123`).
   - File a walk-in blotter entry, then file a formal complaint with resident complainant and non-resident respondent.
   - Show automatic duplicate case warning if identical parties filed within 30 days.
9. **Summons, Deadlines & CFA Escalation**:
   - Issue summons and show sequential numbering and hearing schedule.
   - Show deadline indicator ("Day X of 15" working days).
   - Record complainant escalation request, generate printable Certificate to File Action (CFA) signed by Pangkat Chairman [A Q5], and endorse to police.
10. **Reports Center, Search & Backup**:
    - Log in as Barangay Captain / Admin (`admin@calarian.gov.ph` / `password123`).
    - Open `/admin/reports-center`: preview and print yearly DILG KP Accomplishment report and monthly case summary with official signature blocks.
    - Test global search across all fiscal years with hyphens (`11-001-2026`).
    - Run system backup under `/admin/settings/backups`.
    - Open `/admin/settings/pending` to show the register of items awaiting final confirmation from official barangay blank forms.

> [!NOTE]
> **Planned Speech Transcription Accuracy Evaluation (WS7)**: Prior to final pilot sign-off, a manual transcription accuracy benchmark is planned using 10–15 real-world audio recordings per supported language (English, Filipino, Bisaya, and Chavacano). Recordings will be evaluated on core semantic meaning and location accuracy rather than literal word-for-word parity.

---

## 4. Manual Multi-Device UI Checklist

Before submitting pull requests or concluding workstream tasks, every developer and coding agent must manually inspect views at three target viewport widths: **375 px (Mobile)**, **768 px (Tablet)**, and **1280 px (Desktop)** per [**`docs/UI_UX_SPEC.md`**](file:///c:/githubFiles/Barangay-Reporting-System-1/docs/UI_UX_SPEC.md):

### Viewport Verification Grid

| Check Item | 375 px (Mobile) | 768 px (Tablet) | 1280 px (Desktop) |
| :--- | :--- | :--- | :--- |
| **No Horizontal Page Scroll** | Canvas strictly fits; zero `overflow-x` page scroll. | Canvas fits; zero page scroll. | Canvas fits centered up to 1280 px max. |
| **Primary Action Placement** | Stacks to 100% full width on top of secondary/cancel. | Right-aligned in header/footer rows. | Rightmost button in all action clusters. |
| **Table Layout & Scrolling** | Card container scrolls horizontally; first column pins sticky. | Table fits or scrolls cleanly; no wrapped IDs. | Fixed column widths matching spec exactly. |
| **Form Layout & Sticky Footers** | Single column; sticky footer never covers last input. | Single column; 2-col short inputs only. | Single column max 720 px (or 2-col short grids). |
| **Modals & Overlays** | Max 90vw / 90vh; interior content scrolls gracefully. | Centered 480 px (sm) or 640 px (md). | Centered; focus traps to first input; Esc closes. |
| **Touch Targets & Focus** | Minimum 44 px tap targets on all buttons and inputs. | Standard 40 px control heights. | Visible 2 px blue focus rings on tab navigation. |
| **Color & Contrast** | Badges use both icon/text and color; 4.5:1 contrast. | 4.5:1 contrast on `#FFFFFF` / `#F8FAFC`. | 4.5:1 contrast verified across all states. |

---

## 5. Planned Feature Test Plan (WS3 / WS4 / Resident Intake)

> [!NOTE]
> The test suites below represent the **Planned Test Plan** for upcoming resident complaint intake and case lifecycle features. These test cases are planned for implementation in subsequent tasks alongside feature code; existing automated tests (337 tests, 2,253 assertions referenced in TASKS.md) remain passing and unaffected.

### 5.1 Resident Entry Screen
- Assert entry view renders exactly three primary actions: Emergency, Report a Community Problem, and File a Complaint.
- Assert Emergency action uses `--red` accent and displays 911 / hotline guidance without altering report state.
- Assert no separate "File a Blotter" button exists on the resident entry page.

### 5.2 Resident Complaint Intake
- Assert `POST /complaint` creates a record in `complaint_intakes` in status `pending`.
- Assert submission does NOT create a case record, summons, or party in the canonical `parties` table.
- Assert upfront confidentiality screening: answering "Yes" to VAWC/child-abuse screening redirects to guidance, stores zero records, and renders "Leave this page" quick exit.
- Assert anonymity is rejected / unavailable on the complaint form (complainant name and contact are required).
- Assert data privacy consent checkbox is required and cannot be submitted unchecked.
- Assert `GET /complaint/received` displays receipt text only, strictly withholding case status, respondent data, and dispute tracking.
- Assert rate limiting (`throttle:3,10`) and honeypot spam protection on complaint endpoint.

### 5.3 Staff Intake Review & Docketing
- Assert pending intakes display on `/admin/cases?tab=pending_intake` (`cases.view` permission required).
- Assert "Docket this" opens the case creation form prefilled with intake facts.
- Assert executing "Docket this" creates exactly one case at status `filed` and logs the blotter entry reference (two numbers, one record).
- Assert duplicate case warning appears if parties match an existing active case within 30 days.
- Assert rejecting or redirecting an intake strictly requires a standard outcome reason and written explanation note.

### 5.4 Core Governance & Non-Automated State Transitions
- Assert no automated status transitions occur from summons issuance, summons counts, hearing attendance, or deadline expiration.
- Assert transitions require explicit authorized staff/official actions inside `DB::transaction()`.

### 5.5 Mediation Clock & Working Days
- Assert mediation statutory clock starts from `first_meeting_on` before the Punong Barangay, with fallback to `filed_on`.
- Assert working day computation excludes weekends and official non-working holidays loaded from `calendar.holidays` via `App\Support\WorkingDays`.

### 5.6 Hearing Attendance & Non-Appearance Handling
- Assert recording respondent `failed_to_appear` logs absence in minutes and triggers soft warning when attempting `in_mediation` -> `escalated` ("The Pangkat usually must be constituted first").
- Assert complainant non-appearance allows transition to `dismissed` with mandatory explanation note, and never auto-dismisses.
- Assert at 15 working days without settlement, the case displays "Ready for next step: endorse to Pangkat"; assert no automated status change occurs and no summons-count threshold exists.

### 5.7 Printable Templates & Signatories
- Assert printables dynamically resolve signatories via `SignatoryResolver::forDocument()` (Punong Barangay for summons, notices, and referrals; Pangkat Chairman for CFA per C3; Secretary for blotter extract preparation).
- Assert unconfirmed templates render `<x-ui.draft-watermark>`.
- Assert CSV data exports emit empty strings `""` for unstated fields and never emit UI placeholder strings like `"Not provided"`.

### 5.8 Guest and Anonymous Community Reports
- Assert community problem report can be submitted as a guest without creating an account or logging in.
- Assert anonymous report stores `is_anonymous = true` with null reporter name, contact, email, and stores no IP address or device identifier.
- Assert optional one-time tracking code is generated for guest status lookup via `/track/{code}`.
- Assert complaint form continues to reject anonymity.

### 5.9 Regression Verification
- Assert canonical 12-status community report lifecycle remains completely unchanged and functional.
- Assert Report-to-Case conversion flow remains fully decoupled, transitioning civil report to `no_action` (`reason: converted_to_case`) while starting case at `filed`.
