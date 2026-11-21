# PHASE 0: FOUNDATION TASK (give this whole file to the AI assistant)

> **Who runs this:** the team lead only, on branch `feature/phase0-foundation`.
> **Why it exists:** four teammates will build features in parallel afterwards. They can only avoid overlapping if the shared pieces (database schema, settings, permissions, numbering, routes, layout, UI components, seeders, test helpers) already exist and are agreed. This task builds exactly that, plus empty "stub" pages for everything the team will build.
> **Rule:** build structure and contracts. Do **not** build the feature screens that belong to the workstreams listed in `docs/TEAM_WORKSTREAMS.md`.

---

## 0. Your role and ground rules

You are a senior Laravel engineer working in the repository **Barangay-Reporting-System-1** (Laravel 13, PHP 8.4, Blade, Tailwind CSS v4, FontAwesome Free 7, SQLite for dev/tests, MySQL for production).

Product principle: *"The AI suggests, the rules route, the officials decide."*

**Before writing code, read these files completely:** `docs/PROJECT.md`, `ARCHITECTURE.md`, `DATABASE.md`, `WORKFLOW.md`, `API.md`, `CONVENTIONS.md`, `TESTING.md`, `TASKS.md`, and `docs/TEAM_WORKSTREAMS.md`, `docs/PLACEHOLDERS.md`, `docs/SPEC_ADDENDUM.md`, `docs/ACADEMIC_DOCS_UPDATE.md` (the last four are provided with this task).

> **Precedence:** `docs/SPEC_ADDENDUM.md` overrides this file wherever they differ. Its section 11 is a checklist of additions to this task (extra schema, permissions, stubs, routes, private disk, email verification, confidential-case scope).

**Non-negotiable rules**
1. Follow `CONVENTIONS.md`: strict types, return types, constructor promotion, thin controllers, Form Requests, `DB::transaction()` for multi-table writes, Pint formatting, no manual edits to `composer.lock`, `package-lock.json`, `public/build/`.
2. **Do not break the existing system.** All existing routes, statuses, and tests must keep working. Existing `role:staff` / `role:admin` routes stay as they are.
3. Work in **small commits, one per numbered step below**. After each step run `php artisan test` and `./vendor/bin/pint --test`. Do not continue to the next step with a red test suite.
4. **Never hardcode values the barangay has not confirmed.** Anything listed in `docs/PLACEHOLDERS.md` lives in the database (settings or seed data) and is flagged `is_confirmed = false`. Never write `if ($count >= 3)`; read the setting.
5. If the existing code contradicts this brief (different class names, existing audit helper, existing layout), **prefer the existing code**, adapt the brief, and record the difference in `docs/FOUNDATION_DISCOVERY.md`.
6. PHP reserves the word `Case`. The case model is **`BarangayCase`** (table `cases`).
7. Do not invent legal rules. Case lifecycle, summons limit, deadlines, and escalation targets are **drafts pending barangay confirmation**.

**Final deliverable:** a merged-ready branch where (a) the app boots from an empty database into the setup wizard stub, (b) every route and nav item in section 6 exists and renders a stub page, (c) every table in section 2 is migrated with models and factories, (d) all services in section 4 exist with the stated signatures, and (e) the full test suite is green.

---

## Step 0: Discovery (no feature code)

Run and record in `docs/FOUNDATION_DISCOVERY.md`:
- `php artisan test`, `./vendor/bin/pint --test`, `php artisan route:list` (baseline counts).
- Where audit rows are written today (search for `audit_logs`, `AuditLog`). You will wrap that in `AuditLogger` (step 4).
- The current admin layout file, sidebar/nav markup, and any existing Blade components.
- How `EnsureRole` works; how `users.role` is defined in migrations.
- How referral numbers `REF-YYYY-NNNN` are generated and whether a table for agency updates / referral logs already exists (search `referral` migrations).
- How seeders are organised (`DatabaseSeeder` and others) and what demo data they create.
- Whether `config/reports.php` holds the follow-up interval and SLA values.

Stop and ask only if something blocks the plan. Otherwise continue.

---

## Step 1: Install every dependency now (so teammates never touch lock files)

```
composer require barryvdh/laravel-dompdf openspout/openspout
```
- No other new packages without lead approval. No JavaScript packages are needed.
- Commit the lock file changes in this step only.

---

## Step 2: Database schema (all new tables, one place)

