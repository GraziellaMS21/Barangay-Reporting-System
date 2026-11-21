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
- `database/seeders/DatabaseSeeder.php` orchestrates:
  `PurokSeeder`, `EmergencyContactSeeder`, `CategorySeeder`, `OfficeSeeder`, `RoutingRuleSeeder`, `SettingSeeder`, `UserSeeder`, `DemoReportSeeder`, `CategoryQuestionSeeder`, `ResponseTemplateSeeder`, `ResidentSeeder`, `StaffSeeder`, `EngineSeeder`, `AdminSeeder`.
- **Phase 0 Contract**: Create `RequiredDataSeeder` (idempotent, no demo accounts, safe for production) and `DemoDataSeeder` (calls RequiredDataSeeder + adds demo accounts and records). Add `app:demo` and `app:empty` Artisan commands.

---

## 7. Existing Settings & Response Templates Tables
- `settings`: Exists in `2026_11_03_040000_phase_4_to_7_support_tables.php` with `id`, `key`, `value`, `timestamps`. Phase 0 migration will alter this table to add `type`, `group`, `label`, `description`, `is_confirmed`, `confirmed_by`, `confirmed_at`, and `source_question`.
- `response_templates`: Exists in `2026_11_10_000001_phase8_foundation.php` with `id`, `name`, `status`, `category_id`, `body`, `is_active`, `timestamps`. Phase 0 migration will alter this table to add `title`, `kind`, and `created_by`.

---

## 8. Configuration
- `config/reports.php`: Contains `referrals.follow_up_days` (default 7), proof photo limits, status options, and channels.
- **Phase 0 Contract**: Move dynamic settings into `settings` table as outlined in `docs/PLACEHOLDERS.md` while maintaining fallback defaults.
