# Administrator First-Run & Setup Guide

**Barangay Problem Reporting & Katarungang Pambarangay System**  
*Operating Manual for the Barangay Secretary and System Administrators*

---

## Welcome to Your New Barangay System

This guide walks you through setting up the system from the very first time you turn it on until it is ready for public use by residents and staff. No technical programming knowledge is required. Follow the steps in the exact order presented below.

---

## Table of Contents
1. [Starting the Barangay Server](#1-starting-the-barangay-server)
2. [First-Time Setup Wizard (5 Steps)](#2-first-time-setup-wizard)
3. [Day-One Post-Setup Configuration (Order of Operations)](#3-day-one-post-setup-configuration)
   - [Step 3.1: Review and Confirm Baseline Settings](#step-31-review-and-confirm-baseline-settings)
   - [Step 3.2: Verify the Operational Fiscal Year](#step-32-verify-the-operational-fiscal-year)
   - [Step 3.3: Set Up Local Emergency Hotlines](#step-33-set-up-local-emergency-hotlines)
   - [Step 3.4: Register Barangay Officials and Staff (Personnel)](#step-34-register-barangay-officials-and-staff-personnel)
   - [Step 3.5: Confirm Document Signatories](#step-35-confirm-document-signatories)
   - [Step 3.6: Create Staff & Officer Login Accounts](#step-36-create-staff--officer-login-accounts)
   - [Step 3.7: Configure Official Holidays & Working Calendar](#step-37-configure-official-holidays--working-calendar)
   - [Step 3.8: Review Case Types & Numbering Schemes](#step-38-review-case-types--numbering-schemes)
4. ["Before Residents Can Use the System" Checklist](#4-before-residents-can-use-the-system-checklist)
5. ["What the System Will Not Do Until You Finish Step X"](#5-what-the-system-will-not-do-until-you-finish-step-x)
6. [Daily Operations Quick Reference](#6-daily-operations-quick-reference)

---

## 1. Starting the Barangay Server

If your computer or office server restarts, open **Windows PowerShell** and run these commands to start the application:

```powershell
# Navigate to the system directory
cd c:\githubFiles\Barangay-Reporting-System-1

# Start the web server
php artisan serve
```

Once the terminal displays `Server running on [http://127.0.0.1:8000]`, open Google Chrome or Microsoft Edge and navigate to:
```
http://localhost:8000
```

> [!NOTE]
> Keep the PowerShell window open in the background while the office is operating. Closing the window shuts down access to the system.

---

## 2. First-Time Setup Wizard

When anyone accesses the system for the very first time, the system will automatically protect itself and redirect straight to the **5-Step Setup Wizard** (`http://localhost:8000/setup`).

### Step 1: System Verification
- **What you see:** A health checklist showing green badges for *Database Connected*, *Storage Directory Writable*, and *Mail Transport Configured*.
- **Action:** Verify all items show **Ready**, then click the blue button: **`[Start Setup]`**.

### Step 2: Administrator Account
- **What you enter:**
  - **Administrator Full Name:** e.g., `Administrator Setup` (or the Punong Barangay's name).
  - **Official Email Address:** e.g., `admin.setup@calarian.gov.ph` (This will be your master sign-in credential).
  - **Password & Confirm Password:** Enter a secure password (minimum 8 characters).
- **Action:** Click **`[Save and Continue]`**.

### Step 3: Barangay Profile & Jurisdiction
- **What you enter:**
  - **Barangay Name:** e.g., `Barangay Calarian`
  - **City / Municipality:** e.g., `Zamboanga City`
  - **Province:** e.g., `Zamboanga del Sur`
  - **Region:** e.g., `Region IX (Zamboanga Peninsula)`
  - **Barangay Hall Physical Address:** e.g., `Calarian, Zamboanga City, 7000` *(appears on summons return notices and transmittals)*
  - **Official Contact Number:** e.g., `0917-123-4567`
  - **Official Email Address:** e.g., `info@calarian.gov.ph`
- **Action:** Click **`[Save and Continue]`**.

### Step 4: Active Fiscal Year
- **What you enter:**
  - **Initial Operational Fiscal Year:** Enter `2026` (the current operational calendar year).
- **Action:** Click **`[Save and Continue]`**.

### Step 5: Review & Finalize Setup
- **What you see:** A summary card of the administrator account, barangay details, and active fiscal year.
- **Optional Step 5b:** You may click **`[Add Staff Accounts (Optional)]`** if you wish to pre-register desk clerks, or proceed directly to completion.
- **Action:** Click the amber button **`[Complete Setup]`**.

> [!IMPORTANT]
> **Sign-In Required:** Completing the wizard locks the setup screens and redirects you to the Login Portal. Sign in using the administrator email and password you created in Step 2. The public resident website is now active.

---

## 3. Day-One Post-Setup Configuration

*Follow these steps immediately after your first login.*

### Step 3.1: Review and Confirm Baseline Settings
Look at the top right of your screen. You will see an amber banner:  
**`⚠️ 64 Pending Items: Unconfirmed system defaults require official review.`**

1. Click the banner (or open **Administration > Pending Confirmations** from the sidebar).
2. The page lists all pre-loaded settings, official positions, dispute categories, and document templates.
3. You can review items individually or click the section buttons:
   - **`[Confirm General Settings]`**
   - **`[Confirm Positions]`**
   - **`[Confirm Signatories]`**
   - **`[Confirm Case Types]`**
   - **`[Confirm Venues]`**
   - **`[Confirm Templates]`**
4. Once all sections are confirmed, the amber warning banner disappears from your header.

---

### Step 3.2: Verify the Operational Fiscal Year
1. Go to **Administration > Fiscal Years** in the sidebar.
2. Confirm that **FY 2026** is marked with a green badge: **`Active`**.
3. All case dockets, summons numbers, and referral letters will automatically be scoped under this active year.

---

### Step 3.3: Set Up Local Emergency Hotlines
> [!WARNING]
> By default, the pre-loaded emergency contacts have placeholder phone numbers (`N/A`). On the resident portal, local hotlines will remain hidden until you enter real telephone or mobile numbers!

1. Go to **Administration > Emergency Contacts**.
2. Click **`[Edit]`** next to each service:
   - **Police Station:** Enter your local PNP precinct number (e.g., `0919-777-7377`).
   - **Fire Station:** Enter your local BFP fire sub-station number.
   - **Health Office / Ambulance:** Enter your local barangay health station or city ambulance desk.
   - **Disaster Response Office (CDRRMO):** Enter your city emergency operations hotline.
   - **Barangay Hotline:** Enter the official 24/7 hotline of your Barangay Hall.
3. Save each contact. Once saved, these numbers will immediately appear in the **"Emergency / 911"** popup on the resident website.

---

### Step 3.4: Register Barangay Officials and Staff (Personnel)
*The system needs to know who holds which office so legal documents can be signed.*

1. Go to **Operations > Personnel**.
2. Notice the roster is currently empty. Click **`[Add Personnel]`**.
3. Add the **Punong Barangay**:
   - **Full Name:** `Hon. Eduardo V. Alvarez`
   - **Position:** Select `Punong Barangay`
   - **Term Start:** Enter the start of the current term (e.g., `2026-01-01`)
   - **Signatory Authority:** Check **`Yes (Authorized Signatory)`**
   - **Upload Official Signature:** *(Optional)* Upload a transparent PNG signature scan.
   - Click **`[Save Personnel]`**.
4. Add the **Barangay Secretary**:
   - **Full Name:** `Graziella Marife S. Saavedra`
   - **Position:** Select `Barangay Secretary`
   - **Term Start:** `2026-01-01`
   - **Signatory Authority:** Check **`Yes (Authorized Signatory)`**
   - Click **`[Save Personnel]`**.
5. Add at least one **Process Server / Tanod**:
   - **Full Name:** `Tanod Juan dela Cruz`
   - **Position:** Select `Barangay Tanod / Peace Officer`
   - Click **`[Save Personnel]`**.

---

### Step 3.5: Confirm Document Signatories
1. Go to **Operations > Signatories**.
2. Review the four core document categories:
   - **Summons (KP Form No. 9):**
     - Prepared by: `Barangay Secretary` (resolves to Graziella Marife S. Saavedra)
     - Presided by: `Punong Barangay` (resolves to Hon. Eduardo V. Alvarez)
   - **Hearing / Conciliation Notice (KP Form No. 8 & 13):**
     - Prepared by: `Barangay Secretary`
     - Approved by: `Punong Barangay`
   - **Amicable Settlement (KP Form No. 16):**
     - Attested by: `Punong Barangay`
   - **Certificate to File Action (KP Form No. 20):**
     - Attested by: `Lupon Secretary / Barangay Secretary`
     - Approved by: `Punong Barangay / Pangkat Chairman`
3. Click **`[Save Signatories Matrix]`**. Now all printed forms will automatically display the official names and signature lines.

---

### Step 3.6: Create Staff & Officer Login Accounts
1. Go to **Operations > User Accounts**.
2. Click **`[Add User Account]`**.
3. Create the **Secretary's Staff Account**:
   - **Full Name:** `Graziella Marife S. Saavedra`
   - **Email:** `secretary@calarian.gov.ph`
   - **Role:** Select **`Staff`**
   - **Linked Personnel Record:** Select `Graziella Marife S. Saavedra`
   - **Permission Preset:** Choose **`Full Barangay Staff`** (gives access to community reports, cases, summonses, and referrals).
   - Enter a temporary password and click **`[Save User]`**.
4. Create an Account for the **Desk Officer / Tanod**:
   - **Full Name:** `Tanod Juan dela Cruz`
   - **Email:** `tanod@calarian.gov.ph`
   - **Role:** Select **`Staff`**
   - **Permission Preset:** Choose **`Case Encoder / Lupon Clerk`** or select custom permissions.

---

### Step 3.7: Configure Official Holidays & Working Calendar
*Under the Katarungang Pambarangay Law, the 15-day mediation clock only counts working days (Monday to Friday, excluding official non-working holidays).*

1. Go to **Administration > Official Calendar Holidays** (`/admin/settings/calendar`).
2. Add any upcoming local holidays, charter days, or national non-working holidays:
   - **Date:** Select the date from the picker (e.g., `2026-12-25`).
   - **Holiday Label:** e.g., `Christmas Day`.
   - Click **`[Add Holiday]`**.
3. When the Lupon schedules hearings, the system will automatically skip weekends and these recorded holidays when computing the 15-day deadline.

---

### Step 3.8: Review Case Types & Numbering Schemes
1. Go to **Administration > Case Types**:
   - Confirm the 6 pre-loaded dispute types: *Boundary Dispute, Neighborhood Noise, Physical Injury/Altercation, Minor Debt, Tenancy Disagreement, and Other Lupon Matters*.
2. Go to **Administration > Numbering Schemes**:
   - Review your document prefix formats:
     - Case Number: `11-001-2026` or `CASE-2026-00001`
     - Summons: `SUM-2026-0001`
     - External Referral: `REF-2026-0001`
   - Click **`[Save Numbering Schemes]`**. Sequence counters start automatically at `0001` for the active year.

---

## 4. "Before Residents Can Use the System" Checklist

Before announcing the portal to your community, verify this 6-point readiness checklist:

- [ ] **Setup Wizard Finished:** System setup is marked complete and `/setup` is locked.
- [ ] **Emergency Numbers Replaced:** Placeholder `N/A` numbers in Administration > Emergency Contacts are replaced with real working phone numbers.
- [ ] **Secretary & Captain Enrolled:** Both the Punong Barangay and Barangay Secretary are registered in Operations > Personnel.
- [ ] **Signatories Connected:** Document signers in Operations > Signatories show the names of the Captain and Secretary, not blank underlines.
- [ ] **Staff Accounts Tested:** The Secretary has logged in successfully and verified access to the **Community Reports** queue and **Cases & Blotter**.
- [ ] **Resident Portal Public:** Visiting `http://localhost:8000` from an unauthenticated browser loads the Calarian Public Services home screen.

---

## 5. "What the System Will Not Do Until You Finish Step X"

| Step Skipped | What Fails or Will Not Work |
| :--- | :--- |
| **Skipped Step 3.1** *(Pending Settings Confirmation)* | An amber warning banner permanently clutters the header. Official reports carry "unconfirmed baseline" audit flags. |
| **Skipped Step 3.2** *(Active Fiscal Year)* | The system cannot generate sequential Case Numbers or Referral IDs; docketing throws numbering errors. |
| **Skipped Step 3.3** *(Emergency Hotlines)* | The resident portal's emergency modal only shows the national 911 button; all local precinct, fire, and health station numbers remain hidden. |
| **Skipped Step 3.4 & 3.5** *(Personnel & Signatories)* | Printed Summonses (KP Form 9), Hearing Notices (KP Form 8), and Certificates to File Action (CFA) print with blank underlines (`____________________`) instead of official names and titles. |
| **Skipped Step 3.6** *(Staff Accounts)* | Only the master administrator can log in. The Barangay Secretary and Tanods cannot access their assigned queues or record summons deliveries. |
| **Skipped Step 3.7** *(Holidays Calendar)* | The 15-day statutory mediation clock will treat public holidays as working days, potentially penalizing parties or prematurely escalating cases. |

---

## 6. Daily Operations Quick Reference

### Handling Community Reports
1. Resident submits a report via `http://localhost:8000/report`.
2. AI automatically analyzes the report in seconds (suggesting category and urgency).
3. Barangay Secretary opens **Intake & Cases > Community reports**.
4. Click on the report to open the **Review Card**.
5. The Secretary confirms or adjusts the category/urgency, and clicks **`[Route Report]`** to assign it to an internal maintenance office or external city agency.
6. Once work is completed, click **`[Propose Resolution]`** with optional completion photos.

### Handling Dispute Complaints (Lupon Tagapamayapa)
1. Resident submits a complaint or walks in to the Barangay Hall.
2. Open **Intake & Cases > Cases & Blotter** and click **`[New Case / Blotter]`**.
3. Select the Complainant and Respondent parties.
4. **Step 1:** Click **`[Print Complaint Form]`** (KP Form No. 1) for the complainant to sign under oath. Once signed, click **`[Mark Signed Complaint Received]`**.
5. **Step 2:** Click **`[Schedule Hearing & Issue Summons]`**. Enter the date (must be in the future), time (default 9:00 AM), venue, and presiding officer. This simultaneously generates Hearing #1 and Summons #1.
6. **Step 3:** Tanod serves the paper summons. When the Tanod returns the signed stub, click **`[Record Service]`**, choose the service method (*Personal Service* or *Substituted Service*), and log the notes.
7. **Step 4:** Conduct the mediation hearing. The 15-working-day mediation clock automatically tracks statutory deadlines. Click **`[Record Hearing Outcome]`** to log an amicable settlement (KP Form 16) or schedule the next conciliation session.
