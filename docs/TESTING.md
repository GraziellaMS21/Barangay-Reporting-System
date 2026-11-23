# Testing Strategy & Verification Guide

Testing standards, CLI commands, test suite organization, and the client demonstration runbook for **Barangay-Reporting-System-1**.

---

## 1. Testing Commands

Tests execute in an isolated SQLite `:memory:` database with dummy mail drivers and mocked Gemini API clients.

```sh
# Run entire test suite (184 passing tests)
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
- **Admin (Barangay Captain / Secretary)**: `admin@calarian.gov.ph` / `password123`
- **Staff (Full)**: `staff@calarian.gov.ph` / `password123`
- **Encoder (Clerk / Lupon Member)**: `clerk@calarian.gov.ph` / `password123`
- **Agency (CSWDO)**: `cswdo@zamboanga.gov.ph` / `password123`

*(Legacy development credentials from earlier seeders: `admin@barangay.test` and `staff@barangay.test` with password `password`).*

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

### Planned Workstream Test Suites
- `tests/Feature/Setup/SetupWizardTest.php` (WS1): Complete 7-step wizard, resume on step, admin creation, fiscal year activation.
- `tests/Unit/WorkingDaysTest.php` (WS1/Phase 0.5): Weekend skips, holiday exclusions from `calendar.holidays`, deadline boundaries.
- `tests/Feature/People/SignatoryResolverTest.php` (WS2): Resolves document signers by date, position, and case Pangkat Chairman.
- `tests/Feature/Cases/CaseIntakeAndDuplicatesTest.php` (WS3): Blotter vs complaint, trimmed party form, case duplicate detection, party merging.
- `tests/Feature/CaseActions/DeadlinesAndSummonsTest.php` (WS4): 15-working-day mediation clock, summons without limit, settlement agreement, CFA generation.
- `tests/Feature/Agency/AgencyPortalWorkflowTest.php` (WS5): Registration with ID/letter upload, Secretary approval, scoped referral visibility, attachments on private disk.
- `tests/Feature/Imports/LegacyImportTest.php` (WS6): Sample 100-row Excel import, mapping preview, conflict detection, batch rollback.

---

## 3. Client Demonstration Runbook (10-Step Demo Script)

Use this step-by-step walkthrough for thesis defenses, official client demonstrations, and stakeholder reviews:

1. **System Setup & Fresh Installation**:
   - Run `php artisan app:empty`.
   - Open `/setup` in browser. Complete the 7-step onboarding wizard: create administrator account, enter Barangay Calarian profile, set active fiscal year, assign personnel and permissions.
2. **Mobile Resident Submission**:
   - Open `/` in a browser resized to a phone viewport ($375\text{px}$).
   - Highlight the single-card layout, emergency 911 hotline prompt, and data privacy notice.
   - Fill out an incident report (e.g., Taglish drainage complaint, attached photo, map pin in Calarian).
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
