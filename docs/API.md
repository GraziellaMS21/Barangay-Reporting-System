# API Contracts & Routing Reference

Reference documentation for web endpoints, RESTful parameters, authentication middleware, and payload schemas in **Barangay-Reporting-System-1**.

> [!NOTE]
> Endpoints and request parameters marked as **`(planned, Task N)`** represent target refactoring items defined in [**`docs/TASKS.md`**](file:///C:/githubFiles/Barangay-Reporting-System-1/docs/TASKS.md) that do not yet exist in code. All other endpoints represent currently active and verified routes.

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
- **Payload**: `note` (string, required, max: 1000).
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
| `POST` | `/admin/reports/{id}/review` | `Admin\ReportReviewController@saveReview` | Confirms review values and routes Mode A / Mode B. | Current *(transitioning to `no_action` with `no_action_reason`: **planned, Task 2**)* |
| `POST` | `/admin/reports/{id}/status` | `Admin\ReportController@updateStatus` | Manual status transition with mandatory note. | Current *(transitioning to `no_action` with `no_action_reason`: **planned, Task 2**; resume from `no_action`: **planned, Task 4**)* |
| `POST` | `/admin/reports/{id}/unmerge` | `Admin\ReportController@unmerge` | Unlinks accidental duplicate and returns report to `under_review`. | *(**planned, Task 4**)* |
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