Create migrations with timestamp prefix `2026_11_22_0001NN_...` (NN increments). Use the conventions in `DATABASE.md` (snake_case plural tables, `timestamps()`, indexed foreign keys, `foreignId()->constrained()`). Types below are shorthand: `str` = string, `txt` = text, `int` = unsigned integer, `bool`, `date`, `dt` = timestamp, `json`, `fk table` = foreignId constrained, `?` = nullable.

### 2.1 Changes to existing tables
| Table | Change |
| :--- | :--- |
| `users` | `role` from enum to `str(20)` values `admin`, `staff`, `agency` (keep default `staff`, handle SQLite and MySQL); add `office_id fk offices ?` (agency users), `must_change_password bool default false`, `last_login_at dt ?` |
| `reports` | add `fiscal_year_id fk fiscal_years ?` indexed |
| `referrals` | add `fiscal_year_id fk fiscal_years ?` indexed |

**Backfill (same migration set, idempotent):** create a `fiscal_years` row for every distinct `created_at` year found in `reports`/`referrals`; stamp the rows; mark the current year `active`, earlier years `closed` (not locked). If any user with role `admin` exists, set setting `system.setup_completed = true` (protects existing installs). A fresh empty database leaves it `false`.

### 2.2 Administration tables
**`settings`**: `key str unique`, `value txt ?`, `type str` (`string|int|bool|json|date`), `group str indexed`, `label str`, `description txt ?`, `is_confirmed bool default false`, `confirmed_by fk users ?`, `confirmed_at dt ?`, `source_question str ?` (e.g. `Q22`).

**`fiscal_years`**: `year smallint unique`, `label str`, `starts_on date`, `ends_on date`, `status str` (`upcoming|active|closed`), `is_locked bool default false`, `closed_at dt ?`, `closed_by fk users ?`, `close_note txt ?`.

**`number_sequences`**: `type str`, `fiscal_year_id fk ?`, `last_number int default 0`, unique(`type`,`fiscal_year_id`).

**`positions`**: `name str`, `code str unique`, `sort_order int default 0`, `is_signatory_default bool default false`, `is_active bool default true`, `is_confirmed bool default false`.

**`personnel`**: `user_id fk users ? (nullOnDelete)`, `position_id fk positions`, `full_name str`, `contact_number str ?`, `email str ?`, `term_start date ?`, `term_end date ?`, `is_signatory bool default false`, `signature_path str ?`, `is_active bool default true`, `notes txt ?`.

**`signatory_assignments`**: `document_type str`, `role_label str` (`prepared_by|noted_by|approved_by`), `position_id fk positions`, `sort_order int default 0`, `is_confirmed bool default false`, unique(`document_type`,`role_label`).

**`permissions`**: `slug str unique`, `label str`, `group str`. **`permission_user`**: `user_id`, `permission_id` (composite primary key, cascade).

**`response_templates`**: `title str`, `kind str` (`public_note|info_request|referral|case_notice`), `category_id fk ?`, `body txt`, `is_active bool default true`, `created_by fk users ?`.

**`report_templates`**: `key str unique`, `title str`, `description txt ?`, `source str` (`problem_reports|referrals|cases|summons|personnel|mixed`), `columns json ?`, `filters json ?`, `orientation str default portrait`, `signatory_document_type str ?`, `is_active bool default true`, `is_confirmed bool default false`.
**`generated_reports`** (log): `report_template_id fk ?`, `report_key str`, `params json ?`, `format str`, `fiscal_year_id fk ?`, `generated_by fk users ?`, `created_at`.

### 2.3 Agency tables
**`agency_registrations`**: `user_id fk users`, `office_id fk offices ?`, `agency_name_claimed str`, `position_title str`, `contact_number str`, `id_document_path str`, `status str` (`pending|approved|rejected|suspended`), `reviewed_by fk users ?`, `reviewed_at dt ?`, `decision_note txt ?`.
**`referral_updates`**: `referral_id fk cascade`, `user_id fk ?`, `body txt`, `new_status str ?`, `visible_to_resident bool default false`. *(Create only if discovery shows no equivalent table. If one exists, reuse it and record that.)*

### 2.4 Case module tables
**`case_types`**: `name str`, `slug str unique`, `description txt ?`, `sort_order int default 0`, `is_active bool default true`, `is_confirmed bool default false`.

