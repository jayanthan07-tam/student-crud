<?php
// delete_student.php
// Academic Operations System - Student Record Deletion Handler & Confirmation
// Section 08: Delete Student – DELETE (POST-Only Execution, CSRF Protection & Transactional Cascade)

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Confirm Record Deletion';
$currentPage = 'students';

// =========================================================================
// 1. Process Destructive POST Request (Actual Deletion)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection Check
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        $_SESSION['flash_error'] = 'Security verification failed: Invalid or expired CSRF token. Deletion aborted.';
        header('Location: students.php');
        exit;
    }

    $isDbConnected = (isset($pdo) && $pdo instanceof PDO);
    if (!$isDbConnected) {
        $_SESSION['flash_error'] = $dbConnectionError ?? 'Database connection offline. Could not delete student record.';
        header('Location: students.php');
        exit;
    }

    // Validate Student ID
    $studentId = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0);

    if ($studentId <= 0) {
        $_SESSION['flash_error'] = 'Invalid student identifier provided for deletion.';
        header('Location: students.php');
        exit;
    }

    // Verify student exists and capture details for flash confirmation
    $student = getStudentById($pdo, $studentId);
    if (!$student) {
        $_SESSION['flash_error'] = "Student record #{$studentId} was not found in the database or has already been removed.";
        header('Location: students.php');
        exit;
    }

    $studentName = $student['student_name'];
    $registerNo  = $student['register_no'];

    // Execute atomic ACID deletion of marks and student record
    $deleted = deleteStudentWithMarks($pdo, $studentId);

    if ($deleted) {
        $_SESSION['flash_success'] = "Student '{$studentName}' (Reg: {$registerNo}) and all associated examination records were permanently deleted.";
    } else {
        $_SESSION['flash_error'] = "Failed to delete student '{$studentName}'. The database transaction was safely rolled back.";
    }

    header('Location: students.php');
    exit;
}

// =========================================================================
// 2. HTTP GET Request: Security Notice & Confirmation View
// Rule: Never delete records using GET requests!
// =========================================================================
$isDbConnected = (isset($pdo) && $pdo instanceof PDO);
$dbErrorNotice = !$isDbConnected ? ($dbConnectionError ?? 'Database connection offline. Please check XAMPP MySQL.') : null;
$studentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$student = ($isDbConnected && $studentId > 0) ? getStudentById($pdo, $studentId) : null;
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
      <span class="breadcrumb-current">Delete Student</span>
    </div>

    <div class="header-action-row">
      <div>
        <h1 class="page-title" style="color: var(--color-danger);">Confirm Record Deletion</h1>
        <p class="page-subtitle">Destructive registry operation &bull; Explicit administrator confirmation required.</p>
      </div>

      <a href="students.php" class="btn btn-outline">
        &larr; Cancel and Return to Students
      </a>
    </div>
  </div>

  <?php if (!empty($dbErrorNotice)): ?>
    <div class="form-alert alert-danger">
      <span class="alert-icon">&excl;</span>
      <div>
        <strong>Database Unavailable:</strong> <?= htmlspecialchars($dbErrorNotice) ?>
      </div>
    </div>
  <?php elseif (!$student): ?>
    <!-- Student Not Found Warning -->
    <div class="content-card text-center" style="padding: 4rem 2rem; max-width: 600px; margin: 2rem auto;">
      <div style="width: 56px; height: 56px; border-radius: 50%; background: #fee2e2; color: #ef4444; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem;">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="8" x2="12" y2="12"></line>
          <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
      </div>
      <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--color-text-main); margin-bottom: 0.5rem;">
        Student Record Not Found
      </h2>
      <p style="color: var(--color-text-muted); font-size: 0.88rem; line-height: 1.5; margin-bottom: 1.5rem;">
        No active student record with ID <strong class="num-tabular font-mono">#<?= htmlspecialchars((string)$studentId) ?></strong> exists in the database.
      </p>
      <a href="students.php" class="btn btn-primary">Return to Students Directory</a>
    </div>
  <?php else: ?>
    <!-- Security Warning Card -->
    <div class="content-card" style="max-width: 640px; margin: 1.5rem auto; border-top: 4px solid var(--color-danger);">
      <div class="card-header-flex">
        <h2 class="card-title" style="color: var(--color-danger); display: flex; align-items: center; gap: 0.5rem;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
            <line x1="12" y1="9" x2="12" y2="13"></line>
            <line x1="12" y1="17" x2="12.01" y2="17"></line>
          </svg>
          Permanent Record Deletion
        </h2>
        <span class="badge badge-danger">Irreversible</span>
      </div>

      <div style="background: #fff1f2; border: 1px solid #fecdd3; border-radius: var(--radius-md); padding: 1rem 1.25rem; margin-bottom: 1.5rem; display: flex; gap: 0.75rem; align-items: flex-start;">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#e11d48" stroke-width="2" style="flex-shrink: 0; margin-top: 2px;">
          <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
        </svg>
        <div style="font-size: 0.85rem; color: #9f1239; line-height: 1.5;">
          <strong>Security Notice:</strong> HTTP GET requests cannot alter or delete student records. To expunge this student, confirm deletion via the secure POST action below.
        </div>
      </div>

      <!-- Target Student Dossier Preview -->
      <div style="background: var(--color-canvas); padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--color-border); margin-bottom: 1.5rem;">
        <div style="font-size: 0.75rem; color: var(--color-text-light); text-transform: uppercase; font-weight: 700; margin-bottom: 0.35rem;">
          Record Designated for Removal
        </div>
        <div style="font-size: 1.2rem; font-weight: 800; color: var(--color-text-main);">
          <?= htmlspecialchars($student['student_name']) ?>
        </div>
        <div style="display: flex; gap: 1rem; margin-top: 0.5rem; font-size: 0.85rem; color: var(--color-text-muted);">
          <span>Register No: <strong class="font-mono text-main"><?= htmlspecialchars($student['register_no']) ?></strong></span>
          <span>&bull;</span>
          <span>Record ID: <strong class="font-mono">#<?= (int)$student['id'] ?></strong></span>
          <span>&bull;</span>
          <span>Age: <strong><?= calculateAge($student['dob']) ?> yrs</strong></span>
        </div>
      </div>

      <!-- Secure POST Deletion Form -->
      <form action="delete_student.php" method="POST" id="confirmDeleteForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <input type="hidden" name="id" value="<?= (int)$student['id'] ?>">

        <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
          <a href="students.php" class="btn btn-secondary">
            Cancel
          </a>
          <button type="submit" class="btn btn-danger" style="display: inline-flex; align-items: center; gap: 0.4rem;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="3 6 5 6 21 6"></polyline>
              <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
            </svg>
            Permanently Expunge Student
          </button>
        </div>
      </form>
    </div>
  <?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
