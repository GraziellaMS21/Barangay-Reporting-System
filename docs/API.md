# API Contracts & Routing Reference

Reference documentation for web endpoints, RESTful parameters, authentication middleware, and payload schemas in **Barangay-Reporting-System-1**.

> [!NOTE]
> All endpoints and request parameters documented below reflect active, verified routes and contracts in the application.

---

## 1. Route Partitions & Middleware Groups

Routing is modularized under `routes/` and registered via `routes/web.php`:
- `routes/resident.php`: Guest resident submissions, tracking, and resolution feedback.
- `routes/engine.php`: API utilities (e.g. nearby reports Haversine calculation).
- `routes/staff.php`: Authenticated staff queue, review cards, assignments, and updates.
- `routes/admin.php`: Administrative settings, user management, and security audit logs.

| Middleware | Target Audience | Behavior on Unauthenticated Access |
| :--- | :--- | :--- |
| `web` (default) | Public / Resident | Open guest access; CSRF validation on POST. |
| `auth` | Staff / Admin | Redirects unauthenticated requests to `/login`. |
| `role:staff` | Staff / Admin | Permits both `staff` and `admin` roles; rejects others with HTTP 403. |
| `role:admin` | Admin Only | Restricts to `admin` role (Barangay Captain/System Admin); rejects staff with HTTP 403. |

---

## 2. Public / Resident Routes (`routes/resident.php`)

### `GET /`
- **Controller**: `ReportController@create`
- **Description**: Renders the resident single-card incident reporting interface.

### `POST /report`
- **Controller**: `ReportController@store`
- **Middleware**: `throttle:10,1`
- **Payload**:
  - `category_id` (integer, required, exists in `categories`)
  - `description` (string, required, min: 10, max: 2000)
  - `lat` (numeric, nullable)
  - `lng` (numeric, nullable)
  - `purok_id` (integer, nullable, exists in `puroks`)
  - `landmark` (string, nullable, max: 255)
  - `people_at_risk` (boolean, nullable) — Safety toggle
  - `is_ongoing` (boolean, nullable)
  - `is_recurring` (boolean, nullable)
  - `answers` (array, nullable) — Dynamic question responses
  - `photos[]` (array of image files, optional, max: 5 files, 5 MB total)
  - `reporter_name` (string, nullable)
  - `reporter_contact` (string, nullable)
  - `reporter_email` (email, nullable)
  - `is_anonymous` (boolean, nullable)
  - `consent` (accepted, required)
- **Response**: Redirects to `/report/success/{tracking_code}`.

### `GET /report/success/{tracking_code}`
- **Controller**: `ReportController@success`
- **Description**: Displays confirmation card with the generated `BRG-XXXXXX` tracking code.

### `GET /track/{tracking_code?}`
- **Controller**: `TrackController@show`
- **Description**: Public incident progression tracker, official timeline, and feedback forms.

### `POST /track/{tracking_code}/clarification`
- **Controller**: `TrackController@replyClarification`
- **Payload**: `response` (string, required, max: 2000).
- **Description**: Resident response to a staff `needs_info` inquiry.

### `POST /track/{tracking_code}/confirm`
- **Controller**: `TrackController@confirmResolution`
- **Payload**: `rating` (integer, 1–5, optional), `comment` (string, optional).
- **Description**: Resident verifies work is completed; transitions report from `awaiting_confirmation` $\rightarrow$ `resolved`.

### `POST /track/{tracking_code}/reopen`
- **Controller**: `TrackController@reopen`
- **Payload**: `reason` (string, required, min: 10, max: 1000).
- **Description**: Resident disputes resolution; transitions report to `reopened`.

### `GET /map`
- **Controller**: `MapController@index`
- **Description**: Public transparency map showing active public reports (emergencies strictly excluded).

---

## 3. Engine & Utility Routes (`routes/engine.php`)

### `GET /api/nearby-reports`
- **Parameters**: `lat` (float, required), `lng` (float, required), `category_id` or `category` (optional).
- **Description**: Calculates Haversine distance and returns open incidents within $300\text{m}$.
- **Response Format**:
  ```json
  [
    {
      "tracking_code": "BRG-AB12CD",
      "category": "Drainage",
      "status": "In Progress",
      "distance_m": 85,
      "created_at": "2 hours ago"
    }
  ]
  ```