**`parties`** (a reusable person record): `full_name str indexed`, `alias str ?`, `birth_date date ?`, `sex str ?`, `civil_status str ?`, `occupation str ?`, `contact_number str ?`, `email str ?`, `address_line str ?`, `purok_id fk puroks ?`, `home_barangay str ?`, `home_city str ?`, `residency_status str default 'unverified'` (`resident|non_resident|unverified`), `residency_basis str ?`, `residency_verified_by fk users ?`, `residency_verified_at dt ?`, `id_type str ?`, `id_number str ?`, `extra json ?`.

**`cases`** (model `BarangayCase`): `case_number str unique`, `fiscal_year_id fk`, `case_type_id fk ?`, `source str default 'walk_in'` (`walk_in|online_report|referral|other`), `source_report_id fk reports ?`, `title str`, `narrative txt`, `filed_on date`, `incident_date date ?`, `incident_location txt ?`, `purok_id fk ?`, `status str indexed default 'filed'`, `encoded_by fk users`, `assigned_to fk users ?`, `settled_on date ?`, `settlement_terms txt ?`, `outcome_note txt ?`, `is_confidential bool default false` (see `SPEC_ADDENDUM.md` section 7).
**`case_parties`**: `case_id fk cascade`, `party_id fk`, `role str` (`complainant|respondent|witness`), `residency_at_filing str ?` (snapshot), `representative_name str ?`, `notes txt ?`, unique(`case_id`,`party_id`,`role`).
**`case_status_logs`**: `case_id fk cascade`, `user_id fk ?`, `from_status str ?`, `to_status str`, `note txt ?`, `created_at`.
**`case_notes`**: `case_id fk cascade`, `user_id fk`, `body txt`.
**`case_field_configs`**: `entity str` (`party|case|summons`), `field_key str`, `label str`, `is_required bool default false`, `is_visible bool default true`, `applies_to str default 'all'` (`all|resident|non_resident`), `sort_order int default 0`, `is_confirmed bool default false`, unique(`entity`,`field_key`).

**`summons`**: `case_id fk cascade`, `respondent_case_party_id fk case_parties`, `sequence_no int`, `summons_number str unique ?`, `issued_on date`, `issued_by fk users`, `hearing_at dt ?`, `venue str ?`, `served_by_personnel_id fk personnel ?`, `served_on date ?`, `service_result str default 'pending'` (`pending|received|refused|not_found|other`), `attendance str default 'pending'` (`pending|appeared|no_show|rescheduled`), `remarks txt ?`, `voided_at dt ?`, `void_note txt ?`, `override_note txt ?`.
**`hearings`**: `case_id fk cascade`, `summons_id fk ?`, `scheduled_at dt`, `venue str ?`, `presided_by_personnel_id fk personnel ?`, `status str default 'scheduled'` (`scheduled|held|cancelled|reset`), `outcome_note txt ?`.
**`escalation_destinations`**: `name str`, `office_id fk offices ?`, `sort_order int default 0`, `is_active bool default true`, `is_confirmed bool default false`.
**`case_escalations`**: `case_id fk cascade`, `escalation_destination_id fk`, `escalated_by fk users`, `decided_by_personnel_id fk personnel ?`, `reason txt`, `override_note txt ?`, `summons_count_at_time int`, `referral_id fk referrals ?`, `escalated_at dt`.

Add Eloquent unit tests only for what is non-trivial (see step 10). Run `php artisan migrate:fresh` and `migrate:rollback` to prove both directions work on SQLite.

---

## Step 3: Models, relationships, factories, traits

- One model per new table (`Setting`, `FiscalYear`, `NumberSequence`, `Position`, `Personnel`, `SignatoryAssignment`, `Permission`, `ResponseTemplate`, `ReportTemplate`, `GeneratedReport`, `AgencyRegistration`, `ReferralUpdate`, `CaseType`, `Party`, `BarangayCase`, `CaseParty`, `CaseStatusLog`, `CaseNote`, `CaseFieldConfig`, `Summons`, `Hearing`, `EscalationDestination`, `CaseEscalation`). Define every relationship both ways (e.g. `BarangayCase::parties()`, `complainants()`, `respondents()`, `summons()`, `activeSummons()` (where `voided_at` is null), `hearings()`, `escalations()`, `statusLogs()`, `notes()`, `fiscalYear()`).
- `App\Models\Concerns\HasConfirmation` trait for models with `is_confirmed`: `scopeUnconfirmed()`, `markConfirmed(User $by)`. Apply to `Position`, `SignatoryAssignment`, `CaseType`, `CaseFieldConfig`, `EscalationDestination`, `ReportTemplate`.
- `App\Models\Concerns\BelongsToFiscalYear` trait: `fiscalYear()` relation and `scopeForFiscalYear($q, ?FiscalYear $fy)` (null = no filter). Apply to `Report`, `Referral`, `BarangayCase`.
- `User` additions: `hasPermission(string $slug): bool` (admin = always true), `permissions()`, `personnel()`, `office()`, `isAdmin()`, `isStaff()`, `isAgency()`.
- A factory for every new model with realistic Filipino sample names and Calarian puroks.
- `App\Support\CaseStatus` constants: `FILED`, `IN_MEDIATION`, `SETTLED`, `UNSETTLED`, `ESCALATED`, `DISMISSED`, `WITHDRAWN` plus `all()` and labels. **Draft, pending confirmation.**

