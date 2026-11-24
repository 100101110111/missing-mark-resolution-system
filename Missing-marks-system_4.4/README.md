
## v4.4 update
- For **Both CATS & End Term**, Case Details now displays **Pending award** for CAT 1, CAT 2 and End Term until the administrator completes the combined award.
- All three award tabs remain open and editable for the combined workflow.
- Removed the wording **Closed assessments cannot be changed from this report**; the interface now states that open assessment fields can be entered or changed by the administrator.
# 🎓 Missing Marks Resolution System
### Kenya School of TVET
### 🆕 Version 3 — Restructured Submission Flow, Verification Workflow &amp; Status Tracking with Notifications

A full-featured, database-driven web application that lets students **report, track and
follow up missing marks** in a structured way, and lets the registrar's office (Admin)
**review, update and resolve** every case with a full audit trail.

Built with **PHP (PDO/MySQLi), MySQL, Bootstrap 5, Chart.js and vanilla JS** — runs natively
on **XAMPP**, no extra installs required.

---

## ✨ Features

**Student side**
- Register / Login with admission number + email
- 🆕 **Restructured reporting page** (`report_new.php`): a dedicated, full-page guided form (no longer a popup) with a clear on-click **Submit Report** button
- 🆕 On submit, a **ticket-style confirmation page** displays "*Your Missing Mark report has been submitted successfully*" along with a copyable reference/ticket number and a preview of the verification workflow ahead
- Auto-generated, unique reference number per report (e.g. `MM-2026-04213`)
- Personal dashboard with live stats (Total / Pending / Resolved / Rejected)
- 🆕 **Status Tracking widget** on the dashboard and report detail page: a visual 3-stage tracker — *Submitted → Under Verification → Approved/Rejected* — showing exactly where each active case is right now
- 🆕 **Notifications panel** (dashboard + navbar bell with unread badge): live sample email/SMS/system notifications fired the moment a staff member moves a case to verification, approves, or rejects it
- Full report history with search & status filters
- Report detail page with a **visual status timeline** and the same tracker
- Editable profile + change password

**Staff side (Academic Registrar, HOD &amp; Trainer)** — 🆕 v2
- **Dedicated Staff Portal** (`admin/login.php`), reached from its own "Staff / Admin" tab on the homepage — completely separate from the student login/register flow
- Central dashboard with animated stat cards
- **Charts** (doughnut + bar) showing reports by status and by semester
- "Trainers with most cases" leaderboard
- Full reports table with search + status/semester filters, now **paginated** for large datasets
- **CAT marks**: when reporting a CAT, the form captures numeric CAT 1 (0–30) and CAT 2 (0–70) marks.
- **Award Marks**: assign a CAT grade and/or an End Term grade to any case, using the ordered scale `A, A-, B+, B, B-, C+, C, C-, D+, D, D-, E`
- **Approve / Reject** each case with one click (in addition to the full manual status + remarks form) — 🆕 every status change automatically fires a sample email/SMS/system notification to the student
- **Bulk actions**: select multiple reports in the table and Approve or Reject them all at once — 🆕 notifications are sent for every affected student
- Student directory (searchable, paginated)
- Trainer directory management (add/remove)
- **Staff Accounts page**: any staff member can onboard more Registrar / HOD / Trainer accounts — the platform scales to as many staff as your institution needs, without editing the database by hand

**Under the hood**
- Secure password hashing (`password_hash` / `password_verify`)
- PDO prepared statements everywhere (SQL-injection safe)
- CSRF tokens on every form
- Session-based auth with role separation (student / staff), plus a `staff_role` (Registrar / HOD / Trainer) for staff accounts
- Fully responsive Bootstrap 5 UI — the Staff Portal uses the exact same theme, components and colors as the student portal
- **Built to scale**: indexed database columns (`status`, `semester`, `trainer_name`, `created_at`, `role`), and paginated listings (reports, students, my-reports) so performance stays fast as the number of students, staff and cases grows

---

## 📁 Project Structure

