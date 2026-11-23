# Phase 0: Foundation Discovery Findings

Recorded as part of **Step 0: Discovery** for the foundation roadmap in `docs/PHASE0_FOUNDATION_TASK.md` and `docs/SPEC_ADDENDUM.md`.

---

## 1. Baseline Metrics
- **PHPUnit Test Suite**: 149 tests, 1,177 assertions, 100% passing.
- **Route Count (`php artisan route:list`)**: 103 routes registered.
- **Code Style (`pint --test`)**: Clean (passed).

---

## 2. Audit Logging
- Model: `App\Models\AuditLog` (`app/Models/AuditLog.php`).
- Database Table: `audit_logs` (`id`, `user_id`, `action`, `model`, `model_id`, `old_values` [json], `new_values` [json], `timestamps`).
- Current usage: Direct `AuditLog::query()->create([...])` calls in:
  - `App\Services\EmergencyContactService`
  - `App\Services\ReferralService`
  - `App\Services\ReportReviewService`
  - `App\Services\SettingsManagementService`
- **Phase 0 Contract**: `App\Services\AuditLogger` will encapsulate `AuditLog::query()->create([...])` providing `log(?User $by, string $action, Model|string $subject, array $old = [], array $new = []): void`.

---

## 3. Layout & Navigation
- Admin Layout: `resources/views/layouts/admin.blade.php`.
- Sidebar structure: Hardcoded links in `<nav class="staff-nav">` with `@if(auth()->user()->isAdmin())` section.
- **Phase 0 Contract**: Config-driven navigation in `config/navigation.php` supporting roles, permissions, feature flags, and active fiscal year context. Agency users will have a separate dedicated layout `layouts/agency.blade.php`.

---

## 4. Roles & Access Control
- Middleware: `App\Http\Middleware\EnsureRole` (`app/Http/Middleware/EnsureRole.php`). Checks `in_array($user->role, $roles, true)`.
- Database: `users.role` is a `string(20)` with default `'staff'` (added via `2026_11_02_033951_add_role_to_users_table.php`).
- Existing roles: `'admin'`, `'staff'`.
- **Phase 0 Contract**: Extend `EnsureRole` to support `'agency'` role. Add `hasPermission(string $slug)` to `User` and alias `permission:slug` middleware.

---

## 5. Referral Numbers & Updates Table
- Number Generation: `App\Services\ReferralService::generateReferenceNo()` formats `REF-{YEAR}-{0001}` using transactional sequence locking against the `referrals` table.
- Table `referral_updates`: **Already exists** in `database/migrations/2026_11_18_000001_referral_tracking_and_proof_photos.php`.
  - Columns: `id`, `referral_id`, `user_id`, `note`, `channel`, `occurred_at`, `photo_path`, `visible_to_resident`, `timestamps`.
  - **Decision per Section 2.3 Note**: We will reuse this existing table rather than duplicating it.

---

## 6. Seeders & Demo Data
- `database/seeders/DatabaseSeeder.php` orchestrates strictly baseline reference data by calling `RequiredDataSeeder` (0 users, 0 reports).
- `RequiredDataSeeder` orchestrates reference seeders: `PurokSeeder`, `CategorySeeder`, `CategoryQuestionSeeder`, `OfficeSeeder`, `RoutingRuleSeeder`, `EmergencyContactSeeder`, and `ResponseTemplateSeeder`, plus permissions, positions, signatory assignments, settings placeholders, priority weights, case types, and escalation destinations.
- `DemoDataSeeder` handles demo accounts and demo activity (calls `RequiredDataSeeder`, seeds active FY 2026, advances number sequences, creates demo accounts and cases, and invokes `DemoReportSeeder`).
- Legacy cluttered seeders removed: `AdminSeeder`, `EngineSeeder`, `UserSeeder`, `SettingSeeder`, `StaffSeeder`, `ResidentSeeder`.
- Clean CLI commands: `php artisan app:empty` (clean baseline) and `php artisan app:demo` (full demo environment).

---

## 7. Existing Settings & Response Templates Tables
- `settings`: Exists in `2026_11_03_040000_phase_4_to_7_support_tables.php` with `id`, `key`, `value`, `timestamps`. Phase 0 migration will alter this table to add `type`, `group`, `label`, `description`, `is_confirmed`, `confirmed_by`, `confirmed_at`, and `source_question`.
- `response_templates`: Exists in `2026_11_10_000001_phase8_foundation.php` with `id`, `name`, `status`, `category_id`, `body`, `is_active`, `timestamps`. Phase 0 migration will alter this table to add `title`, `kind`, and `created_by`.

---

## 8. Configuration
- `config/reports.php`: Contains `referrals.follow_up_days` (default 7), proof photo limits, status options, and channels.
- **Phase 0 Contract**: Move dynamic settings into `settings` table as outlined in `docs/PLACEHOLDERS.md` while maintaining fallback defaults.

---

## 9. Verification round 2 (2026-11-22) [D, V]

Conducted to reconcile `docs/REVISION_2.md` claims against the actual repository code and database schema.

### Git and Filesystem Verification
Verification confirmed that `WORKFLOW.md`, `SPEC_ADDENDUM.md`, and `TEAM_WORKSTREAMS.md` were **not actually patched** on disk or in git history prior to this verification round, despite `REVISION_2.md` lines 12–15 stating they were patched. They remained at their previous revisions and must be updated directly as part of this documentation round.