---

## 4. Staff Routes (`routes/staff.php`)

Protected by `auth` and `role:staff`.

| Method | URI | Controller Action | Purpose | Status / Notes |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/admin/reports` | `Admin\ReportController@index` | Main priority triage queue with filters and pagination. | Current |
| `GET` | `/admin/reports/my-cases` | `Admin\ReportController@index` | Filtered queue showing only cases assigned to current user. | Current |
| `GET` | `/admin/reports/{id}` | `Admin\ReportController@show` | 3-way comparative review page (Resident vs AI vs Final). | Current |
| `POST` | `/admin/reports/{id}/review` | `Admin\ReportReviewController@saveReview` | Confirms review values and routes Mode A / Mode B. | Current |
| `POST` | `/admin/reports/{id}/status` | `Admin\ReportController@updateStatus` | Manual status transition with mandatory note and reason code for `no_action`. | Current |
| `POST` | `/admin/reports/{id}/unmerge` | `Admin\ReportController@unmerge` | Unlinks accidental duplicate and returns report to `under_review`. | Current |
| `POST` | `/admin/reports/{id}/info-request` | `Admin\ReportController@requestInfo` | Posts clarification inquiry (`needs_info`). | Current |
| `POST` | `/admin/reports/{id}/proof-photos` | `Admin\ReportController@storeProofPhotos` | Uploads up to 5 multi-image resolution proofs. | Current |
| `POST` | `/admin/reports/{id}/notes` | `Admin\ReportController@storeInternalNote` | Appends staff-only internal discussion note. | Current |
| `GET` | `/admin/referrals` | `Admin\ReferralController@index` | Unified directory for internal dispatch and external agency referrals. | Current |
| `GET` | `/admin/referrals/{id}/letter` | `Admin\ReferralController@letter` | Printable formal agency endorsement letter. | Current |

---

## 5. Admin Routes (`routes/admin.php`)

Protected by `auth` and `role:admin`.

| Method | URI | Controller Action | Purpose | Status / Notes |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/admin/dashboard` | `Admin\DashboardController@index` | KPI metrics, category breakdowns, and AI insights. | Current |
| `GET` | `/admin/settings/categories` | `Admin\CategoryController@index` | Manage categories, question builder, and SLA hours. | Current |
| `GET` | `/admin/settings/offices` | `Admin\OfficeController@index` | Manage internal committees and external agencies. | Current |
| `GET` | `/admin/settings/routing-rules` | `Admin\RoutingRuleController@index` | Jurisdiction routing matrix and Mode A/B rules. | Current |
| `GET` | `/admin/settings/priority-weights`| `Admin\SettingController@priorityWeights` | Adjust numerical weights ($w_1 \dots w_5$) for priority scoring. | Current |
| `GET` | `/admin/audit-log` | `Admin\AuditLogController@index` | Immutable security audit trail of staff overrides and setting changes. | Current |
| `GET` | `/admin/reports/export` | `Admin\ReportController@exportCsv` | Streamed CSV download of reports matching queue filters. | Current |

---

## 6. Phase 0 Route Partitions & Endpoints

### A. Setup Wizard (`routes/setup.php`)
Guest / Admin setup route group with `web` middleware.

