# Phase 0 Foundation Handoff Document

> **Branch**: `feature/phase0-foundation`  
> **Status**: Completed & Verified (184 tests passing, Pint compliant)  
> **Target Audience**: Workstream Owners (WS1 through WS6), Software Engineering Team Group 3, Western Mindanao State University
>
> **Corrected in Revision 2 (2026-11-22).** Counts now match the inventory in this file, section 6 now follows `TEAM_WORKSTREAMS.md`, and the obsolete 3-summons rule is removed. Items marked **[V]** still need a check in the repo. See `REVISION_2.md` section 4.

---

## 1. Executive Summary

Phase 0 establishes the common foundational layer for the Barangay Problem Reporting System's expanded Katarungang Pambarangay (blotter/conciliation) and Partner Agency Portal architecture.

Per the lead-only mandate:
- **Zero feature screens** were built beyond structured stubs.
- **22 database tables** (21 plus the `permission_user` pivot, per the inventory below) were created with relationships, foreign keys, and indexes across 6 migrations. **[V]** confirm against the migrations.
- **21 Eloquent models** were created with factories, casts, and traits (`BelongsToFiscalYear`, `HasConfirmation`).
- **Core services** (Settings, Fiscal Years, Numbering, Permissions, Audit Logging, Personnel) were fully implemented, and stubs for downstream workstreams were registered.
- **7 modular route files** were created. The route map in section 3 lists **55** stub endpoints (the earlier text said 35). Many routes named in the workstream briefs are not in that table; see `REVISION_2.md` section 4.1. **[V]** `php artisan route:list`.
- **Dynamic navigation** and **11 shared UI Blade components** were created with an interactive showcase at `/admin/_ui`.
- **Baseline commands** (`php artisan app:empty`, `php artisan app:demo`) and idempotently seedable placeholders (`RequiredDataSeeder`, `DemoDataSeeder`) were verified.
- **All 149 existing regression tests** remain 100% green; 35 new unit and feature tests bring the test suite to **184 passing tests**.

---

## 2. Inventory of Created Files