---

## Step 4: Core services (implement fully unless marked STUB)

Put in `app/Services/`. Signatures are contracts other members will call, so **do not change them later without lead approval**.

| Service | Methods | Notes |
| :--- | :--- | :--- |
| `SettingsService` | `get(string $key, mixed $default = null): mixed`, `getInt/getBool`, `set(string $key, mixed $value, ?User $by = null): void`, `confirm(string $key, User $by): void`, `isConfirmed(string $key): bool`, `unconfirmed(): Collection` | Cast by `type`; cache with `Cache::rememberForever`, bust on write; audit every `set` and `confirm`. Add global helper `setting($key, $default)` |
| `FiscalYearService` | `active(): ?FiscalYear`, `forNewRecord(): FiscalYear` (throws `NoActiveFiscalYear` / `FiscalYearLocked`), `create(int $year, ?int $startMonth = null)`, `activate`, `close(FiscalYear, User, string $note)`, `reopen(FiscalYear, User, string $note)` | Exactly one active year. Closing sets `is_locked = true`. **Lock rule:** no new records may be stamped to a locked year; finalized records (resolved, no_action, merged, closed referral, settled case) of that year become read-only; open records stay editable and keep their original year |
| `FiscalYearContext` | `viewing(): ?FiscalYear` (null = all years), `setViewing(?int $fiscalYearId)` | Session-backed; default = active year. Used by lists and the header switcher |
| `FiscalYearGuard` | `assertWritable(Model $record): void` | Throws if the record is finalized and its year is locked. Owners call it from their own services |
| `NumberingService` | `next(string $type, ?FiscalYear $fy = null): string`, `preview(string $type, ?FiscalYear $fy = null): string` | Types: `referral`, `case`, `summons`. Format from setting `numbering.{type}.format`, tokens `{YEAR}`, `{YY}`, `{SEQ:n}` (zero-padded). Reset per fiscal year when `numbering.reset = per_fiscal_year`. Use `lockForUpdate()` inside a transaction. `preview` does not consume a number |
| `PermissionService` | `all(): Collection`, `forUser(User): Collection`, `sync(User, array $slugs)`, `applyPreset(User, string $preset)` | Presets from `config/permission_presets.php` |
| `AuditLogger` | `log(?User $by, string $action, Model\|string $subject, array $old = [], array $new = []): void` | Wrap the **existing** audit mechanism found in discovery. All new code uses only this |
| `PersonnelService` | `create(array $data): Personnel` (**real**, needed by the setup wizard), `deactivate(Personnel, CarbonInterface $termEnd, string $note, User $by)` (**STUB**, WS2 completes) | `create` may also create a linked `User` when `create_login = true` |
| `SignatoryResolver` | `forDocument(string $documentType, ?CarbonInterface $asOf = null): array` | **STUB** returning `[]`. WS2 implements. Return shape: `[['label' => 'Prepared by', 'name' => '...', 'position' => '...', 'signature_path' => null], ...]`. Every consumer must render a blank signature line when the array is empty |
| `CaseStatusService` | `canTransition(string $from, string $to): bool`, `transition(BarangayCase $case, string $to, string $note, ?User $by = null): BarangayCase` | **STUB** (WS3 implements). Draft transition table is in section 9 |
| `SummonsCounterService` | `forCase(BarangayCase $case): array` returning `['issued' => int, 'limit' => int, 'remaining' => int, 'eligible' => bool]` | **STUB** returning zeros and the configured limit (WS4 implements). Rule: `issued` counts non-voided summons for the case; `eligible = issued >= setting('cases.summons_limit') && case status not in (settled, escalated, dismissed, withdrawn)` |
| `ReferralService` additions | `createFromCase(BarangayCase $case, EscalationDestination $destination, User $by): Referral` | **STUB** throwing `LogicException('Not implemented (WS5)')`. Also change referral number generation to call `NumberingService::next('referral')` and stamp `fiscal_year_id` (keep default format `REF-{YEAR}-{SEQ:4}` so existing tests pass) |