```
missing-marks-system/
├── admin/                     # Staff (Registrar / HOD / Trainer) portal
│   ├── actions/update_report.php, bulk_update.php   # 🆕 fire notifications on status change
│   ├── includes/admin_header.php, admin_footer.php
│   ├── login.php               # dedicated Staff Portal login
│   ├── dashboard.php
│   ├── reports.php              # pagination + bulk approve/reject
│   ├── report_view.php          # grade awarding + approve/reject buttons
│   ├── students.php             # search + pagination
│   ├── trainers.php
│   └── staff.php                # onboard more Registrar/HOD/Trainer accounts
├── actions/
│   ├── submit_report.php          # 🆕 now redirects to the ticket confirmation page
│   └── mark_notifications_read.php # 🆕 mark a student's notifications as read
├── assets/
│   ├── css/style.css
│   └── js/script.js
├── config/
│   └── db.php                 # Database connection + app constants
├── database/
│   ├── missing_marks_db.sql       # Full schema + seed trainers (fresh installs)
│   ├── migration_v1_to_v2.sql     # v1 → v2 upgrade (staff roles, grades)
│   └── migration_v2_to_v3.sql     # 🆕 v2 → v3 upgrade (notifications table)
├── includes/
│   ├── functions.php           # 🆕 notification + status-tracker helpers
│   ├── header.php / footer.php
│   ├── navbar_public.php / navbar_student.php  # 🆕 notification bell + badge
│   └── modal_view_report.php
├── index.php                  # Landing page (Staff / Admin tab)
├── register.php / login.php / logout.php
├── report_new.php             # 🆕 restructured, dedicated report submission page
├── report_success.php         # 🆕 ticket-style success confirmation with reference no.
├── dashboard.php               # 🆕 status tracker + notifications panel
├── my_reports.php / report_details.php / profile.php
├── setup_admin.php            # One-time admin account creator (Registrar by default)
└── README.md
```

---

## 🚀 Setup on XAMPP (step-by-step)

1. **Copy the project folder**
   Extract this ZIP and place the `missing-marks-system` folder inside your XAMPP
   `htdocs` directory, e.g.:
   - Windows: `C:\xampp\htdocs\missing-marks-system`
   - macOS: `/Applications/XAMPP/htdocs/missing-marks-system`
   - Linux: `/opt/lampp/htdocs/missing-marks-system`

2. **Start XAMPP**
   Open the XAMPP Control Panel and start the **Apache** and **MySQL** modules.

3. **Create the database**
   - Open `http://localhost/phpmyadmin`
   - Click **Import**
   - Choose the file `database/missing_marks_db.sql` from this project
   - Click **Go**. This creates the `missing_marks_db` database with all tables and a starter list of trainers.

4. **Create the admin (Registrar) account**
   In your browser, visit:
   ```
   http://localhost/missing-marks-system/setup_admin.php
   ```
   This safely creates the default Administrator account, assigned the **Registrar** staff
   role. Default credentials:
   - **Email:** `admin@kstvet.ac.ke`
   - **Admission No. (also works as username):** `ADMIN001`
   - **Password:** `admin123`

   ⚠️ Change this password after your first login (Profile page has no admin password
   change form by design — update it directly in phpMyAdmin, or extend the system), and
   delete `setup_admin.php` from the server once you're done.

   > **Upgrading an existing v1 install?** Instead of re-importing the schema, run
   > `database/migration_v1_to_v2.sql` in phpMyAdmin against your existing database —
   > it adds the new staff/grade columns without touching your existing data.
   >
   > **Upgrading an existing v2 install?** Run `database/migration_v2_to_v3.sql` — it
   > adds the `notifications` table used for the status-tracking notifications feature.

5. **Open the system**
   ```
   http://localhost/missing-marks-system/
   ```
   - Students: click **Register**, or **Login**, from the homepage.
   - Staff (Registrar / HOD / Trainer): click **Staff / Admin** in the top navigation
     (or go straight to `admin/login.php`) and sign in with the credentials above.
   - Once logged in, the Registrar account can create more staff accounts from
     **Staff Accounts** in the sidebar — no need to touch the database.

That's it — the system is fully working! 🎉

---

## 🔧 Configuration

All database settings live in `config/db.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'missing_marks_db');
define('DB_USER', 'root');
define('DB_PASS', '');
```

These are the default XAMPP MySQL credentials. Change them if your setup differs.

`BASE_URL` is calculated **automatically** based on where the project folder sits inside
`htdocs`, so the app works whether it's installed at
`http://localhost/missing-marks-system/` or any other folder name — no manual editing needed.

---

## 🔔 How the Verification Workflow &amp; Notifications Work

Every report moves through a simple, visible workflow:

**Submitted → Under Verification → Approved ✅ / Rejected ❌**

- The moment a student submits a report, it's **Submitted** (status `Pending`) and they land on a
  ticket confirmation page with their reference number.
- When staff open the case and set it to **"In Review"**, the tracker moves to **Under
  Verification** — and the student gets notified.
- When staff click **Approve &amp; Resolve** or **Reject** (or change status manually), the
  tracker completes and the student is notified again.

This tracker is shown live on the student's **Dashboard** (for active cases), on the **Report
Detail** page, and on the **ticket confirmation** page right after submitting.