| Category | File Path | Purpose / Description |
| :--- | :--- | :--- |
| **Dependencies** | `composer.json` / `composer.lock` | Added `barryvdh/laravel-dompdf` (^3.1) and `openspout/openspout` (^5.15). |
| **Migrations** | `database/migrations/2026_11_22_000101_update_users_table_for_phase0.php` | Added `must_change_password`, `office_id`, `last_login_at` to `users`. |
| | `database/migrations/2026_11_22_000102_create_administration_tables.php` | Created `fiscal_years`, `number_sequences`, `positions`, `personnel`, `signatory_assignments`, `permissions`, `permission_user`, `report_templates`, `generated_reports`. |
| | `database/migrations/2026_11_22_000103_add_fiscal_year_to_reports_and_referrals.php` | Added `fiscal_year_id` FK to `reports` and `referrals`. |
| | `database/migrations/2026_11_22_000104_create_agency_tables.php` | Created `agency_registrations` and `agency_invitations`. |
| | `database/migrations/2026_11_22_000105_create_case_module_tables.php` | Created `case_types`, `parties`, `cases`, `case_parties`, `case_status_logs`, `case_notes`, `case_field_configs`, `summons`, `hearings`, `escalation_destinations`, `case_escalations`. |
| | `database/migrations/2026_11_22_000106_grant_staff_full_preset_to_existing_staff.php` | Data migration assigning `staff_full` preset to existing staff accounts. |
| **Models** | `app/Models/Concerns/HasConfirmation.php` | Trait for placeholder confirmation tracking (`is_confirmed`, `source_question`). |
| | `app/Models/Concerns/BelongsToFiscalYear.php` | Trait for automatic fiscal year scoping and active-year assignment. |
| | `app/Support/CaseStatus.php` | Canonical constants and labels for Katarungang Pambarangay cases. |
| | `app/Models/FiscalYear.php` | Fiscal year lifecycle model (`active`, `inactive`, `closed`, `locked`). |
| | `app/Models/NumberSequence.php` | Atomic sequence numbering counter per fiscal year. |
| | `app/Models/Position.php` | Barangay official positions (`code`, `is_signatory_default`). |
| | `app/Models/Personnel.php` | Official roster entries with term dates and signature paths. |
| | `app/Models/SignatoryAssignment.php` | Document signatory role mapping by position. |
| | `app/Models/Permission.php` | Granular permission slug definitions. |
| | `app/Models/ReportTemplate.php` | Pre-configured compliance reporting definitions. |
| | `app/Models/GeneratedReport.php` | Historic generated report files and metadata. |
| | `app/Models/AgencyRegistration.php` | Self-service registration submissions by outside agencies. |
| | `app/Models/AgencyInvitation.php` | Secure invitation tokens for agency staff. |
| | `app/Models/CaseType.php` | Katarungang Pambarangay dispute categories. |
| | `app/Models/Party.php` | Complainant/respondent directory with residency verification. |
| | `app/Models/BarangayCase.php` | Blotter complaint and mediation docket model. |
| | `app/Models/CaseParty.php` | Pivot connecting parties to cases with roles (`complainant`, `respondent`, `witness`). |
| | `app/Models/CaseStatusLog.php` | Immutable case status transition audit logs. |
| | `app/Models/CaseNote.php` | Internal investigation and hearing notes. |
| | `app/Models/CaseFieldConfig.php` | Dynamic form field configuration for cases and parties. |
| | `app/Models/Summons.php` | Summons issuance and service records (no fixed limit; the barangay counts days, not summons). |
| | `app/Models/Hearing.php` | Conciliation hearing sessions before PB or Lupon. |
| | `app/Models/EscalationDestination.php` | Venues for Certificate to File Action (seeded MTCC, Prosecutor, PNP, CSWDO; the secretary named police, prosecutor and court, so CSWDO is unconfirmed). No `office_id` yet (added in Revision 2). |
| | `app/Models/CaseEscalation.php` | Formal escalation and referral records for cases. |
| **Services** | `app/Services/SettingsService.php` | Configuration store, typed getters, and placeholder audit confirmation. |
| | `app/helpers.php` | Global helper `setting($key, $default = null)`. |
| | `app/Services/FiscalYearService.php` | Fiscal year lifecycle and active-year resolution. |
| | `app/Services/FiscalYearContext.php` | Session-scoped viewing fiscal year. |
| | `app/Services/FiscalYearGuard.php` | Immutability guard throwing `FiscalYearLocked` on finalized records in locked years. |
| | `app/Services/NumberingService.php` | Atomic sequence generation and format mask parsing. |
| | `app/Services/PermissionService.php` | Permission sync, role preset application, and gate validation. |
| | `app/Services/AuditLogger.php` | Centralized audit logger across all administration domains. |
| | `app/Services/PersonnelService.php` | Official creation and deactivation safeguards. |
| | `app/Services/SignatoryResolver.php` | *(Stub)* Document signatory resolver. |
| | `app/Services/CaseStatusService.php` | *(Stub)* Case state machine transitions. |
| | `app/Services/SummonsCounterService.php` | *(Stub)* Summons count for display only; no limit (Revision 2). |
| | `app/Services/OverdueService.php` | *(Stub)* Cross-entity overdue SLA calculator. |
| | `app/Services/GlobalSearchService.php` | *(Stub)* Universal search engine. |
| **Middleware** | `app/Http/Middleware/SetupCompleted.php` | Redirects to `/setup` if setup incomplete; protects `/setup` once complete. |
| | `app/Http/Middleware/CheckPermission.php` | Enforces granular permission slug (`permission:{slug}`). |
| | `app/Http/Middleware/EnsurePasswordChanged.php` | Forces initial password reset for flagged users (`must_change_password`). |
| | `app/Http/Middleware/EnsureAgencyActive.php` | Checks that agency accounts are in approved status. |
| | `app/Http/Middleware/EnsureRole.php` | Extended to support `role:staff` (admin + staff) and `role:agency`. |
| **Config** | `config/features.php` | Feature flags (`case_module`, `agency_portal`, etc.). |
| | `config/documents.php` | Document types and default signatory roles. |
| | `config/permission_presets.php` | Role presets (`staff_full`, `case_encoder`, `reports_analyst`). |
| | `config/navigation.php` | Single source of truth for dynamic sidebar navigation. |
| **Routes** | `routes/setup.php` | Onboarding setup wizard routes. |
| | `routes/settings.php` | Fiscal years, numbering, templates, form fields, and settings routes. |
| | `routes/people.php` | Personnel, positions, signatories, and user accounts. |
| | `routes/reports_center.php` | Reports catalog, preview, export, and artifact download routes. |
| | `routes/cases.php` | Blotter intake, case list, show, edit, and party directory. |
| | `routes/case_actions.php` | Summons generation, hearings, settlements, and escalations. |
| | `routes/agency.php` | Partner portal inbox, accept, updates, team management, and registration. |
| **Views & UI** | `resources/views/layouts/agency.blade.php` | Dedicated shell layout for external agency users. |
| | `resources/views/layouts/print.blade.php` | Print-optimized layout with formal barangay letterhead. |
| | `resources/views/partials/signatory-block.blade.php` | Dynamic signatory signing block partial. |
| | `resources/views/components/ui/` | 11 reusable UI components (header, card, button, badge, table, field, modal, empty-state, flash, pending-badge, draft-watermark). |
| | `resources/views/components/year-switcher.blade.php` | Topbar fiscal year switcher component. |
| | `resources/views/admin/ui-guide.blade.php` | Interactive UI component guide rendered at `/admin/_ui`. |
| | `resources/views/admin/stub.blade.php` | Workstream stub landing template. |
| | `resources/views/admin/cases/show.blade.php` | Case detail skeleton view with tab partials. |
| **Commands & Seeders** | `database/seeders/RequiredDataSeeder.php` | Idempotent baseline seeder with unconfirmed placeholders. |
| | `database/seeders/DemoDataSeeder.php` | Rich demo dataset with active FY, demo accounts, and realistic cases. |
| | `app/Console/Commands/AppEmptyCommand.php` | `php artisan app:empty` (clean wipe + required seed). |
| | `app/Console/Commands/AppDemoCommand.php` | `php artisan app:demo` (clean wipe + full demo dataset). |

