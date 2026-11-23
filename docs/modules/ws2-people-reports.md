# WS2 Module Notes — People, Access, Signatories & Reports Center

**Branch:** `feature/ws2-people-reports`  
**Owner:** WS2

## Implemented Deliverables

1. **Positions & Personnel (`/admin/positions`, `/admin/personnel`)**
   - Full CRUD for official barangay positions with system code validation (`^[a-z][a-z0-9_]*$`), default signatory flags, active status toggling, and unconfirmed placeholder badges.
   - Personnel directory with search, position/status filters, term dates, signatory authorization, PNG signature upload to the `private` disk (`signatures/`), Lupon member login creation with `case_encoder` preset (`D1`, `A Q15`), and deactivation with mandatory note and audit logging (`PersonnelService::deactivate`).

2. **Signatories Matrix & `SignatoryResolver` (`/admin/signatories`)**
   - Interactive matrix assigning `Prepared by`, `Noted by`, and `Approved by` positions per document type (`referral_letter`, `summons`, `case_endorsement`, `certificate_to_file_action`, `settlement_agreement`, `monthly_report`, `annual_report`) with live incumbent previews.
   - `SignatoryResolver::forDocument(string $documentType, ?CarbonInterface $asOf = null, ?BarangayCase $case = null)` resolves active officials within term dates, supports case-specific Pangkat Chairman override for `certificate_to_file_action` (`A Q5`, `docs/DECISIONS.md` Decision 62), embeds private signature images as base64 data URIs for printables (`docs/ARCHITECTURE.md`), and returns `[]` when unconfigured so views render blank signature lines.

3. **Users, Granular Permissions & Forced Password Change (`/admin/users`, `/account/password`)**
   - Supports multiple system administrators (Punong Barangay and Barangay Secretary, `A Q6`) with last-active-admin protection.
   - Preset application (`staff_full`, `case_encoder`, `reports_analyst`, `case_reviewer`) and grouped granular permission matrix with `PermissionService::sync()`.
   - Temporary password generation (`must_change_password = true`) and forced password update screen at `/account/password`.

4. **Reports Center (`/admin/reports-center`)**
   - Implements all 8 canonical report definitions (`docs/PROJECT.md` § 8):
     - `kp_accomplishment` (Yearly/Monthly DILG KP Accomplishment Report)
     - `case_summary` (Monthly Case Summary by Type to DILG — respects `BarangayCase::visibleTo($user)` for row-level data while keeping aggregate totals)
     - `purok_category_matrix` (Problems per Purok and Category matrix, `A Q34`)
     - `problem_reports_summary`
     - `referral_register`
     - `summons_log`
     - `personnel_list`
     - `year_over_year`
   - Supports live preview, paper-size aware Print view (`reports.default_format = print`, `reports.paper_size`), PDF, Excel (`xlsx`), and CSV exports, logging every export to `generated_reports` and `AuditLogger`.
