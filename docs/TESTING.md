# Testing Strategy & Verification Guide

Testing standards, CLI commands, test suite organization, and the client demonstration runbook for **Barangay-Reporting-System-1**.

---

## 1. Testing Commands

Tests execute in an isolated SQLite `:memory:` database with dummy mail drivers and mocked Gemini API clients.

```sh
# Run entire test suite
php artisan test

# Run a specific feature test file
php artisan test tests/Feature/StaffCaseManagementTest.php

# Run an isolated test method by name filter
php artisan test --filter=test_staff_can_assign_report_to_office

# Verify code style and formatting
./vendor/bin/pint --test

# Clear cached config prior to running tests
php artisan config:clear

# Phase 0 Onboarding & Demo Commands
php artisan app:empty                # Wipe DB & seed required baseline placeholders for /setup testing
php artisan app:demo                 # Wipe DB & seed full demo dataset with active FY, cases & accounts
```

### Test Credentials (from `php artisan app:demo`):
- **Admin**: `admin@calarian.gov.ph` / `password123`
- **Staff (Full)**: `staff@calarian.gov.ph` / `password123`
- **Encoder (Clerk)**: `clerk@calarian.gov.ph` / `password123`
- **Agency (CSWDO)**: `cswdo@zamboanga.gov.ph` / `password123`

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
| `tests/Feature/ReferralAndProofWorkflowTest.php` | Mode B referral creation, `REF-YYYY-NNNN` generation, printable letter views, and agency update logging. |
| `tests/Feature/ReferralsAndRoutingDirectoryTest.php` | Unified destination directory (`offices`), internal vs external filtering, and deactivation constraints. |
| `tests/Feature/ReportProgressUpdateTest.php` | Milestone updates (e.g. work started, inspection scheduled) and progress update logging. |
| `tests/Feature/ReportQueueAndInfoThreadTest.php` | Two-way `info_requests` clarification threads and resident reply integration. |
| `tests/Feature/ReportPrivacyAndValidationTest.php` | RA 10173 compliance: reporter PII exclusion from public endpoints, maps, and AI prompts. |
| `tests/Feature/GeminiExtractionTest.php` | Structured Gemini JSON extraction, prompt injection defense, emergency flag detection, and API failure fallbacks. |
| `tests/Feature/AdminSettingsTest.php` | Role authorization gates, category question builder updates, priority weight persistence, and audit logging. |

---

## 3. Client Demonstration Runbook (10-Step Demo Script)

Use this step-by-step walkthrough for thesis defenses, official client demonstrations, and stakeholder reviews:

1. **Mobile Resident Submission**:
   - Open `/` in a browser resized to a phone viewport ($375\text{px}$).
   - Highlight the single-card layout, emergency 911 hotline prompt, and data privacy notice.
   - Fill out an incident report (e.g., Taglish drainage complaint, attached photo, map pin in Calarian).
   - Submit and display the generated `BRG-XXXXXX` tracking code.
2. **Notification Delivery**:
   - Inspect `storage/logs/laravel.log` to demonstrate the automated `ReportReceived` notification email containing only the tracking link (no reporter PII or photos).
3. **Staff Triage & AI Comparison**:
   - Log in at `/login` as staff (`staff@barangay.test` / `password`).
   - Open `/admin/reports` and show the queue sorted by the numerical priority score.
   - Open the submitted report to demonstrate the **3-way comparative review**: Resident Input vs. Google Gemini Suggestion vs. Final Decision.
4. **Human-in-the-Loop Review Gate**:
   - Demonstrate that "Confirm and route" is blocked until category and urgency have been verified.
   - Edit a field, accept AI suggestions, and confirm review.
5. **Mode A Internal Dispatch**:
   - Assign the report to an internal barangay committee with an SLA deadline.
   - Confirm routing and observe the status transition to `routed`.
6. **Public Status Tracking**:
   - Open `/track/{code}` in an incognito window.
   - Demonstrate real-time timeline updates reflecting the staff's review note.
7. **Emergency Safety Demonstration**:
   - Return to `/` and submit an emergency incident with the checkbox *"Someone is in danger right now"*.
   - Show the immediate 911 banner, confirm the report is hidden from the public map (`/map`), and show the alert banner on the staff dashboard (`/admin/dashboard`).
8. **Mode B External Agency Referral**:
   - Triage a report outside barangay authority (e.g., national highway collapse).
   - Select external jurisdiction (Mode B) to generate a formal `REF-YYYY-NNNN` referral letter and scheduled 7-day follow-up.
9. **Proof of Work & Resident Verification**:
   - As staff, upload completed resolution proof photos and closing notes (`awaiting_confirmation`).
   - On the resident tracking page, inspect the photo proof, click "Confirm Fixed", and submit a 5-star rating (`resolved`).
10. **Administrative Governance & Audit Trail**:
    - Log in as Captain (`admin@barangay.test`).
    - Adjust category SLA hours and priority weights under `/admin/settings`.
    - Open `/admin/audit-log` to demonstrate the immutable security log recording every staff override and setting modification.