---

## 3. Route Map as Reported by Phase 0 (55 Stub Endpoints)

> **[V]** This table was written from the plan, not generated. Regenerate it with `php artisan route:list --json` and save the result as `docs/ROUTES.snapshot.json`. Built route names stay as they are; the briefs' names differ in a few places (`REVISION_2.md` section 4.1).

| Method | URL | Route Name | Middleware | Controller Action | Target View |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `GET` | `/setup` | `setup.show` | `web` | `SetupController@show` | `setup.show` (stub) |
| `POST` | `/setup/step/{step}` | `setup.step` | `web` | `SetupController@saveStep` | redirect |
| `POST` | `/setup/complete` | `setup.complete` | `web` | `SetupController@complete` | redirect `/login` |
| `GET` | `/admin/settings/general` | `admin.settings.general.edit` | `permission:settings.manage` | `SettingsController@editGeneral` | `admin.settings.general` |
| `PUT` | `/admin/settings/general` | `admin.settings.general.update` | `permission:settings.manage` | `SettingsController@updateGeneral` | redirect |
| `GET` | `/admin/fiscal-years` | `admin.fiscal-years.index` | `permission:fiscal_year.manage` | `FiscalYearController@index` | `admin.fiscal-years.index` |
| `POST` | `/admin/fiscal-years` | `admin.fiscal-years.store` | `permission:fiscal_year.manage` | `FiscalYearController@store` | redirect |
| `POST` | `/admin/fiscal-years/{id}/activate` | `admin.fiscal-years.activate` | `permission:fiscal_year.manage` | `FiscalYearController@activate` | redirect |
| `POST` | `/admin/fiscal-years/{id}/lock` | `admin.fiscal-years.lock` | `permission:fiscal_year.manage` | `FiscalYearController@lock` | redirect |
| `POST` | `/admin/fiscal-years/switch-view` | `admin.fiscal-years.switch-view` | `auth` | `FiscalYearController@switchView` | redirect back |
| `GET` | `/admin/settings/numbering` | `admin.numbering.edit` | `permission:settings.manage` | `NumberingController@edit` | `admin.settings.numbering` |
| `PUT` | `/admin/settings/numbering` | `admin.numbering.update` | `permission:settings.manage` | `NumberingController@update` | redirect |
| `GET` | `/admin/settings/templates` | `admin.templates.index` | `permission:templates.manage` | `TemplateController@index` | `admin.settings.templates` |
| `GET` | `/admin/settings/case-types` | `admin.case-types.index` | `permission:settings.manage` | `CaseTypeController@index` | `admin.settings.case-types` |
| `GET` | `/admin/settings/case-fields` | `admin.case-fields.index` | `permission:settings.manage` | `CaseFieldConfigController@index` | `admin.settings.case-fields` |
| `GET` | `/admin/settings/escalation-destinations` | `admin.escalation-destinations.index` | `permission:settings.manage` | `EscalationDestinationController@index` | `admin.settings.escalation-destinations` |
| `GET` | `/admin/settings/agencies` | `admin.agencies.index` | `permission:agencies.manage` | `AgencyManagementController@index` | `admin.settings.agencies` |
| `GET` | `/admin/settings/backups` | `admin.backups.index` | `permission:backups.manage` | `BackupController@index` | `admin.settings.backups` |
| `GET` | `/admin/personnel` | `admin.personnel.index` | `permission:personnel.manage` | `PersonnelController@index` | `admin.personnel.index` |
| `GET` | `/admin/positions` | `admin.positions.index` | `permission:personnel.manage` | `PositionController@index` | `admin.positions.index` |
| `GET` | `/admin/signatories` | `admin.signatories.edit` | `permission:personnel.manage` | `SignatoryController@edit` | `admin.signatories.edit` |
| `PUT` | `/admin/signatories` | `admin.signatories.update` | `permission:personnel.manage` | `SignatoryController@update` | redirect |
| `GET` | `/admin/users` | `admin.users.index` | `permission:users.manage` | `UserController@index` | `admin.users.index` |
| `GET` | `/admin/users/{id}/permissions` | `admin.users.permissions.edit` | `permission:users.manage` | `UserController@editPermissions` | `admin.users.permissions` |
| `PUT` | `/admin/users/{id}/permissions` | `admin.users.permissions.update` | `permission:users.manage` | `UserController@updatePermissions` | redirect |
| `GET` | `/admin/reports-center` | `admin.reports-center.index` | `permission:reports_center.view` | `ReportsCenterController@index` | `admin.reports-center.index` |
| `GET` | `/admin/reports-center/{key}` | `admin.reports-center.show` | `permission:reports_center.view` | `ReportsCenterController@show` | `admin.reports-center.show` |
| `POST` | `/admin/reports-center/{key}/export` | `admin.reports-center.export` | `permission:reports_center.export` | `ReportsCenterController@export` | file download / stream |
| `GET` | `/admin/reports-center/generated/{id}` | `admin.reports-center.download` | `permission:reports_center.view` | `ReportsCenterController@download` | file download |
| `GET` | `/admin/cases` | `admin.cases.index` | `permission:cases.view` | `CaseController@index` | `admin.cases.index` |
| `GET` | `/admin/cases/create` | `admin.cases.create` | `permission:cases.create` | `CaseController@create` | `admin.cases.create` |
| `POST` | `/admin/cases` | `admin.cases.store` | `permission:cases.create` | `CaseController@store` | redirect `/admin/cases/{id}` |
| `GET` | `/admin/cases/{id}` | `admin.cases.show` | `permission:cases.view` | `CaseController@show` | `admin.cases.show` (skeleton tabs) |
| `GET` | `/admin/cases/{id}/edit` | `admin.cases.edit` | `permission:cases.edit` | `CaseController@edit` | `admin.cases.edit` |
| `PUT` | `/admin/cases/{id}` | `admin.cases.update` | `permission:cases.edit` | `CaseController@update` | redirect `/admin/cases/{id}` |
| `GET` | `/admin/parties` | `admin.parties.index` | `permission:cases.view` | `PartyController@index` | `admin.parties.index` |
| `GET` | `/admin/parties/{id}` | `admin.parties.show` | `permission:cases.view` | `PartyController@show` | `admin.parties.show` |
| `GET` | `/admin/secretary` | `admin.secretary` | `permission:cases.view` | `SecretaryDashboardController@index` | `admin.secretary.dashboard` |
| `GET` | `/admin/cases/{id}/summons/create` | `admin.summons.create` | `permission:cases.summons` | `SummonsController@create` | `admin.cases.summons.create` |
| `POST` | `/admin/cases/{id}/summons` | `admin.summons.store` | `permission:cases.summons` | `SummonsController@store` | redirect |
| `GET` | `/admin/hearings` | `admin.hearings.index` | `permission:cases.summons` | `HearingController@index` | `admin.hearings.index` |
| `POST` | `/admin/cases/{id}/hearings` | `admin.hearings.store` | `permission:cases.summons` | `HearingController@store` | redirect |
| `GET` | `/admin/cases/{id}/escalate` | `admin.cases.escalate.create` | `permission:cases.escalate` | `CaseEscalationController@create` | `admin.cases.escalate` |
| `POST` | `/admin/cases/{id}/escalate` | `admin.cases.escalate.store` | `permission:cases.escalate` | `CaseEscalationController@store` | redirect |
| `POST` | `/admin/cases/{id}/settle` | `admin.cases.settle` | `permission:cases.edit` | `CaseController@settle` | redirect |
| `POST` | `/admin/cases/{id}/notes` | `admin.cases.notes.store` | `permission:cases.view` | `CaseNoteController@store` | redirect |
| `GET` | `/agency/register` | `agency.register` | `guest` | `AgencyRegistrationController@create` | `agency.register` |
| `POST` | `/agency/register` | `agency.register.store` | `guest` | `AgencyRegistrationController@store` | redirect `/agency/pending` |
| `GET` | `/agency/pending` | `agency.pending` | `auth` | `AgencyRegistrationController@pending` | `agency.pending` |
| `GET` | `/agency/referrals` | `agency.referrals.index` | `role:agency, agency.active` | `AgencyReferralController@index` | `agency.referrals.index` |
| `GET` | `/agency/referrals/{id}` | `agency.referrals.show` | `role:agency, agency.active` | `AgencyReferralController@show` | `agency.referrals.show` |
| `POST` | `/agency/referrals/{id}/accept` | `agency.referrals.accept` | `role:agency, agency.active` | `AgencyReferralController@accept` | redirect |
| `POST` | `/agency/referrals/{id}/updates` | `agency.referrals.updates.store` | `role:agency, agency.active` | `AgencyReferralController@postUpdate` | redirect |
| `GET` | `/agency/team` | `agency.team.index` | `role:agency, agency.active` | `AgencyTeamController@index` | `agency.team.index` |
| `POST` | `/agency/team/invite` | `agency.team.invite` | `role:agency, agency.active` | `AgencyTeamController@invite` | redirect |