### Repository Inspection Findings

| # | Inspection Item | Repository / Database Fact Found [V] | Affected Documents |
| :--- | :--- | :--- | :--- |
| 1 | **Route snapshot** | Exact route count is **244 registered routes**. Snapshot exported to `docs/ROUTES.snapshot.json`. The handoff table documented 55 stub routes; the full application has 244 total routes including public, resident, auth, staff, admin, and engine APIs. | `docs/API.md`, `docs/TEAM_WORKSTREAMS.md`, `docs/FOUNDATION_HANDOFF.md` |
| 2 | **Table & Model counts** | **53 database tables** in total schema (including pivots, sessions, cache, jobs). **44 Eloquent models** exist in `app/Models/` (21 models created in Phase 0 + 23 baseline models). | `docs/DATABASE.md`, `docs/FOUNDATION_HANDOFF.md` |
| 3 | **Altered tables & `referral_updates`** | `settings` table gained `type`, `group`, `label`, `description`, `is_confirmed`, `confirmed_by`, `confirmed_at`, `source_question`. `response_templates` table gained `title`, `kind`, `created_by`. `referral_updates` exists from migration `2026_11_18_000001_referral_tracking_and_proof_photos.php` and was reused without duplication. | `docs/DATABASE.md`, `docs/FOUNDATION_HANDOFF.md` |
| 4 | **`App\Support\CaseStatus` constants** | `CaseStatus` currently defines 7 constants: `FILED = 'filed'`, `IN_MEDIATION = 'in_mediation'`, `SETTLED = 'settled'`, `UNSETTLED = 'unsettled'`, `ESCALATED = 'escalated'`, `DISMISSED = 'dismissed'`, `WITHDRAWN = 'withdrawn'`. `IN_CONCILIATION` is **not yet in code** (planned for Phase 0.5 alignment). | `docs/WORKFLOW.md`, `docs/SPEC_ADDENDUM.md`, `docs/TEAM_WORKSTREAMS.md` |
| 5 | **Permission Presets** | `config/permission_presets.php` defines 3 presets: `staff_full` (16 permissions), `case_encoder` (5 permissions), `reports_analyst` (3 permissions). `case_reviewer` does not exist yet in code (planned for Phase 0.5). Exactly 18 permission slugs are seeded. | `docs/TEAM_WORKSTREAMS.md`, `docs/ARCHITECTURE.md` |
| 6 | **Schema column types & values** | `summons.service_result` is varchar default `'pending'`; `summons.attendance` is varchar default `'pending'`; `summons.sequence_no` (int) and `override_note` (text, nullable) exist. `cases.source` default is `'walk_in'`; `cases.is_confidential` default is `0`. `fiscal_years.status` has values `'active'`, `'inactive'`, `'closed'`, `'locked'`. `number_sequences` has unique key (`fiscal_year_id`, `type`). `parties.residency_status` has 4 values (`resident`, `non_resident`, `transient`, `unverified`). | `docs/SPEC_ADDENDUM.md`, `docs/DATABASE.md`, `docs/WORKFLOW.md` |
| 7 | **Existing 7-day follow-up tests** | Two tests in `tests/Feature/ReferralAndProofWorkflowTest.php` assert 7 days: `test_referral_can_be_created_with_generated_reference_number` and `test_referral_service_creates_referral_with_proper_defaults`. These will need updating when `referral.follow_up_days` default shifts to 3 days in code. | `docs/TESTING.md`, `docs/WORKFLOW.md` |
| 8 | **Seeded baseline counts** | Categories: 6 (`infrastructure`, `sanitation`, `security`, `administrative`, `environmental`, `health`). Routing rules: 6. Offices: 6. Positions: 4 (`punong_barangay`, `barangay_secretary`, `lupon_member`, `barangay_tanod`). Case types: 6. Escalation destinations: 4 (`MTCC`, `City Prosecutor Office`, `PNP Station 8`, `CSWDO`). Permissions: 18 slugs. | `docs/PLACEHOLDERS.md`, `docs/FOUNDATION_HANDOFF.md` |
| 9 | **Demo credentials** | Demo accounts from `DemoDataSeeder.php`: `admin@calarian.gov.ph` (Captain), `secretary@calarian.gov.ph` (Secretary), `staff@calarian.gov.ph` (Desk Officer), `clerk@calarian.gov.ph` (Lupon Clerk), `reviewer@calarian.gov.ph` (Kagawad Peace & Order), `cswdo@zamboanga.gov.ph` (CSWDO Agency) (all password `password123`). Legacy `@barangay.test` accounts and seeders deleted. | `docs/TESTING.md`, `docs/FOUNDATION_HANDOFF.md` |
| 10 | **SPEC_ADDENDUM Section 11 items** | `BarangayCase::visibleTo()` scope exists on model. Private disk configured in `config/filesystems.php`. Mail keys present in `.env.example`. `ReferralService::dueForFollowUp()` implemented. `<x-ui.global-search>` Blade component exists. Seeded response templates and privacy notice present. `fiscal_year_id` stamping implemented in `ReportController@store`. `User` does not yet implement `MustVerifyEmail`. | `docs/SPEC_ADDENDUM.md`, `docs/TASKS.md` |