| Method | URI | Controller Action | Middleware / Access | Purpose |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/setup` | `SetupController@show` | `web` | 5-step interactive onboarding wizard. |
| `POST` | `/setup/step/{step}` | `SetupController@saveStep` | `web` | Validate & save specific setup step. |
| `POST` | `/setup/complete` | `SetupController@complete` | `web` | Finalize setup wizard & redirect to login. |

### B. Settings Module (`routes/settings.php`)
Protected by `auth`, `role:staff`, `permission:settings.manage` (or specific permission).

| Method | URI | Controller Action | Middleware / Permission | Purpose |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/admin/settings/general` | `SettingsController@editGeneral` | `permission:settings.manage` | Core barangay identity & hall location. |
| `PUT` | `/admin/settings/general` | `SettingsController@updateGeneral` | `permission:settings.manage` | Update general barangay settings. |
| `GET` | `/admin/fiscal-years` | `FiscalYearController@index` | `permission:fiscal_year.manage` | Fiscal year management list. |
| `POST` | `/admin/fiscal-years` | `FiscalYearController@store` | `permission:fiscal_year.manage` | Create new fiscal year. |
| `POST` | `/admin/fiscal-years/{id}/activate` | `FiscalYearController@activate` | `permission:fiscal_year.manage` | Set active fiscal year. |
| `POST` | `/admin/fiscal-years/{id}/lock` | `FiscalYearController@lock` | `permission:fiscal_year.manage` | Lock closed fiscal year (immutable). |
| `POST` | `/admin/fiscal-years/switch-view` | `FiscalYearController@switchView` | `auth` | Set session viewing fiscal year. |
| `GET` | `/admin/settings/numbering` | `NumberingController@edit` | `permission:settings.manage` | Configure numbering format masks. |
| `PUT` | `/admin/settings/numbering` | `NumberingController@update` | `permission:settings.manage` | Update numbering schemes. |
| `GET` | `/admin/settings/templates` | `TemplateController@index` | `permission:templates.manage` | Manage official response templates. |
| `GET` | `/admin/settings/case-types` | `CaseTypeController@index` | `permission:settings.manage` | Configure Katarungang Pambarangay case categories. |
| `GET` | `/admin/settings/case-fields` | `CaseFieldConfigController@index` | `permission:settings.manage` | Configure dynamic case and party form fields. |
| `GET` | `/admin/settings/escalation-destinations` | `EscalationDestinationController@index` | `permission:settings.manage` | Manage court/prosecutor escalation venues. |
| `GET` | `/admin/settings/agencies` | `AgencyManagementController@index` | `permission:agencies.manage` | Manage registered external partner agencies. |
| `GET` | `/admin/settings/backups` | `BackupController@index` | `permission:backups.manage` | Database backup management & export. |

### C. People & User Management (`routes/people.php`)
Protected by `auth`, `role:staff`, `permission:personnel.manage` or `permission:users.manage`.

| Method | URI | Controller Action | Middleware / Permission | Purpose |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/admin/personnel` | `PersonnelController@index` | `permission:personnel.manage` | Directory of barangay officials & tanods. |
| `GET` | `/admin/positions` | `PositionController@index` | `permission:personnel.manage` | Manage barangay positions and codes. |
| `GET` | `/admin/signatories` | `SignatoryController@edit` | `permission:personnel.manage` | Assign document signatories by position. |
| `PUT` | `/admin/signatories` | `SignatoryController@update` | `permission:personnel.manage` | Update document signatory roles. |
| `GET` | `/admin/users` | `UserController@index` | `permission:users.manage` | Manage staff accounts and permissions. |
| `GET` | `/admin/users/{id}/permissions` | `UserController@editPermissions` | `permission:users.manage` | Edit granular user permission checkboxes. |
| `PUT` | `/admin/users/{id}/permissions` | `UserController@updatePermissions` | `permission:users.manage` | Sync permissions / apply permission preset. |

### D. Reports Center (`routes/reports_center.php`)
Protected by `auth`, `role:staff`, `permission:reports_center.view` or `reports_center.export`.

| Method | URI | Controller Action | Middleware / Permission | Purpose |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/admin/reports-center` | `ReportsCenterController@index` | `permission:reports_center.view` | Report catalog and pre-built templates. |
| `GET` | `/admin/reports-center/{key}` | `ReportsCenterController@show` | `permission:reports_center.view` | Run report parameters and live table preview. |
| `POST` | `/admin/reports-center/{key}/export` | `ReportsCenterController@export` | `permission:reports_center.export` | Export report to PDF, Excel, or CSV. |
| `GET` | `/admin/reports-center/generated/{id}` | `ReportsCenterController@download` | `permission:reports_center.view` | Download previously generated report artifact. |

### E. Case Management & Intake (`routes/cases.php`)
Protected by `auth`, `role:staff`, `permission:cases.view`.