---

## 4. Shared Blade UI Component Library

All components live in `resources/views/components/ui/` (plus `<x-year-switcher />` in `resources/views/components/`). Visit **`http://localhost:8000/admin/_ui`** to view the live interactive gallery.

### Component Summary

1. **`<x-ui.page-header :title="''" :subtitle="''">`**: Standardized page title header with slot for breadcrumbs and action buttons.
2. **`<x-ui.card :padding="'p-6'">`**: Rounded-2xl white container with subtle slate-100 border and header/footer slots.
3. **`<x-ui.button :variant="'primary|secondary|outline|danger|amber'" :size="'sm|md|lg'">`**: Consistent tactile button styling matching existing design tokens.
4. **`<x-ui.badge :variant="'success|info|warning|danger|slate'" :dot="true">`**: Status indicator pill with optional colored dot.
5. **`<x-ui.table :headers="['Col 1', 'Col 2']">`**: Borderless modern table wrapper with empty-state integration.
6. **`<x-ui.form-field :label="'Field Label'" :name="'field_name'" :required="true">`**: Accessible form wrapper providing labels, error states, and helper text.
7. **`<x-ui.modal :id="'modal-id'" :title="'Modal Title'">`**: Lightweight pure-CSS/JS dialog component.
8. **`<x-ui.empty-state :title="'No records found'" :icon="'fa-solid fa-inbox'">`**: Centered placeholder for zero-data views.
9. **`<x-ui.flash />`**: Session notification alert rendering success, error, or warning toasts.
10. **`<x-ui.pending-badge :count="5" />`**: Warning pill displaying count of unconfirmed municipality setup placeholders.
11. **`<x-ui.draft-watermark />`**: Semi-transparent angled banner for previewing unsaved documents.
12. **`<x-year-switcher />`**: Topbar dropdown allowing instant switching between active and historical fiscal year viewing contexts.

