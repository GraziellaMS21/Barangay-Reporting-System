# Workstream 6 (WS6, Stretch): Legacy Excel/CSV File Import

## 1. Scope & Ownership
- **Branch**: `feature/ws6-legacy-import`
- **Permission**: `imports.manage` (administrators pass automatically via `Gate::before` and `CheckPermission`; staff can be granted `imports.manage`, seeded via `Database\Seeders\Ws6ExtraSeeder`).
- **Feature Flag**: `config('features.legacy_import')` (`FEATURE_LEGACY_IMPORT`).
- **Owned Paths**:
  - `database/migrations/2026_12_01_600001_create_legacy_import_tables.php`
  - `app/Models/ImportBatch.php`, `app/Models/ImportRow.php`
  - `database/seeders/Ws6ExtraSeeder.php`
  - `app/Services/Imports/SpreadsheetFileService.php`, `app/Services/Imports/LegacyImportService.php`
  - `app/Http/Requests/Admin/Imports/*`
  - `app/Http/Controllers/Admin/Imports/ImportController.php`
  - `routes/imports.php` (plus 1-line require in `routes/web.php`)
  - `resources/views/admin/imports/{index,create,map,preview}.blade.php`
  - `tests/Feature/Imports/LegacyImportTest.php`

---

## 2. Implemented Rules (`docs/DECISIONS.md` Decision 66)
1. **Original Case Numbers Preserved**: Keeps the historical docket number verbatim. Any `case_number` already in use in `cases` (or duplicated within the batch) is flagged as a conflict error row and never overwrites existing records.
2. **Sequences Untouched**: Never invokes `NumberingService::next()`. Active fiscal year sequence counters in `number_sequences` remain untouched.
3. **Historical Fiscal Years Lifecycle**: Auto-creates missing `FiscalYear` records for historical years within the `records.lookback_years` window (default 4 years), imports while open, and automatically closes and locks historical fiscal years at the end of the batch while keeping the current active fiscal year active.
4. **Party Matching & Duplicate Safeguards**:
   - Exact normalized `full_name` + matching `birth_date` OR matching normalized `contact_number` links directly to the existing `Party` record.
   - Name-only matches without birth date or phone confirmation are placed on the preview review list as warnings (`likely_matches`) and create a separate `Party` record on commit so nothing merges automatically.
5. **30-Day Case Duplicate Detection**: Flags rows where an existing case has the same complainant and respondent normalized names with a `filed_on` or `incident_date` within 30 days.
6. **Legacy Status Mapping**: Maps legacy status strings to canonical `CaseStatus` constants (`filed`, `in_mediation`, `in_conciliation`, `settled`, `unsettled`, `escalated`, `dismissed`, `withdrawn`). Unrecognized values default to `filed` with an automatic `CaseNote` and `outcome_note` preserving the original status text. Blotter-only rows (`record_type = 'blotter'`) remain at `filed`.
7. **Audit Trail & Private Storage Cleanup**: Stores uploaded files on the `private` disk (`imports/`), logs `import_batch.uploaded`, `import_batch.previewed`, `import_batch.committed`, and `import_batch.rolled_back` via `AuditLogger`, and deletes the uploaded source file from private disk after commit.
8. **Blank Downloadable Template & Full Batch Rollback**: Provides downloadable `.xlsx` and `.csv` templates at `/admin/imports/template` and full transactional rollback (`POST /admin/imports/{batch}/rollback`) that deletes imported cases and newly created unlinked parties.