Also add:
- Stamp `fiscal_year_id` in `ReportController@store` through `FiscalYearService::forNewRecord()` (no workstream edits report intake).
- Gate setup in `AuthServiceProvider`/`AppServiceProvider`: `Gate::before` returns `true` for admins; every row in `permissions` is defined as a Gate ability by slug; `@can('cases.view')` and `->can()` work.
- `config/features.php`: `case_module` (`true`), `agency_portal` (`true`), `case_referral_handoff` (`false`), `hearing_email_reminders` (`false`). The setting `cases.enabled` also hides the case module at runtime.

---

## Step 5: Middleware

Register aliases in `bootstrap/app.php`:
| Alias | Behaviour |
| :--- | :--- |
| `setup.complete` | When `system.setup_completed` is false, redirect every web request to `setup.show`, except `/setup/*`, `/up`, assets, and `/login` for logged-in admin. When true, requests to `/setup/*` redirect to the login page. Apply globally to the `web` group |
| `permission:slug[,slug]` | Allows if the user has any listed permission (admin always passes), else HTTP 403 |
| `password.changed` | If `users.must_change_password`, redirect to `account.password.edit` (except that route and logout). Apply to staff/admin groups |
| `agency.active` | Allows only users with `role = agency`, `is_active = true`, and an approved registration; others see `agency.pending` |
| `EnsureRole` | Extend to accept `agency` (e.g. `role:agency`). `role:staff` still permits `staff` and `admin` only, never `agency` |

---

## Step 6: Routes, controllers, stub pages

Create these route files and require them from `routes/web.php`: `setup.php`, `settings.php`, `people.php`, `reports_center.php`, `cases.php`, `case_actions.php`, `agency.php`. Each route points at an **empty controller method that returns the shared stub view** (`resources/views/stub.blade.php`, using `<x-ui.page-header>` and showing "Coming soon, owned by {WS}"). Controllers live in the namespaces shown. Teammates fill in the bodies; they do **not** add or rename routes without lead approval.

| File | Owner | Routes (name = URL) |
| :--- | :--- | :--- |
| `setup.php` | WS1 | `setup.show` GET `/setup`; `setup.step` GET `/setup/{step}`; `setup.store` POST `/setup/{step}`; `setup.finish` POST `/setup/finish` |
| `settings.php` (`auth`, `role:staff`, plus the permission from `SPEC_ADDENDUM.md` section 2) | WS1 | `admin.settings.general.edit/update` `/admin/settings/general`; `admin.fiscal-years.index/store`, `.activate`, `.close`, `.reopen` `/admin/settings/fiscal-years`; `admin.numbering.edit/update` `/admin/settings/numbering`; `admin.pending.index`, `admin.pending.confirm` `/admin/settings/pending`; `admin.templates.index/create/store/edit/update/destroy` `/admin/settings/templates`; `year.switch` POST `/year/switch` (any authenticated user) |
| `people.php` (`auth`, `role:staff`, plus the permission from `SPEC_ADDENDUM.md` section 2) | WS2 | `admin.personnel.*` `/admin/personnel`; `admin.positions.*` `/admin/positions`; `admin.signatories.edit/update` `/admin/signatories`; `admin.users.index/create/store/edit/update`, `admin.users.deactivate`, `admin.users.reset-password` `/admin/users`; `account.password.edit/update` `/account/password` (any authenticated user) |
| `reports_center.php` (`permission:reports_center.view`) | WS2 | `admin.reports-center.index` `/admin/reports-center`; `.show` `/admin/reports-center/{key}`; `.export` POST `/admin/reports-center/{key}/export`; `.history` `/admin/reports-center/history` |
| `cases.php` (`auth`, `role:staff`, `permission:cases.view` unless stated) | WS3 | `admin.cases.index/create/store/show/edit/update`; `admin.cases.status` POST `/admin/cases/{case}/status`; `admin.cases.assign`; `admin.cases.notes.store`; `admin.parties.index/show/edit/update`; `admin.parties.search` GET `/admin/parties/search`; `admin.reports.convert-to-case` POST `/admin/reports/{report}/convert-to-case`; `admin.case-types.*` `/admin/settings/case-types`; `admin.case-fields.index/update` `/admin/settings/case-fields` |
| `case_actions.php` (`auth`, `role:staff`) | WS4 | `admin.cases.summons.create/store`, `.service`, `.attendance`, `.void`, `.print` under `/admin/cases/{case}/summons`; `admin.cases.hearings.store/update`, `.outcome`; `admin.hearings.index` `/admin/hearings`; `admin.cases.escalations.create/store`, `.letter`; `admin.cases.settlement.store`; `admin.escalation-destinations.*` `/admin/settings/escalation-destinations` |
| `agency.php` | WS5 | public: `agency.register.show/store` `/agency/register` (`throttle:5,1`); `agency.pending` `/agency/pending`; agency portal (`auth`, `role:agency`, `agency.active`): `agency.referrals.index/show/update`, `agency.referrals.message`; admin side (`permission:agencies.manage`): `admin.agencies.index/show`, `.approve`, `.reject`, `.suspend`, `.reinstate`, `.document` (streamed private file); staff side: `admin.referrals.send`, `admin.referrals.messages.store` |