**About the email/SMS notifications:** every status change writes a `system`, `email`, and `sms`
row to the `notifications` table — these are what power the **Notifications panel** and bell icon
on the student dashboard, and they're a reliable, always-visible **sample** of what the student
would receive, regardless of server configuration. In addition, the system makes a best-effort
attempt to send a **real email** via PHP's `mail()` function (see `attemptEmailSend()` in
`includes/functions.php`) — on stock XAMPP this typically requires configuring `sendmail` or an
SMTP relay in `php.ini` to actually deliver; it is **not required for the demo to work**, since the
in-app notification is always recorded either way. SMS is logged the same way, ready for you to
plug in a real gateway (e.g. Africa's Talking, Twilio) inside `notifyReportStatusChange()`.

## 🗄️ Database Overview

| Table                     | Purpose                                              |
|---------------------------|-------------------------------------------------------|
| `users`                   | Students and staff accounts (role-based); staff rows also carry a `staff_role` of Registrar, HOD or Trainer |
| `trainers`                | Manageable directory of trainer names                 |
| `missing_marks_reports`   | Each missing-marks case, with a unique reference no., plus `cat_grade` and `exam_grade` once awarded |
| `report_status_history`   | Full audit trail / timeline for every status change and grade award |
| `notifications`           | 🆕 Sample system/email/SMS notifications sent to students on each status change |

---

## 🎨 Design

The interface uses a custom **Kenyan forest-green & gold** theme (`assets/css/style.css`),
Google Fonts (Poppins/Sora), Font Awesome icons and Chart.js — all loaded via CDN, so an
internet connection is needed the first time pages load (or swap in local copies of the
libraries if you need a fully offline demo).

---

## 🧑‍💻 Default Test Flow (for a demo)

1. Visit the homepage → click **Register** → create a student account.
2. Log in → click **Report Missing Marks** → this opens the dedicated **report page**
   (`report_new.php`) → fill the form → click **Submit Report**.
3. You'll land on a **ticket confirmation page** — "Your Missing Mark report has been
   submitted successfully" — with your reference/ticket number and a preview of the
   verification workflow. Copy the reference number if you like.
4. Back on the **dashboard**, your new case appears under **Recent Reports** and in the
   new **Status Tracking** section (Submitted stage lit up).
5. Open a new browser (or incognito) tab → click **Staff / Admin** on the homepage →
   sign in with the Registrar credentials from `setup_admin.php`.
6. On the Staff dashboard, the case you just submitted already appears in **Recent
   Reports** and in the status/semester charts.
7. Go to **All Reports** → **Review** the case → set status to **"In Review"** and save.
8. Switch back to the student tab → refresh the dashboard → the **Status Tracking**
   widget now shows "Under Verification" as the current stage, and the
   **Notifications** panel (and bell icon) shows a new sample system/email/SMS alert.
9. Back on the staff tab → award a **CAT Grade** / **End Term Grade**, then click
   **Approve &amp; Resolve** (or try **bulk actions** on the reports list).
10. Refresh the student dashboard again → the tracker completes to **Approved**, the
    awarded grades are visible, and another notification (approval) appears in the
    panel and bell dropdown.

Enjoy demoing your **Missing Marks Resolution System**! 🚀


### Numeric CAT 1 / CAT 2 update
For an existing installation, run `database/migration_v3_to_cat_marks.sql` in phpMyAdmin. CAT 1 accepts 0–30 and CAT 2 accepts 0–70. The fields appear only when `CAT` is selected in the report form.


## Version 4 — Assessment Awarding Workflow

This update restructures missing-mark reporting and administrator awarding:

- **CAT 1** reports: administrator can award only a numeric mark from **0–30**.
- **CAT 2** reports: administrator can award only a numeric mark from **0–70**.
- **Both CATS & End Term** reports: CAT 1 (0–30), CAT 2 (0–70), and End Term (A through E (including A-, B+, B-, C+, C-, D+ and D-)) are open.
- Closed assessment fields are disabled and shown as **AWARDED**.
- End Term grades are restricted to **A, B, C, D, E**.
- Server-side validation enforces the same limits, so browser manipulation cannot bypass the rules.
- The student report form no longer asks students to enter CAT marks; they only select the missing assessment type.
- Existing legacy assessment values are retained in the database for compatibility.

### Database update

For an existing v3 installation, run:

`database/migration_v4_assessment_workflow.sql`

before using the upgraded application.


## v4.2 Assessment Workflow Update
- Missing assessment choices: CAT 1, CAT 2, END TERM, Both CATS & End Term.
- CAT 1: 0–30.
- CAT 2: 0–70.
- END TERM: A, A-, B+, B, B-, C+, C, C-, D+, D, D-, E.
- Both CATS & End Term: all three fields remain open.
- For single-assessment reports, non-selected assessments are shown as AWARDED and remain locked.
