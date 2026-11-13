<?php
// add_student.php
// Academic Operations System - Student Enrollment & Marks Registration
// Section 07: Add Student Page (Google Stitch UI Design & Transactional PDO Insertion)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Add New Student';
$currentPage = 'add_student';

// 1. Include Centralized Database Connection & Helper Functions
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Generate or retrieve CSRF token
$csrfToken = generateCsrfToken();

// Load curriculum subjects dynamically from MySQL
$isDbConnected = (isset($pdo) && $pdo instanceof PDO);
$subjects = $isDbConnected ? getAllSubjects($pdo) : [];

// Initialize form sticky values
$studentName    = '';
$registerNo     = '';
$mobileNumber   = '';
$dob            = '';
$marksInput     = [];
$attendanceInput = [];
$errors         = [];
$successMessage = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);

if (!$isDbConnected) {
    $errors[] = $dbConnectionError ?? 'Database connection is currently offline. Please ensure MySQL is running in XAMPP before enrolling a student.';
}

// Pre-fill default attendance status as 'Present' for all subjects
foreach ($subjects as $sub) {
    $subId = (int)$sub['id'];
    $attendanceInput[$subId] = 'Present';
    $marksInput[$subId] = '';
}

// 2. Handle POST Request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 2.1 Verify CSRF Security Token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security verification failed (Invalid CSRF token). Please reload the page and try again.';
    }

    // 2.2 Extract & Sanitize Primary Student Details
    $studentName  = trim($_POST['student_name'] ?? '');
    $registerNo   = trim($_POST['register_no'] ?? '');
    $mobileNumber = trim($_POST['mobile_number'] ?? '');
    $dob          = trim($_POST['dob'] ?? '');
    $submittedAction = $_POST['action'] ?? 'save';

    // Validate Student Name
    if ($studentName === '') {
        $errors[] = 'Student Name is required.';
    } elseif (mb_strlen($studentName) < 2 || mb_strlen($studentName) > 100) {
        $errors[] = 'Student Name must be between 2 and 100 characters.';
    }

    // Validate Register Number
    if ($registerNo === '') {
        $errors[] = 'Register Number is required.';
    } elseif (!preg_match('/^[A-Za-z0-9_-]{3,30}$/', $registerNo)) {
        $errors[] = 'Register Number must be 3 to 30 alphanumeric characters.';
    } elseif ($pdo instanceof PDO && isRegisterNumberTaken($pdo, $registerNo)) {
        $errors[] = "Register Number '{$registerNo}' already exists in the database. Please provide a unique register number.";
    }

    // Validate Mobile Number
    if ($mobileNumber === '') {
        $errors[] = 'Mobile Number is required.';
    } elseif (!preg_match('/^[0-9]{10}$/', $mobileNumber)) {
        $errors[] = 'Mobile Number must be a valid 10-digit number (e.g. 9876500001).';
    }

    // Validate Date of Birth
    if ($dob === '') {
        $errors[] = 'Date of Birth is required.';
    } else {
        $dobDate = DateTime::createFromFormat('Y-m-d', $dob);
        $today = new DateTime('today');
        if (!$dobDate || $dobDate->format('Y-m-d') !== $dob) {
            $errors[] = 'Date of Birth is invalid.';
        } elseif ($dobDate > $today) {
            $errors[] = 'Date of Birth cannot be in the future.';
        } else {
            $age = $dobDate->diff($today)->y;
            if ($age < 15 || $age > 80) {
                $errors[] = 'Student age must be between 15 and 80 years for academic enrollment.';
            }
        }
    }

    // 2.3 Extract & Validate Subject Marks & Attendance
    $processedMarks = [];
    $rawAttendance = $_POST['attendance'] ?? [];
    $rawMarks = $_POST['marks'] ?? [];

    foreach ($subjects as $sub) {
        $subId = (int)$sub['id'];
        $subName = $sub['subject_name'];
        $maxMarks = (float)$sub['maximum_marks'];

        $status = $rawAttendance[$subId] ?? 'Present';
        if (!in_array($status, ['Present', 'Absent'], true)) {
            $status = 'Present';
        }
        $attendanceInput[$subId] = $status;

        if ($status === 'Absent') {
            // Absent subjects store NULL in MySQL
            $marksInput[$subId] = '';
            $processedMarks[] = [
                'subject_id'        => $subId,
                'attendance_status' => 'Absent',
                'marks_obtained'    => null,
            ];
        } else {
            // Present subject: must have valid numeric marks
            $rawMarkVal = trim((string)($rawMarks[$subId] ?? ''));
            $marksInput[$subId] = $rawMarkVal;

            if ($rawMarkVal === '') {
                $errors[] = "Please enter marks obtained for {$subName} or mark student as Absent.";
            } elseif (!is_numeric($rawMarkVal)) {
                $errors[] = "Marks obtained for {$subName} must be a valid number.";
            } else {
                $valFloat = (float)$rawMarkVal;
                if ($valFloat < 0 || $valFloat > $maxMarks) {
                    $errors[] = "Marks for {$subName} must be between 0 and {$maxMarks}.";
                } else {
                    $processedMarks[] = [
                        'subject_id'        => $subId,
                        'attendance_status' => 'Present',
                        'marks_obtained'    => round($valFloat, 2),
                    ];
                }
            }
        }
    }

    // 2.4 Execute Transactional Database Operations
    if (empty($errors)) {
        if (!($pdo instanceof PDO)) {
            $errors[] = "Database connection is offline. Please verify XAMPP MySQL.";
        } else {
            try {
                // Begin atomic transaction
                $pdo->beginTransaction();

                // 1. Insert into students table (Age is NOT stored in DB)
                $insertStudentSql = "
                    INSERT INTO students (student_name, register_no, mobile_number, dob)
                    VALUES (:student_name, :register_no, :mobile_number, :dob)
                ";
                $stmt = $pdo->prepare($insertStudentSql);
                $stmt->execute([
                    ':student_name'  => $studentName,
                    ':register_no'   => $registerNo,
                    ':mobile_number' => $mobileNumber,
                    ':dob'           => $dob,
                ]);

                $newStudentId = (int)$pdo->lastInsertId();

                // 2. Insert into student_marks table for all 7 subjects
                $insertMarksSql = "
                    INSERT INTO student_marks (student_id, subject_id, marks_obtained, attendance_status)
                    VALUES (:student_id, :subject_id, :marks_obtained, :attendance_status)
                ";
                $marksStmt = $pdo->prepare($insertMarksSql);

                foreach ($processedMarks as $pm) {
                    $marksStmt->bindValue(':student_id', $newStudentId, PDO::PARAM_INT);
                    $marksStmt->bindValue(':subject_id', $pm['subject_id'], PDO::PARAM_INT);
                    if ($pm['marks_obtained'] === null) {
                        $marksStmt->bindValue(':marks_obtained', null, PDO::PARAM_NULL);
                    } else {
                        $marksStmt->bindValue(':marks_obtained', $pm['marks_obtained'], PDO::PARAM_STR);
                    }
                    $marksStmt->bindValue(':attendance_status', $pm['attendance_status'], PDO::PARAM_STR);
                    $marksStmt->execute();
                }

                // Commit transaction only if every single query succeeded
                $pdo->commit();

                // 2.5 Route based on submitted action
                if ($submittedAction === 'save_and_add_another') {
                    $_SESSION['flash_success'] = "Student '{$studentName}' (Reg No: {$registerNo}) was successfully enrolled with examination marks! Form reset for next entry.";
                    header("Location: add_student.php");
                    exit();
                } else {
                    // Save and redirect to Student Management page
                    $_SESSION['flash_success'] = "Student '{$studentName}' (Reg No: {$registerNo}) was successfully registered and examination marks logged.";
                    header("Location: students.php");
                    exit();
                }

            } catch (PDOException $e) {
                // Roll back everything if any query failed
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log("Enrollment transaction error: " . $e->getMessage());
                $errors[] = "A database error occurred while saving the student record. Please verify inputs and try again.";
            }
        }
    }
}

