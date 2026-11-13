<?php
// edit_student.php
// Academic Operations System - Student Record Modification
// Section 08: Edit Student – UPDATE (Google Stitch UI Design & Transactional Integrity)

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Edit Student Record';
$currentPage = 'students';

// 1. Resolve student ID from GET or POST
$isDbConnected = (isset($pdo) && $pdo instanceof PDO);
$dbOffline = !$isDbConnected;
$dbErrorNotice = $dbOffline ? ($dbConnectionError ?? 'Database connection offline. Please check XAMPP MySQL.') : null;

$studentId = 0;
if (isset($_GET['id'])) {
    $studentId = (int)$_GET['id'];
} elseif (isset($_POST['id'])) {
    $studentId = (int)$_POST['id'];
}

// 2. Load student record
$student = ($isDbConnected && $studentId > 0) ? getStudentById($pdo, $studentId) : null;
$notFound = ($student === null);

// 3. Load all subjects with existing student marks
$subjectsData = [];
if ($isDbConnected && !$notFound) {
    $subjectsData = getStudentMarksWithSubjects($pdo, $studentId);
}

// State variables for form
$errors = [];
$studentName = $student['student_name'] ?? '';
$registerNo  = $student['register_no'] ?? '';
$mobileNo    = $student['mobile_number'] ?? '';
$dob         = $student['dob'] ?? '';

// Build marks map
$marksMap = [];
foreach ($subjectsData as $s) {
    $subId = (int)$s['subject_id'];
    $marksMap[$subId] = [
        'subject_id'    => $subId,
        'subject_name'  => $s['subject_name'],
        'maximum_marks' => (int)$s['maximum_marks'],
        'status'        => $s['attendance_status'] ?? 'Present',
        'marks'         => ($s['marks_obtained'] !== null) ? (string)$s['marks_obtained'] : ''
    ];
}