| Method | URI | Controller Action | Middleware / Permission | Purpose |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/admin/cases` | `CaseController@index` | `permission:cases.view` | Docketed cases list with status filters. |
| `GET` | `/admin/cases/create` | `CaseController@create` | `permission:cases.create` | New dispute blotter filing form. |
| `POST` | `/admin/cases` | `CaseController@store` | `permission:cases.create` | Persist dispute complaint and docket number. |
| `GET` | `/admin/cases/{id}` | `CaseController@show` | `permission:cases.view` | Case detail, parties, hearing schedule, and logs. |
| `GET` | `/admin/cases/{id}/edit` | `CaseController@edit` | `permission:cases.edit` | Edit complaint narrative and metadata. |
| `PUT` | `/admin/cases/{id}` | `CaseController@update` | `permission:cases.edit` | Update case details. |
| `GET` | `/admin/parties` | `PartyController@index` | `permission:cases.view` | Master party directory (complainants/respondents). |
| `GET` | `/admin/parties/{id}` | `PartyController@show` | `permission:cases.view` | Party profile, residency status, and case history. |
| `GET` | `/admin/secretary` | `SecretaryDashboardController@index` | `permission:cases.view` | Barangay Secretary blotter & hearing overview. |

### F. Case Procedural Actions (`routes/case_actions.php`)
Protected by `auth`, `role:staff`.

| Method | URI | Controller Action | Middleware / Permission | Purpose |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/admin/cases/{id}/summons/create` | `SummonsController@create` | `permission:cases.summons` | Form to generate summons 1, 2, or 3. |
| `POST` | `/admin/cases/{id}/summons` | `SummonsController@store` | `permission:cases.summons` | Issue and print formal summons notice. |
| `GET` | `/admin/hearings` | `HearingController@index` | `permission:cases.summons` | Calendar & roster of conciliation hearings. |
| `POST` | `/admin/cases/{id}/hearings` | `HearingController@store` | `permission:cases.summons` | Schedule mediation hearing proceeding. |
| `GET` | `/admin/cases/{id}/escalate` | `CaseEscalationController@create` | `permission:cases.escalate` | Certificate to File Action (CFA) / Court referral form. |
| `POST` | `/admin/cases/{id}/escalate` | `CaseEscalationController@store` | `permission:cases.escalate` | Certify case to court or higher agency. |
| `POST` | `/admin/cases/{id}/settle` | `CaseController@settle` | `permission:cases.edit` | Record amicable settlement agreement terms. |
| `POST` | `/admin/cases/{id}/notes` | `CaseNoteController@store` | `permission:cases.view` | Append investigation or hearing notes. |

### G. External Agency Portal (`routes/agency.php`)
Protected by `auth`, `role:agency`, `agency.active`.

| Method | URI | Controller Action | Middleware / Access | Purpose |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/agency/register` | `AgencyRegistrationController@create` | `guest` / `auth` | Self-service registration for outside agencies. |
| `POST` | `/agency/register` | `AgencyRegistrationController@store` | `guest` / `auth` | Submit credentials and official agency ID proof. |
| `GET` | `/agency/pending` | `AgencyRegistrationController@pending` | `auth` | Awaiting admin account approval landing page. |
| `GET` | `/agency/referrals` | `AgencyReferralController@index` | `role:agency, agency.active` | Outside agency referral inbox. |
| `GET` | `/agency/referrals/{id}` | `AgencyReferralController@show` | `role:agency, agency.active` | Referral case file & documentation packet. |
| `POST` | `/agency/referrals/{id}/accept` | `AgencyReferralController@accept` | `role:agency, agency.active` | Agency acknowledgment of referred case. |
| `POST` | `/agency/referrals/{id}/updates` | `AgencyReferralController@postUpdate` | `role:agency, agency.active` | Agency progress milestone or resolution update. |
| `GET` | `/agency/team` | `AgencyTeamController@index` | `role:agency, agency.active` | Team roster (Office Admin only). |
| `POST` | `/agency/team/invite` | `AgencyTeamController@invite` | `role:agency, agency.active` | Invite agency officer colleague. |