// Compute initial age for display if DOB is set
$initialAgeDisplay = !empty($dob) ? calculateAge($dob) . ' yrs' : '';

// Include Reusable Header
include __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumbs Navigation -->
<nav class="breadcrumb-nav" aria-label="Breadcrumb" style="margin-bottom: 0.75rem;">
  <ol style="display: flex; align-items: center; gap: 0.5rem; list-style: none; padding: 0; font-size: 0.8rem; color: var(--color-text-light);">
    <li>
      <a href="dashboard.php" style="color: var(--color-text-muted); text-decoration: none; font-weight: 500;">Dashboard</a>
    </li>
    <li style="color: var(--color-border);">&rsaquo;</li>
    <li>
      <a href="students.php" style="color: var(--color-text-muted); text-decoration: none; font-weight: 500;">Students</a>
    </li>
    <li style="color: var(--color-border);">&rsaquo;</li>
    <li style="color: var(--color-primary); font-weight: 600;" aria-current="page">Add Student</li>
  </ol>
</nav>

<!-- Page Header with Primary Action -->
<div class="page-header" style="margin-bottom: 1.5rem;">
  <div>
    <h1 class="page-title">Add New Student</h1>
    <p class="page-subtitle">Provision official institutional registry identity, demographic credentials, and subject examination marks.</p>
  </div>
  <div>
    <a href="students.php" class="btn btn-secondary">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="19" y1="12" x2="5" y2="12"></line>
        <polyline points="12 19 5 12 12 5"></polyline>
      </svg>
      <span>Back to Directory</span>
    </a>
  </div>