The **case show page skeleton** (`resources/views/admin/cases/show.blade.php`) is created by this step with tabs (Overview, Parties, Summons, Hearings, Escalation, Notes, Timeline). The Summons, Hearings and Escalation tabs `@include` partials from `resources/views/admin/case-actions/partials/` (stub files created now, owned by WS4). The existing problem-report review page gets one hook: `@includeIf('admin.cases.partials.convert-button', ['report' => $report])` placed in the actions area of `review-workflow.blade.php` (renders nothing until WS3 creates the partial).

---

## Step 7: Layout, navigation, shared UI components

1. Reuse the existing admin layout. Replace the nav with a config-driven sidebar built from `config/navigation.php`. Each item: `label`, `route`, `icon` (FontAwesome), `group`, `permission` (optional), `feature` (optional), `roles`. The sidebar hides items the user cannot access and items whose feature flag or `cases.enabled` setting is off. Groups: **Dashboard**; **Problem Reports** (Queue, My cases, Referrals); **Cases** (Cases, Parties, Hearings); **Reports Center**; **Administration** (Personnel, Positions, Signatories, Users, Agencies); **Settings** (General, Fiscal years, Numbering, Categories, Offices, Routing rules, Priority weights, Case types, Case fields, Escalation destinations, Templates, Pending confirmation, Audit log). Agency users get their own minimal layout `layouts/agency.blade.php` with only "Referrals" and "Account".
2. Header additions: active-year switcher component `<x-year-switcher>` (dropdown "Viewing: FY 2026 / All years", posts to `year.switch`), and a yellow **"N items pending barangay confirmation"** link to `admin.pending.index` for admins when unconfirmed items exist.
3. UI components in `resources/views/components/ui/` (Tailwind v4 with the design tokens from `CONVENTIONS.md`): `page-header` (title, subtitle, action slot), `card`, `button` (variants primary, secondary, danger, ghost; sizes sm/md), `badge` (variants for each status colour), `table` (responsive wrapper with `overflow-x-auto`), `form-field` (label, hint, error, required marker), `modal` (Alpine-free, plain JS or `<dialog>`), `empty-state`, `flash` (success/error), `pending-badge` (shows "Pending confirmation" when a given setting key or model is unconfirmed), `draft-watermark` (diagonal "DRAFT: pending official format" for print views). All `x-ui.*` components and the living style page at `/admin/_ui` (non-production only) must strictly follow [**`docs/UI_UX_SPEC.md`**](file:///c:/githubFiles/Barangay-Reporting-System-1/docs/UI_UX_SPEC.md). The style page must show every pattern from `docs/UI_UX_SPEC.md` Section 2 & 3: Pattern A (list), Pattern B (form), Pattern C (detail with tabs), Pattern D (wizard), Pattern E (settings), Pattern F (modal), Pattern G (dashboard cards), and Pattern H (print sample).
4. Print layout `layouts/print.blade.php` with letterhead from settings: barangay name, city, address, seal (`barangay.seal_path`), plus the signatory block partial `partials/signatory-block.blade.php` that accepts the resolver array and prints blank lines when empty.

---

## Step 8: Seeders

Split the seeding:
- **`RequiredDataSeeder`** (safe for production, no demo accounts): existing categories, offices, routing rules, puroks 1 to 7, sensitive places, emergency contacts (as currently seeded); `permissions`; `positions` (Punong Barangay, Barangay Secretary, Barangay Treasurer, Kagawad, Lupon Chairperson, Lupon Secretary, Tanod, Administrative Staff); `signatory_assignments` placeholders for document types in `config/documents.php`; `case_types` (Physical injury, Noise disturbance, Boundary or property dispute, Theft, Domestic or family dispute, Threats or verbal abuse, Unpaid debt, Damage to property, Other); `case_field_configs` (party: full name required, alias, birth date, sex, civil status, occupation, contact number, address, purok, residency status required, home barangay shown for non-residents; case: case type required, filed on required, narrative required; summons: respondent, issued on, hearing at, venue, served by, service result); `escalation_destinations` (PNP Station, Prosecutor's Office, Other); `response_templates` (3 to 5 samples); `report_templates` (the 7 keys in `docs/TEAM_WORKSTREAMS.md` WS2); and every key in `docs/PLACEHOLDERS.md` into `settings` with `is_confirmed = false`. All of it is idempotent (`updateOrCreate`).
- **`DemoDataSeeder`**: calls `RequiredDataSeeder`, then creates fiscal year 2026 (active), accounts `admin@barangay.test`, `staff@barangay.test` (password `password`, staff gets preset `staff_full`), `encoder@barangay.test` (preset `case_encoder`), an approved agency user `agency@police.test` linked to an external office, a pending agency registration, personnel for each position, 10 demo problem reports across statuses, 4 demo parties, 3 demo cases (one with 2 summons, one with 3 summons eligible to escalate, one settled), and sets `system.setup_completed = true`.
- `DatabaseSeeder` calls only `RequiredDataSeeder`.
- Artisan commands: `app:demo` (migrate:fresh, then RequiredDataSeeder and DemoDataSeeder) and `app:empty` (migrate:fresh, then RequiredDataSeeder only, so the setup wizard shows). Document both in `TESTING.md`.

---

## Step 9: Configuration files

- `config/permission_presets.php`: `staff_full` (every existing staff ability: `reports.view/review/update_status/proof`, `referrals.manage`), `case_encoder` (`cases.view/create/edit/summons`, `reports_center.view`), `referral_officer` (`referrals.manage`, `reports.view`), `viewer` (`reports.view`, `cases.view`). Admin has everything by definition.
- Permission slugs (seed into `permissions`): `reports.view`, `reports.review`, `reports.update_status`, `reports.proof`, `referrals.manage`, `cases.view`, `cases.create`, `cases.edit`, `cases.summons`, `cases.escalate`, `personnel.manage`, `users.manage`, `settings.manage`, `fiscal_year.manage`, `templates.manage`, `agencies.manage`, `reports_center.view`, `reports_center.export`, `dashboard.view`, `audit.view`, `cases.view_confidential`, `backups.manage`.
- `config/documents.php`: document type keys used by signatories and print layouts: `referral_letter`, `summons`, `case_endorsement`, `certificate_to_file_action`, `monthly_report`, `annual_report`.
- Existing staff accounts must keep full access: a data migration gives every existing `staff` user the `staff_full` preset.

---

## Step 10: Test infrastructure

- In `tests/TestCase.php` add: `completeSetup()` (sets `system.setup_completed`, creates and activates fiscal year for the current year) **called by default in `setUp()`** so existing tests still pass; `actingAsAdmin()`, `actingAsStaff(array $permissions = null)` (null = `staff_full` preset), `actingAsEncoder()`, `actingAsAgency(?Office $office = null, string $status = 'approved')`; and `withoutSetup()` for wizard tests.
- New tests (required):
  - `SettingsServiceTest`: cast types, cache bust, confirm flow, audit written.
  - `NumberingServiceTest`: format tokens, per-year reset, no duplicates under repeated calls, `preview` does not consume.
  - `FiscalYearServiceTest`: one active year, close locks, reopen needs note, `forNewRecord` throws when locked.
  - `PermissionServiceTest`: admin bypass, preset application, `permission` middleware returns 403.
  - `SetupMiddlewareTest`: fresh install redirects to `/setup`; completed install blocks `/setup`; existing-admin migration sets completed.
  - `RouteStubsTest`: every route in step 6 responds 200 (or the correct redirect/403) for the right actor and 403/redirect for wrong actors, including: agency cannot open `/admin/*`, staff cannot open `/admin/settings/*`, guest cannot open anything protected.
  - `SeederTest`: `RequiredDataSeeder` is idempotent; `DemoDataSeeder` creates the documented demo records.
- Full suite must stay green: existing 132 tests plus the new ones.

---

## Step 11: Documentation updates

Also apply the Blueprint and proposal edits in `docs/ACADEMIC_DOCS_UPDATE.md` only if assigned to you; they are owned by teammates (see its section 3).

Update (these files are **owned by the lead only** from now on):
- `PROJECT.md`: add the External Agency actor; revise Section 6 (Deferred) to say agency accounts and case management are now in scope (resident accounts, SMS, and voice remain deferred); add the Settings, Fiscal Year, Personnel, Cases, and Agency sections.
- `DATABASE.md`: add every table in section 2 and update the ER diagram.
- `API.md`: add every route from step 6 with owner and permission.
- `WORKFLOW.md`: add a section **"Case Lifecycle (DRAFT, pending barangay confirmation)"** with the table in section 9 below and a state diagram.
- `ARCHITECTURE.md`: add the new services and the middleware list.
- `TESTING.md`: new commands (`app:demo`, `app:empty`), new test files, and a revised demo script step that begins with the setup wizard.
- `TASKS.md`: mark Task 6 (Foundation) completed and add Tasks 7 to 11 (WS1 to WS5) as pending with the owner column.
- Copy `docs/TEAM_WORKSTREAMS.md` and `docs/PLACEHOLDERS.md` into the repo if not already present.

---

## Step 12: Verification and handoff

Run and report:
1. `php artisan migrate:fresh` then `php artisan app:empty` then open `/`: must redirect to `/setup` stub.
2. `php artisan app:demo`: log in as admin, staff, encoder, and agency; confirm each sees only the right sidebar items and that every stub page loads.
3. `php artisan test` (all green), `./vendor/bin/pint --test` (clean), `php artisan route:list` (count the new routes).
4. A **handoff report** in `docs/FOUNDATION_HANDOFF.md`: what was built, deviations from this brief and why, the list of stubs still to be filled and who owns each, how to run `app:demo`/`app:empty`, and any risks.

---

## 9. Draft case lifecycle (for the docs and for `CaseStatus`)

> Draft only. The real mediation stages come from the secretary's answers (Q21 to Q27). Statuses are code, but this table is expected to change.

| From | Allowed to | Notes |
| :--- | :--- | :--- |
| `filed` | `in_mediation`, `settled`, `dismissed`, `withdrawn` | Initial status |
| `in_mediation` | `settled`, `unsettled`, `dismissed`, `withdrawn` | |
| `unsettled` | `escalated`, `in_mediation`, `dismissed`, `withdrawn` | Eligible for escalation when the summons rule is met |
| `escalated` | `in_mediation` | Staff-only recovery, mandatory note, audit log |
| `settled` | `in_mediation` | Staff-only (for example repudiated settlement), mandatory note, audit log |
| `dismissed` | `in_mediation` | Staff-only, mandatory note, audit log |
| `withdrawn` | `in_mediation` | Staff-only, mandatory note, audit log |

Every transition writes a `case_status_logs` row and an audit log entry. Only an authorized staff member may change a case status; no automatic transitions (the system flags eligibility, the official decides).

---

## Acceptance checklist (the AI must tick every box in its final message)

- [ ] Fresh DB boots into `/setup` stub; existing installs are unaffected.
- [ ] All new tables, models, relationships, factories exist; `migrate:fresh` and `rollback` work.
- [ ] All services in step 4 exist with the exact signatures; stubs are clearly marked.
- [ ] Every route in step 6 exists, is correctly protected, and renders a stub.
- [ ] Sidebar, year switcher, pending-confirmation link, UI components, print layout exist.
- [ ] `RequiredDataSeeder`, `DemoDataSeeder`, `app:demo`, `app:empty` work.
- [ ] Every item in `docs/PLACEHOLDERS.md` is seeded into `settings` or seed tables with `is_confirmed = false`.
- [ ] Existing 132 tests pass; new tests pass; Pint clean.
- [ ] Docs updated; `FOUNDATION_DISCOVERY.md` and `FOUNDATION_HANDOFF.md` written.
- [ ] Zero feature screens built beyond stubs.