// 4. Handle POST Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$notFound) {
    // CSRF Protection
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        $errors[] = 'Security token invalid or expired. Please submit the form again.';
    }

    // Sanitize basic fields
    $studentName = trim($_POST['student_name'] ?? '');
    $registerNo  = trim($_POST['register_no'] ?? '');
    $mobileNo    = trim($_POST['mobile_number'] ?? '');
    $dob         = trim($_POST['dob'] ?? '');

    // Validation: Student Name
    if ($studentName === '') {
        $errors[] = 'Student Name is required.';
    } elseif (mb_strlen($studentName) < 2 || mb_strlen($studentName) > 100) {
        $errors[] = 'Student Name must be between 2 and 100 characters.';
    }

    // Validation: Register Number (Unique check excluding current student ID)
    if ($registerNo === '') {
        $errors[] = 'Register Number is required.';
    } elseif (!preg_match('/^[A-Za-z0-9_-]{3,30}$/', $registerNo)) {
        $errors[] = 'Register Number must be 3 to 30 alphanumeric characters (letters, numbers, hyphens, or underscores).';
    } elseif ($isDbConnected && isRegisterNumberTaken($pdo, $registerNo, $studentId)) {
        $errors[] = "Register Number '{$registerNo}' is already taken by another student in MySQL. Please use a unique register number.";
    }

    // Validation: Mobile Number (10 digits)
    if ($mobileNo === '') {
        $errors[] = 'Mobile Number is required.';
    } elseif (!preg_match('/^[0-9]{10}$/', $mobileNo)) {
        $errors[] = 'Mobile Number must be a valid 10-digit number (e.g. 9876500029).';
    }

    // Validation: Date of Birth
    if ($dob === '') {
        $errors[] = 'Date of Birth is required.';
    } else {
        $d = DateTime::createFromFormat('Y-m-d', $dob);
        $today = new DateTime('today');
        if (!$d || $d->format('Y-m-d') !== $dob) {
            $errors[] = 'Invalid Date of Birth format.';
        } elseif ($d > $today) {
            $errors[] = 'Date of Birth cannot be in the future.';
        } else {
            $age = $d->diff($today)->y;
            if ($age < 15 || $age > 80) {
                $errors[] = 'Student age must be between 15 and 80 years for academic enrollment.';
            }
        }
    }

    // Read and validate marks
    $submittedMarks = $_POST['marks'] ?? [];
    foreach ($marksMap as $subId => $curr) {
        $statusVal = $submittedMarks[$subId]['status'] ?? 'Present';
        $markVal   = trim($submittedMarks[$subId]['marks'] ?? '');

        $marksMap[$subId]['status'] = ($statusVal === 'Absent') ? 'Absent' : 'Present';
        $marksMap[$subId]['marks']  = $markVal;

        if ($marksMap[$subId]['status'] === 'Present') {
            if ($markVal === '') {
                $errors[] = "Marks obtained is required for '{$curr['subject_name']}' (or select Absent).";
            } elseif (!is_numeric($markVal)) {
                $errors[] = "Marks for '{$curr['subject_name']}' must be a valid number.";
            } else {
                $numVal = (float)$markVal;
                if ($numVal < 0 || $numVal > $curr['maximum_marks']) {
                    $errors[] = "Marks for '{$curr['subject_name']}' must be between 0 and {$curr['maximum_marks']}.";
                }
            }
        }
    }

    // If validation passed, execute atomic ACID update
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // 1. Update students table (Age is calculated dynamically, NOT stored in MySQL)
            $stmtUpdateStudent = $pdo->prepare("
                UPDATE students 
                SET student_name = :student_name,
                    register_no  = :register_no,
                    mobile_number = :mobile_number,
                    dob          = :dob,
                    updated_at   = NOW()
                WHERE id = :id
            ");
            $stmtUpdateStudent->execute([
                ':student_name'  => strtoupper($studentName),
                ':register_no'   => $registerNo,
                ':mobile_number' => $mobileNo,
                ':dob'           => $dob,
                ':id'            => $studentId
            ]);

            // 2. Update or insert student_marks using UNIQUE(student_id, subject_id)
            $stmtUpsertMarks = $pdo->prepare("
                INSERT INTO student_marks (student_id, subject_id, marks_obtained, attendance_status, created_at)
                VALUES (:student_id, :subject_id, :marks_obtained, :attendance_status, NOW())
                ON DUPLICATE KEY UPDATE 
                    marks_obtained = VALUES(marks_obtained),
                    attendance_status = VALUES(attendance_status)
            ");

            foreach ($marksMap as $subId => $markInfo) {
                $isAbsent = ($markInfo['status'] === 'Absent');
                $marksObtained = $isAbsent ? null : (float)$markInfo['marks'];
                $attendanceStatus = $isAbsent ? 'Absent' : 'Present';

                $stmtUpsertMarks->execute([
                    ':student_id'        => $studentId,
                    ':subject_id'        => $subId,
                    ':marks_obtained'    => $marksObtained,
                    ':attendance_status' => $attendanceStatus
                ]);
            }

            $pdo->commit();

            $_SESSION['flash_success'] = "Student '" . mb_strtoupper($studentName, 'UTF-8') . "' (Reg: {$registerNo}) was successfully updated.";
            header("Location: view_student.php?id=" . $studentId);
            exit;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Edit student transaction error: " . $e->getMessage());
            $errors[] = 'A database error occurred while saving your changes. No modifications were committed.';
        }
    }
}

// Generate CSRF token for the rendered form
$csrfToken = generateCsrfToken();

