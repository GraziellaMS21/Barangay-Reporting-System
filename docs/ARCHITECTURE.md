# System Architecture & Technical Design

Architectural specification, service layer boundaries, data flow pipelines, and security trust models for the **Barangay Problem Reporting and Management System**.

---

## 1. Architectural Overview

The application follows a **Modular Monolithic MVC Pattern** built on Laravel 13, augmented with an explicit Domain Service Layer. Controller actions remain thin, delegating domain decisions, state transitions, external AI queries, and routing logic to dedicated service classes.

```
       +-------------------------------------------------------------+
       |                     Resident / Public UI                   |
       |  (Report Form, Tracking Status, Confirmation, Public Map)   |
       +------------------------------+------------------------------+
                                      | HTTP Requests / CSRF
                                      v
       +-------------------------------------------------------------+
       |                  Laravel Routing & Middleware               |
       |  (routes/resident.php, routes/staff.php, routes/admin.php)  |
       +------------------------------+------------------------------+
                                      |
                                      v
       +-------------------------------------------------------------+
       |                 Controller Presentation Layer               |
       |        (ReportController, TrackController, Admin/*)         |
       +------------------------------+------------------------------+
                                      | Calls Domain Services
                                      v
       +-------------------------------------------------------------+
       |                       Domain Service Layer                  |
       |  - GeminiService (Async AI Classification & Photo Auditing) |
       |  - ReportStatusService (Finite State Machine Transitions)    |
       |  - RoutingService (Jurisdiction Rules & Office Assignment)  |
       |  - PriorityService (Risk Weights, SLA & Score Calculation)  |
       |  - ReferralService (External Agency Transmission & Letters) |
       |  - ProofPhotoService (Multi-image Resolution Evidence)      |
       |  - DashboardAnalyticsService & HotspotService               |
       +------------------------------+------------------------------+
                                      | Eloquent ORM
                                      v
       +-------------------------------------------------------------+
       |                 Data Storage & External Services            |
       |  - Database: SQLite (Dev/Test) / MySQL (Production Target)  |
       |  - AI API: Google Gemini REST API (gemini-2.5-flash)        |
       |  - Mapping: OpenStreetMap Tile Servers (via Leaflet.js)     |
       |  - Mail: Laravel Mail (Log Driver / SMTP) & NotificationLog |
       +-------------------------------------------------------------+
```

---

## 2. Service Layer Responsibility Map

| Service Class | Location | Primary Responsibilities |
| :--- | :--- | :--- |
| **`GeminiService`** | `app/Services/GeminiService.php` | Handles prompt serialization, HTTP timeouts (30s), fallback model failover, photo description, and response sanitization. |
| **`ReportStatusService`** | `app/Services/ReportStatusService.php` | Enforces state transition matrix integrity, database transactions, and dispatches `ReportStatusChanged` events. |
| **`RoutingService`** | `app/Services/RoutingService.php` | Evaluates category rules against jurisdiction authority (Mode A internal dispatch vs. Mode B external referral). |
| **`PriorityService`** | `app/Services/PriorityService.php` | Computes dynamic numerical priority score based on risk weights, sensitivity proximity, and situational flags. |
| **`ReferralService`** | `app/Services/ReferralService.php` | Generates unique `REF-YYYY-NNNN` referral records, tracks follow-up dates, formats printable letters, and manages agency communication logs. |
| **`ProofPhotoService`** | `app/Services/ProofPhotoService.php` | Validates, optimizes, and stores multiple resolution photos (up to 5 images, max 5 MB each). |
| **`DuplicateService`** | `app/Services/DuplicateService.php` | Performs Haversine distance calculations (100–150m) and text similarity comparisons against open reports. |
| **`HotspotService`** | `app/Services/HotspotService.php` | Groups coordinates into geospatial grid clusters to identify high-density incident locations. |
| **`NotificationService`** | `app/Services/NotificationService.php` | Wraps mail dispatches in robust exception handling and logs delivery status to `notification_logs`. |

---

## 3. End-to-End Data Flow

```mermaid
sequenceDiagram
    autonumber
    actor Resident
    participant App as Laravel Application
    participant Gemini as Google Gemini API
    actor Staff
    actor ExternalAgency as External Agency

    Resident->>App: Submits report (Category, Description, GPS/Pin, Photos)
    App-->>Resident: Returns unique tracking code (BRG-XXXXXX)
    Note over App: Status = submitted

    App->>Gemini: Async triage request (No reporter PII sent)
    alt Gemini Responds Successfully
        Gemini-->>App: Category, urgency, summary, emergency flag, photo audit
        App->>App: Save to report_ai_results (ai_status = done)
    else Gemini Fails or Times Out (30s)
        App->>App: Mark ai_status = failed (report remains intact for manual triage)
    end

    Staff->>App: Opens review page (/admin/reports/{id})
    Note over Staff: Reviews 3-way comparison (Resident vs AI vs Final)
    
    alt Mode A: Within Barangay Authority
        Staff->>App: Assigns internal office & SLA, clicks "Confirm & Route"
        App->>App: Transition to routed -> in_progress
    else Mode B: Beyond Barangay Authority
        Staff->>App: Prepares referral letter (REF-YYYY-NNNN)
        App->>ExternalAgency: Logs referral and tracks follow-up date
        App->>App: Transition to referred
    else Non-Actionable / False Alarm
        Staff->>App: Selects reason code (e.g. invalid_report)
        App->>App: Transition to no_action (Mandatory explanation)
    end

    Staff->>App: Uploads proof photos & notes when work complete
    App->>App: Transition to awaiting_confirmation
    Resident->>App: Views proof on tracking page, clicks "Confirm Fixed"
    App->>App: Transition to resolved (Resident submits 1-5 rating)
```

---

## 4. Trust Boundaries & AI Operational Limits

1. **Strict Advisory Mandate**: The AI is completely isolated from binding state modifications. It has zero authority to execute database writes to final columns on `reports`, transition statuses, merge duplicates, or close reports.
2. **Human-in-the-Loop Review Gate**: Staff must explicitly review and confirm category and urgency before routing an incident. AI values are rendered with clear visual badges (`AI Suggested`, `Matches`, `Differs`).
3. **Emergency Containment**: The AI cannot contact emergency services (police, fire, CDRRMO). It only sets an advisory `emergency_flagged` status, which triggers on-screen 911 dialing guidance for the resident and sends priority alerts to local officials.

---

## 5. Security & Network Architecture

- **Session Authentication**: Staff and Administrator access is guarded by Laravel's built-in session authentication and `role` middleware (`EnsureRole:staff` or `EnsureRole:admin`).
- **Throttling & Abuse Prevention**:
  - Public submission routes are rate-limited (`throttle:10,1`).
  - Staff login endpoint is throttled (`throttle:5,1`).
- **CSRF & Input Sanitization**: Every HTML form includes `@csrf` verification tokens. All rich text and descriptions are escaped before rendering in Blade templates.
- **File Upload Hardening**: Uploaded photos are verified by MIME type (`image/jpeg`, `image/png`, `image/webp`), sanitized on write, and stored in isolated storage disks (`storage/app/public/reports/`).
