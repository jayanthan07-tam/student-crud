# Student CRUD & Academic Performance Management System

A full-stack Core PHP, MySQL (PDO), HTML5, CSS3, JavaScript, and Chart.js application following the **Google Stitch UI** light-surface academic dashboard design.

## 1. Project Overview
The application manages student demographic records, 7-subject semester internal mark ledgers, runtime age derivation, dynamic attendance-aware percentage evaluation, competition-style cohort ranking, Chart.js analytics, CSV export, and printable reports.

## 2. Folder Structure
```text
student-crud/
├── assets/
│   ├── css/style.css           # Stitch UI layout, responsive breakpoints, print styles
│   ├── images/logo.svg         # Brand emblem
│   └── js/script.js            # Live age & percentage preview, modals, Chart.js setup
├── config/
│   └── database.php            # Centralized PDO connection (utf8mb4, exceptions, assoc fetch)
├── database/
│   ├── student_management.sql  # Database schema, foreign keys, 7 subjects & percentage view
│   └── student_data.sql        # 28 student records, 7 subjects, 196 internal mark entries
├── includes/
│   ├── functions.php           # Domain calculations, prepared PDO queries, CSRF & CSV helpers
│   ├── header.php              # HTML head, sidebar inclusion, top navigation bar
│   ├── sidebar.php             # Navigation menu with live student count badge
│   └── footer.php              # Footer bar and global JavaScript entrypoint
├── add_student.php             # Create Student + 7 subject marks inside ACID transaction
├── dashboard.php               # Cohort KPIs, recent students snapshot, Chart.js bar overview
├── delete_student.php          # POST + CSRF atomic deletion (and GET confirmation view)
├── edit_student.php            # Update demographics & transactional marks upsert
├── index.php                   # Redirects cleanly to dashboard.php
├── performance.php             # Competition ranking ledger, 3 Chart.js analytics charts, CSV export
├── README.md                   # Project documentation
├── students.php                # Directory listing with search, filters, sorting, pagination & modal delete
├── test_connection.php         # Live PDO health check and MySQL table inspection
└── view_student.php            # Complete student dossier (personal details + subject marks ledger)
```

## 3. Technology Stack
- **Backend**: Core PHP 8.x with PHP Data Objects (**PDO**), native sessions, and strict prepared statements.
- **Database**: MySQL 8.0 / MariaDB (`utf8mb4` encoding, `InnoDB` foreign-key constraints with cascading deletes).
- **Frontend**: Semantic HTML5, CSS3 (Google Stitch UI design system, Inter typography), Vanilla JavaScript.
- **Charts**: Chart.js v4.4.7 (via jsDelivr CDN).
- **Local Environment**: XAMPP (Apache + MySQL).

## 4. Database Schema & Rules (`student_management`)
- **`students`**: Stores `id`, `student_name`, `register_no` (`UNIQUE`), `mobile_number` (placeholder contact numbers `9876500001`–`9876500028`), `dob`, `created_at`, `updated_at`.
  - **Runtime Age**: Age is never stored in MySQL; it is derived at runtime from `dob` using PHP `DateTime`.
- **`subjects`**: Defines the 7 curriculum courses (`id`, `subject_name`, `maximum_marks = 100`).
- **`student_marks`**: Maps `student_id` -> `students.id` (`ON DELETE CASCADE`) and `subject_id` -> `subjects.id` (`ON DELETE CASCADE`), storing `marks_obtained` (`DECIMAL(5,2)`, nullable when `Absent`) and `attendance_status` (`ENUM('Present', 'Absent')`).
- **Dynamic Percentage Calculation**:
  - Formula: `ROUND((SUM(Present marks_obtained) / SUM(Present maximum_marks)) * 100, 2)`
  - Subjects marked `Absent` are excluded from both numerator and denominator.
  - If all subjects are marked `Absent`, overall status displays `Absent` (excluded from cohort averages).
  - If any `Present` subject has `NULL` marks or fewer than all curriculum subjects exist, overall percentage resolves to `Not Available`.

## 5. Setup & Installation in XAMPP
1. Copy or clone the repository folder so `student-crud/` is accessible inside Apache's document root (e.g., `C:\xampp\htdocs\student-crud\`), or configure an Apache `Alias` / VirtualHost pointing to `student-crud/`.
2. Start **Apache** and **MySQL** from the **XAMPP Control Panel**.
3. Open **phpMyAdmin** (`http://localhost/phpmyadmin`) and import in order:
   1. `database/student_management.sql` (creates `student_management`, tables, 7 subjects, and `v_student_percentages`).
   2. `database/student_data.sql` (populates the 28 cohort students and 196 subject mark entries).
4. Review `config/database.php` if your MySQL credentials differ from XAMPP defaults (`localhost:3306`, DB `student_management`, user `root`, empty password).
5. Open `http://localhost/student-crud/test_connection.php` to verify PDO connectivity, table existence, and row counts, then proceed to `http://localhost/student-crud/dashboard.php`.

## 6. Key Implemented Features
- **Dashboard (`dashboard.php`)**: Live KPIs (Total Students, Cohort Average %, Highest %, Lowest %), Recent Students preview, and Chart.js Score Distribution chart.
- **Student Directory (`students.php`)**: Debounced search (name/register number), percentage bracket filter, runtime age group filter, column sorting, pagination (`10`/`25`/`50`), and CSRF-protected delete modal.
- **Create (`add_student.php`) & Edit (`edit_student.php`)**: Client + server validation (Register Number uniqueness, 10-digit placeholder mobile check, DOB & age `15–80` range check, attendance toggle disabling absent inputs), live JS age & percentage preview widget, and ACID PDO transactions (`Save` or `Save & Add Another` via PRG redirect).
- **Read (`view_student.php`)**: Personal identity dossier + 7-subject academic ledger displaying `Absent`, `Not Available`, or calculated percentage, plus direct Edit/Delete controls.
- **Delete (`delete_student.php`)**: Strict `POST` + CSRF validation + transactional deletion removing child marks and parent student atomically.
- **Performance Analytics (`performance.php`)**: Standard competition ranking (`1, 2, 2, 4...`), 3 interactive Chart.js visuals (Band Distribution, Top 5 Performers, Subject-wise Averages), `?export=csv` export with UTF-8 BOM and formula-injection neutralization, and print stylesheet (`window.print()`).

## 7. Security Controls
- PDO prepared statements with emulated prepares disabled (`PDO::ATTR_EMULATE_PREPARES => false`) on all SQL queries.
- `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` output escaping across UI renders and flash messages.
- Per-session CSRF tokens (`hash_equals`) on add, edit, and delete POST forms.
- Safe exception logging (`error_log`) without exposing internal PDO credentials or stack details to end users.
- CSV cell neutralization (`sanitizeCsvCell`) preventing spreadsheet formula execution (`=`, `+`, `-`, `@`).

## 8. Troubleshooting
- **Database Offline Banner**: Confirm MySQL is running in XAMPP on port `3306` and `student_management` exists in phpMyAdmin.
- **Charts Not Rendering**: Verify internet access for the Chart.js CDN (`https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js`).
- **Session / CSRF Mismatch**: Ensure cookies are enabled for `localhost` and avoid submitting stale tabs after session expiration.