</div>

<!-- Success Notification Alert -->
<?php if (!empty($successMessage)): ?>
  <div class="form-alert alert-success">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="flex-shrink: 0; margin-top: 1px;">
      <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
      <polyline points="22 4 12 14.01 9 11.01"></polyline>
    </svg>
    <div>
      <strong style="font-weight: 700;">Enrollment Successful!</strong>
      <div style="font-size: 0.85rem; margin-top: 0.2rem;"><?= htmlspecialchars($successMessage) ?></div>
    </div>
  </div>
<?php endif; ?>

<!-- Validation Error Alerts -->
<?php if (!empty($errors)): ?>
  <div class="form-alert alert-danger">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0; margin-top: 2px;">
      <circle cx="12" cy="12" r="10"></circle>
      <line x1="12" y1="8" x2="12" y2="12"></line>
      <line x1="12" y1="16" x2="12.01" y2="16"></line>
    </svg>
    <div>
      <strong style="font-weight: 700;">Please correct the following errors:</strong>
      <ul style="margin: 0.4rem 0 0 1.25rem;">
        <?php foreach ($errors as $err): ?>
          <li><?= htmlspecialchars($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
<?php endif; ?>

<!-- Student Registration Master Card -->
<div class="card" style="max-width: 1080px; margin: 0 auto 2.5rem;">
  <div class="card-header" style="background: #fafbfd; border-bottom: 1px solid var(--color-border);">
    <div>
      <h2 class="card-title">Student Dossier & Examination Records</h2>
      <p class="card-subtitle">Complete all mandatory demographic credentials and record 7 curriculum subject marks.</p>
    </div>
    <span class="badge-status badge-primary">
      <span class="badge-dot"></span>
      Transaction Safe (ACID)
    </span>
  </div>

  <div class="card-body" style="padding: 1.75rem 2rem;">
    <form action="add_student.php" method="POST" id="addStudentForm" novalidate>
      <!-- CSRF Protection Hidden Field -->
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

      <!-- ===================================================================
           SECTION 1: STUDENT BIOGRAPHICAL & REGISTRY DETAILS
           =================================================================== -->
      <div style="margin-bottom: 2rem;">
        <h3 style="font-size: 0.95rem; font-weight: 700; color: var(--color-text-main); margin-bottom: 0.35rem; display: flex; align-items: center; gap: 0.5rem;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--color-primary);">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
          1. Student Identity & Contact Information
        </h3>
        <p style="font-size: 0.8rem; color: var(--color-text-muted); margin-bottom: 1.25rem;">
          Ensure Register Number and Contact Mobile are unique and accurate.
        </p>

        <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.25rem;">
          <!-- 1. Student Name -->
          <div class="form-group">
            <label for="student_name" class="form-label">
              Student Name <span class="required">*</span>
            </label>
            <input 
              type="text" 
              id="student_name" 
              name="student_name" 
              class="form-control" 
              placeholder="e.g. SARAVANAN K"
              value="<?= htmlspecialchars($studentName) ?>" 
              required
              autocomplete="off"
            >
            <span class="form-help">Legal full name as registered in academic ledger</span>
          </div>

          <!-- 2. Register Number -->
          <div class="form-group">
            <label for="register_no" class="form-label">
              Register Number <span class="required">*</span>
            </label>
            <input 
              type="text" 
              id="register_no" 
              name="register_no" 
              class="form-control font-mono" 
              placeholder="e.g. 922525244070"
              value="<?= htmlspecialchars($registerNo) ?>" 
              required
              autocomplete="off"
            >
            <span class="form-help">Unique institutional register ID (Cannot be duplicated)</span>
          </div>

          <!-- 3. Mobile Number -->
          <div class="form-group">
            <label for="mobile_number" class="form-label">
              Mobile Number <span class="required">*</span>
            </label>
            <input 
              type="text" 
              id="mobile_number" 
              name="mobile_number" 
              class="form-control font-mono" 
              placeholder="e.g. 9876500030"
              value="<?= htmlspecialchars($mobileNumber) ?>" 
              required
              autocomplete="off"
              maxlength="15"
            >
            <span class="form-help">10-digit primary student contact number</span>
          </div>

          <!-- 4. Date of Birth -->
          <div class="form-group">
            <label for="dob" class="form-label">
              Date of Birth <span class="required">*</span>
            </label>
            <input 
              type="date" 
              id="dob" 
              name="dob" 
              class="form-control" 
              value="<?= htmlspecialchars($dob) ?>" 
              max="<?= date('Y-m-d') ?>"
              required
            >
            <span class="form-help">Official birth date from matriculation certificate</span>
          </div>

          <!-- 5. Automatically Calculated Age (Read-Only, Not stored in MySQL) -->
          <div class="form-group">
            <label for="ageDisplay" class="form-label">
              Age (Calculated)
            </label>
            <input 
              type="text" 
              id="ageDisplay" 
              class="form-control font-mono" 
              placeholder="Auto-calculated from DOB" 
              value="<?= htmlspecialchars($initialAgeDisplay) ?>"
              readonly 
              tabindex="-1"
              style="background-color: #f8fafc; color: #475569; font-weight: 600; cursor: not-allowed;"
            >
            <span class="form-help">Computed at runtime via DateTime (Not stored in database)</span>
          </div>
        </div>
      </div>

      <!-- ===================================================================
           SECTION 2: DYNAMIC SUBJECT MARKS & ATTENDANCE
           =================================================================== -->
      <div style="border-top: 1px solid var(--color-border); padding-top: 2rem; margin-bottom: 2rem;">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.35rem;">
          <h3 style="font-size: 0.95rem; font-weight: 700; color: var(--color-text-main); display: flex; align-items: center; gap: 0.5rem;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--color-primary);">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
              <polyline points="14 2 14 8 20 8"></polyline>
              <line x1="16" y1="13" x2="8" y2="13"></line>
              <line x1="16" y1="17" x2="8" y2="17"></line>
              <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
            2. Curriculum Subject Marks & Attendance Status
          </h3>
          <span style="font-size: 0.75rem; color: var(--color-text-light);">
            Loaded dynamically from <code>subjects</code> table (<?= count($subjects) ?> Subjects)
          </span>
        </div>
        <p style="font-size: 0.8rem; color: var(--color-text-muted); margin-bottom: 1.25rem;">
          Mark status as <strong>Absent</strong> if the student did not attend the examination. Absent subjects store <code>NULL</code> and are not counted as zero.
        </p>

        <!-- Subjects Table -->
        <div class="table-responsive" style="border: 1px solid var(--color-border); border-radius: var(--radius-md); overflow: hidden;">
          <table class="marks-entry-table">
            <thead>
              <tr>
                <th style="width: 50px;">#</th>
                <th>Subject Name</th>
                <th style="width: 140px; text-align: center;">Maximum Marks</th>
                <th style="width: 170px;">Attendance Status</th>
                <th style="width: 170px; text-align: right;">Marks Obtained</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($subjects as $idx => $sub): 
                $subId = (int)$sub['id'];
                $currentAttendance = $attendanceInput[$subId] ?? 'Present';
                $currentMark = $marksInput[$subId] ?? '';
                $isAbsent = ($currentAttendance === 'Absent');
              ?>
                <tr class="<?= $isAbsent ? 'row-absent' : '' ?>">
                  <!-- Subject Index -->
                  <td style="color: var(--color-text-light); font-weight: 600; font-size: 0.8rem;">
                    <?= $idx + 1 ?>
                  </td>

                  <!-- Subject Name -->
                  <td>
                    <div style="font-weight: 600; color: var(--color-text-main);">
                      <?= htmlspecialchars($sub['subject_name']) ?>
                    </div>
                    <div style="font-size: 0.72rem; color: var(--color-text-light);">
                      Subject ID: <code>SUB-<?= str_pad((string)$subId, 3, '0', STR_PAD_LEFT) ?></code> &bull; Core Curriculum
                    </div>
                  </td>

                  <!-- Maximum Marks -->
                  <td style="text-align: center;">
                    <span class="num-tabular font-mono" style="background: #f1f5f9; padding: 2px 8px; border-radius: var(--radius-sm); font-size: 0.8rem; font-weight: 600; color: var(--color-text-muted);">
                      <?= (int)$sub['maximum_marks'] ?>
                    </span>
                  </td>

                  <!-- Attendance Status Dropdown -->
                  <td>
                    <select 
                      name="attendance[<?= $subId ?>]" 
                      class="form-control attendance-select" 
                      style="height: 36px; font-size: 0.82rem; font-weight: 500;"
                    >
                      <option value="Present" <?= (!$isAbsent) ? 'selected' : '' ?>>Present</option>
                      <option value="Absent" <?= ($isAbsent) ? 'selected' : '' ?>>Absent</option>
                    </select>
                  </td>

                  <!-- Marks Obtained Input -->
                  <td style="text-align: right;">
                    <input 
                      type="number" 
                      step="0.01" 
                      min="0" 
                      max="<?= (float)$sub['maximum_marks'] ?>" 
                      name="marks[<?= $subId ?>]" 
                      value="<?= htmlspecialchars($currentMark) ?>" 
                      class="form-control marks-input" 
                      placeholder="<?= $isAbsent ? 'Absent (NULL)' : '0 - ' . (int)$sub['maximum_marks'] ?>"
                      <?= $isAbsent ? 'disabled' : '' ?>
                      style="display: inline-block;"
                    >
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Real-Time Dynamic Percentage Preview Card -->
        <div class="preview-percentage-card">
          <div class="preview-percentage-header">
            <div class="preview-percentage-title">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--color-primary);">
                <line x1="18" y1="20" x2="18" y2="10"></line>
                <line x1="12" y1="20" x2="12" y2="4"></line>
                <line x1="6" y1="20" x2="6" y2="14"></line>
              </svg>
              <span>Live Calculated Dynamic Percentage Preview</span>
            </div>
            <div>
              <span id="previewPctBadge" class="badge-status status-neutral">
                <span class="badge-dot"></span>
                Not Available
              </span>
            </div>
          </div>

          <!-- Progress Track -->
          <div class="preview-progress-track">
            <div id="previewPctBar" class="preview-progress-fill" style="width: 0%; background-color: #cbd5e1;"></div>
          </div>

          <div class="preview-percentage-stats">
            <div id="previewAttendedStats">
              Present in 7 / 7 subjects &bull; Absent in 0 subjects
            </div>
            <div>
              Formula: <code>(Marks Obtained / Total Attended Max) &times; 100</code>
            </div>
          </div>
        </div>
      </div>

      <!-- ===================================================================
           SECTION 3: FORM SUBMISSION ACTION BUTTONS
           =================================================================== -->
      <div class="form-footer" style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--color-border); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
        <div>
          <a href="students.php" class="btn btn-secondary">
            Cancel
          </a>
        </div>

        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
          <!-- Save and Add Another -->
          <button type="submit" name="action" value="save_and_add_another" class="btn btn-secondary" style="font-weight: 600;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
              <circle cx="8.5" cy="7" r="4"></circle>
              <line x1="20" y1="8" x2="20" y2="14"></line>
              <line x1="23" y1="11" x2="17" y2="11"></line>
            </svg>
            <span>Save &amp; Add Another</span>
          </button>

          <!-- Save Student (Primary) -->
          <button type="submit" name="action" value="save" class="btn btn-primary" style="font-weight: 600;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
              <polyline points="17 21 17 13 7 13 7 21"></polyline>
              <polyline points="7 3 7 8 15 8"></polyline>
            </svg>
            <span>Save Student</span>
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<?php
// Include Reusable Footer
include __DIR__ . '/includes/footer.php';
?>