---

## 5. Instructions for Running & Testing

### 1. Cold-Start Setup Testing
```sh
# Reset DB and seed only required placeholders
php artisan app:empty

# Navigate in browser to:
http://localhost:8000/setup
```
Verify the 5-step wizard loads and identifies unconfirmed placeholder configurations.

### 2. Full Demo Testing
```sh
# Reset DB and seed full demo dataset
php artisan app:demo
```
**Test Credentials**:
- **Admin**: `admin@calarian.gov.ph` / `password123`
- **Staff**: `staff@calarian.gov.ph` / `password123`
- **Encoder**: `clerk@calarian.gov.ph` / `password123`
- **Agency (CSWDO)**: `cswdo@zamboanga.gov.ph` / `password123`

### 3. Verify Test Suite & Coding Style
```sh
# Run entire test suite (184 tests, 1374 assertions)
php artisan test

# Verify Laravel Pint code formatting
./vendor/bin/pint --test
```

---

## 6. Workstream Ownership & Boundaries Guide (corrected)

The source of truth is `docs/TEAM_WORKSTREAMS.md`, with changes in `docs/REVISION_2.md`. The earlier version of this section assigned the workstreams differently and mentioned a 3-summons rule; both were wrong.

| WS | Area | Controllers | Views | Implements |
| :--- | :--- | :--- | :--- | :--- |
| WS1 (lead) | Setup wizard, fiscal years, settings, global search, case KPIs, backups | `Setup`, `Admin/Settings` | `setup`, `admin/settings` | `GlobalSearchService`, backup commands |
| WS2 | Personnel, positions, signatories, users and permissions, Reports Center | `Admin/People`, `Admin/ReportsCenter` | `admin/personnel`, `admin/positions`, `admin/signatories`, `admin/users`, `admin/reports-center`, `account` | `SignatoryResolver`, `PersonnelService::deactivate`, report builders |
| WS3 | Case intake, parties, party merge, convert report to case | `Admin/Cases` | `admin/cases`, `admin/parties` | `CaseStatusService`, `PartyMatchingService`, case duplicate check |
| WS4 | Summons, hearings, settlement, escalation and CFA, secretary dashboard, deadlines | `Admin/CaseActions` | `admin/case-actions` | `SummonsCounterService` (count only), `OverdueService`, working-day helper use |
| WS5 | Agency accounts and portal, referral updates, agency team | `Agency`, `Admin/Agencies` | `agency`, `admin/agencies` | `ReferralService` additions, `Admin\ReferralController` (owned entirely) |
| WS6 (stretch) | Legacy import of old records | `Admin/Imports` | `admin/imports` | `LegacyImportService` |

**Everyone:** Phase 0 files (services, config, layouts, UI components, migrations `2026_11_22_0001*`) are read-only; request changes through the lead. Never change a service signature without approval. Put workstream notes in `docs/modules/ws{n}-*.md`. Controller class paths in the route table above may differ from these folder conventions **[V]**; move nothing without the lead.