include __DIR__ . '/includes/header.php';
?>

  <!-- Top Navigation & Breadcrumbs -->
  <div class="page-header">
    <div class="breadcrumb">
      <a href="dashboard.php">Dashboard</a>
      <span class="breadcrumb-separator">/</span>
      <a href="students.php">Students</a>
      <span class="breadcrumb-separator">/</span>
      <?php if (!$notFound): ?>
        <a href="view_student.php?id=<?= (int)$studentId ?>"><?= htmlspecialchars($student['student_name']) ?></a>
        <span class="breadcrumb-separator">/</span>
      <?php endif; ?>
      <span class="breadcrumb-current">Edit Student</span>
    </div>

    <div class="header-action-row">
      <div>
        <h1 class="page-title">
          <?= $notFound ? 'Modify Student Record' : 'Edit: ' . htmlspecialchars($student['student_name']) ?>
        </h1>
        <p class="page-subtitle">
          <?= $notFound ? 'Student profile update in central registry' : 'Modify biographical demographics and semester examination marks with atomic rollback.' ?>
        </p>
      </div>

      <div class="header-btn-group">
        <a href="<?= !$notFound ? 'view_student.php?id=' . (int)$studentId : 'students.php' ?>" class="btn btn-outline">
          &larr; Cancel and Return
        </a>
      </div>
    </div>
  </div>

  <?php if (!empty($dbErrorNotice)): ?>
    <div class="form-alert alert-danger">
      <span class="alert-icon">&excl;</span>
      <div>
        <strong>Database Unavailable:</strong> <?= htmlspecialchars($dbErrorNotice) ?>
      </div>
    </div>
  <?php elseif ($notFound): ?>
    <!-- Friendly Not Found Error State -->
    <div class="content-card text-center" style="padding: 4rem 2rem; max-width: 680px; margin: 2rem auto;">
      <div style="width: 64px; height: 64px; border-radius: 50%; background: #fee2e2; color: #ef4444; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="8" x2="12" y2="12"></line>
          <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
      </div>
      <h2 style="font-size: 1.35rem; font-weight: 700; color: var(--color-text-main); margin-bottom: 0.5rem;">
        Student Record Not Found
      </h2>
      <p style="color: var(--color-text-muted); font-size: 0.9rem; line-height: 1.6; margin-bottom: 1.75rem;">
        No active student record was found in MySQL with ID <strong class="num-tabular font-mono">#<?= htmlspecialchars((string)$studentId) ?></strong>. The record cannot be edited.
      </p>
      <div style="display: flex; gap: 0.75rem; justify-content: center;">
        <a href="students.php" class="btn btn-primary">
          Return to Students Directory
        </a>
      </div>
    </div>
  <?php else: ?>

    <!-- Inline Error Alert -->
    <?php if (!empty($errors)): ?>
      <div class="form-alert alert-danger">
        <span class="alert-icon">&excl;</span>
        <div>
          <strong>Please correct the following errors before updating:</strong>
          <ul class="error-list">
            <?php foreach ($errors as $err): ?>
              <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    <?php endif; ?>

    <!-- Edit Student Form -->
    <form action="edit_student.php?id=<?= (int)$studentId ?>" method="POST" id="editStudentForm" class="student-form" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <input type="hidden" name="id" value="<?= (int)$studentId ?>">

      <!-- Card 1: Student Demographics -->
      <div class="content-card">
        <div class="card-header-flex">
          <div>
            <h2 class="card-title">Student Personal Demographics</h2>
            <p class="card-subtitle">Targeting <code>students</code> MySQL table for record #<?= (int)$studentId ?></p>
          </div>
          <span class="badge badge-info">Step 1 of 2</span>
        </div>

        <div class="form-grid">
          <!-- Student Name -->
          <div class="form-group">
            <label for="student_name" class="form-label">
              Student Full Name <span class="required-star">*</span>
            </label>
            <input
              type="text"
              id="student_name"
              name="student_name"
              class="form-control text-uppercase"
              placeholder="e.g. PRAVEEN KUMAR S"
              value="<?= htmlspecialchars($studentName) ?>"
              required
              maxlength="100"
            >
            <small class="field-hint">Legal registered name in university ledger.</small>
          </div>

          <!-- Register Number -->
          <div class="form-group">
            <label for="register_no" class="form-label">
              Register Number <span class="required-star">*</span>
            </label>
            <input
              type="text"
              id="register_no"
              name="register_no"
              class="form-control font-mono"
              placeholder="e.g. 711124234029"
              value="<?= htmlspecialchars($registerNo) ?>"
              required
              maxlength="30"
            >
            <small class="field-hint">Must remain unique across all cohort records.</small>
          </div>

          <!-- Mobile Number -->
          <div class="form-group">
            <label for="mobile_number" class="form-label">
              Mobile Number <span class="required-star">*</span>
            </label>
            <input
              type="tel"
              id="mobile_number"
              name="mobile_number"
              class="form-control font-mono"
              placeholder="e.g. 9876500029"
              value="<?= htmlspecialchars($mobileNo) ?>"
              required
              maxlength="10"
              pattern="[0-9]{10}"
            >
            <small class="field-hint">10-digit mobile contact number.</small>
          </div>

          <!-- Date of Birth & Live Calculated Age -->
          <div class="form-group-row">
            <div class="form-group">
              <label for="dob" class="form-label">
                Date of Birth <span class="required-star">*</span>
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
            </div>

            <div class="form-group">
              <label for="calculated_age" class="form-label label-with-tag">
                <span>Calculated Age</span>
                <span class="tag-hint">Dynamic • Not in DB</span>
              </label>
              <input
                type="text"
                id="calculated_age"
                class="form-control input-readonly font-mono font-bold"
                placeholder="Auto from DOB"
                readonly
              >
            </div>
          </div>
        </div>
      </div>

      <!-- Card 2: Subject Marks Section -->
      <div class="content-card">
        <div class="card-header-flex">
          <div>
            <h2 class="card-title">Subject Examination Marks</h2>
            <p class="card-subtitle">Updating marks and attendance status for all seven registered curriculum subjects.</p>
          </div>
          <span class="badge badge-info">Step 2 of 2</span>
        </div>

        <div class="table-responsive">
          <table class="marks-entry-table" id="marksTable">
            <thead>
              <tr>
                <th class="w-12 text-center">#</th>
                <th>Subject Name</th>
                <th class="w-28 text-center">Max Marks</th>
                <th class="w-36 text-center">Attendance Status</th>
                <th class="w-48">Marks Obtained (0–Max)</th>
              </tr>
            </thead>
            <tbody>
              <?php 
                $rowIdx = 0;
                foreach ($marksMap as $subId => $curr): 
                  $rowIdx++;
                  $isAbsent = ($curr['status'] === 'Absent');
              ?>
                <tr class="subject-row <?= $isAbsent ? 'row-absent' : '' ?>" data-subject-id="<?= $subId ?>" data-max-marks="<?= $curr['maximum_marks'] ?>">
                  <td class="text-center font-mono text-muted"><?= $rowIdx ?></td>
                  <td>
                    <strong><?= htmlspecialchars($curr['subject_name']) ?></strong>
                    <span class="subject-subtext">Subject ID: <?= $subId ?></span>
                  </td>
                  <td class="text-center font-mono font-bold"><?= $curr['maximum_marks'] ?></td>
                  <td class="text-center">
                    <select
                      name="marks[<?= $subId ?>][status]"
                      class="form-select attendance-select"
                      data-subject-id="<?= $subId ?>"
                    >
                      <option value="Present" <?= !$isAbsent ? 'selected' : '' ?>>Present</option>
                      <option value="Absent" <?= $isAbsent ? 'selected' : '' ?>>Absent</option>
                    </select>
                  </td>
                  <td>
                    <div class="marks-input-wrapper">
                      <input
                        type="number"
                        name="marks[<?= $subId ?>][marks]"
                        class="form-control marks-input font-mono"
                        min="0"
                        max="<?= $curr['maximum_marks'] ?>"
                        step="0.01"
                        placeholder="0 - <?= $curr['maximum_marks'] ?>"
                        value="<?= htmlspecialchars($curr['marks']) ?>"
                        <?= $isAbsent ? 'disabled' : 'required' ?>
                        data-subject-id="<?= $subId ?>"
                      >
                      <span class="marks-max-tag">/ <?= $curr['maximum_marks'] ?></span>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Card 3: Live Dynamic Percentage Calculation Widget -->
      <div class="preview-percentage-card" id="percentagePreviewCard">
        <div class="preview-header">
          <div>
            <span class="preview-badge">Live Percentage Recalculation</span>
            <p class="preview-formula">
              Formula: <code>(Total Marks Obtained / Total Maximum of Attended Subjects) &times; 100</code>
            </p>
          </div>
          <div class="preview-value-box">
            <div class="preview-pct" id="previewPctDisplay">--</div>
            <div class="preview-status" id="previewStatusDisplay">Awaiting Marks Input</div>
          </div>
        </div>
        <div class="preview-progress-track">
          <div class="preview-progress-fill" id="previewProgressFill" style="width: 0%;"></div>
        </div>
        <div class="preview-footer">
          <span id="previewCountSummary">Attended: 0 &bull; Absent: 0</span>
          <span id="previewTierSummary" class="font-semibold text-slate-300">--</span>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="form-actions-bar">
        <a href="view_student.php?id=<?= (int)$studentId ?>" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary" id="btnSaveStudent">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
          Save Dossier Changes
        </button>
      </div>
    </form>
  <?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
