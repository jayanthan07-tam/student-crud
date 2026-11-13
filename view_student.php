<?php
// view_student.php
// Academic Operations System - Student Dossier & Performance Record
// Section 08: View Student – READ (Google Stitch UI Design & Dynamic Evaluation)

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Student Dossier';
$currentPage = 'students';

// 1. Validate requested student ID & check database availability
$isDbConnected = (isset($pdo) && $pdo instanceof PDO);
$dbOffline = !$isDbConnected;
$dbErrorNotice = $dbOffline ? ($dbConnectionError ?? 'Database connection offline. Please check XAMPP MySQL.') : null;

$studentId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$dossier = null;
$notFound = false;

if (!$isDbConnected) {
    $notFound = true;
} elseif ($studentId <= 0) {
    $notFound = true;
} else {
    $dossier = getStudentCompleteDossier($pdo, $studentId);
    if (!$dossier) {
        $notFound = true;
    }
}

// Generate CSRF token for deletion modal
$csrfToken = generateCsrfToken();

// Check for flash messages
$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

include __DIR__ . '/includes/header.php';
?>

  <!-- Top Navigation & Breadcrumbs -->
  <div class="page-header">
    <div class="breadcrumb">
      <a href="dashboard.php">Dashboard</a>
      <span class="breadcrumb-separator">/</span>
      <a href="students.php">Students</a>
      <span class="breadcrumb-separator">/</span>
      <span class="breadcrumb-current"><?= $notFound ? 'Record Not Found' : htmlspecialchars($dossier['student']['student_name']) ?></span>
    </div>

    <div class="header-action-row">
      <div>
        <h1 class="page-title">
          <?= $notFound ? 'Student Record Dossier' : htmlspecialchars($dossier['student']['student_name']) ?>
        </h1>
        <p class="page-subtitle">
          <?= $notFound ? 'Student profile lookup in central registry' : 'Official verified registry record &bull; Dynamic academic standing evaluation.' ?>
        </p>
      </div>

      <div class="header-btn-group">
        <a href="students.php" class="btn btn-outline">
          &larr; Back to Students
        </a>
        <?php if (!$notFound): ?>
          <a href="edit_student.php?id=<?= (int)$dossier['student']['id'] ?>" class="btn btn-primary">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
              <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
            </svg>
            Edit Student
          </a>
          <button 
            type="button" 
            class="btn btn-danger js-delete-trigger"
            data-id="<?= (int)$dossier['student']['id'] ?>"
            data-name="<?= htmlspecialchars($dossier['student']['student_name'], ENT_QUOTES, 'UTF-8') ?>"
            data-reg="<?= htmlspecialchars($dossier['student']['register_no'], ENT_QUOTES, 'UTF-8') ?>"
          >
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="3 6 5 6 21 6"></polyline>
              <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
            </svg>
            Delete
          </button>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Flash Messages -->
  <?php if (!empty($flashSuccess)): ?>
    <div class="form-alert alert-success">
      <span class="alert-icon">&check;</span>
      <div>
        <strong>Success:</strong> <?= htmlspecialchars($flashSuccess) ?>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!empty($flashError)): ?>
    <div class="form-alert alert-danger">
      <span class="alert-icon">&excl;</span>
      <div>
        <strong>Notice:</strong> <?= htmlspecialchars($flashError) ?>
      </div>
    </div>
  <?php endif; ?>

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
        No active student record was found in the database with ID <strong class="num-tabular font-mono">#<?= htmlspecialchars((string)$studentId) ?></strong>. The student may have been deleted, or an invalid ID parameter was supplied.
      </p>
      <div style="display: flex; gap: 0.75rem; justify-content: center;">
        <a href="students.php" class="btn btn-primary">
          Return to Students Directory
        </a>
        <a href="add_student.php" class="btn btn-secondary">
          Enroll New Student
        </a>
      </div>
    </div>
  <?php else: 
    $student = $dossier['student'];
    $initials = getStudentInitials($student['student_name']);
    $badge = $dossier['badge'];
    $pctValue = $dossier['dynamicPercentage'];
  ?>
    <!-- Student Header Identity Banner -->
    <div class="content-card" style="margin-bottom: 1.5rem;">
      <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1.5rem;">
        <div style="display: flex; align-items: center; gap: 1.25rem;">
          <div class="user-avatar" style="width: 64px; height: 64px; font-size: 1.5rem; background: #e0e7ff; color: #4338ca; border-radius: var(--radius-lg); font-weight: 800; flex-shrink: 0;">
            <?= htmlspecialchars($initials) ?>
          </div>
          <div>
            <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
              <h2 style="font-size: 1.4rem; font-weight: 800; color: var(--color-text-main); letter-spacing: -0.02em;">
                <?= htmlspecialchars($student['student_name']) ?>
              </h2>
              <span class="badge badge-info font-mono">
                Record #<?= (int)$student['id'] ?>
              </span>
              <span class="badge-status <?= htmlspecialchars($badge['class']) ?>">
                <span class="badge-dot"></span>
                <?= htmlspecialchars($badge['text']) ?>
              </span>
            </div>
            <p style="font-size: 0.85rem; color: var(--color-text-muted); margin-top: 0.25rem;">
              Department of Computer Science and Business Systems &bull; Section 04 Verified Dataset
            </p>
          </div>
        </div>

        <div style="display: flex; gap: 1.5rem; align-items: center; border-left: 1px solid var(--color-border); padding-left: 1.5rem;">
          <div class="text-right">
            <span style="font-size: 0.75rem; color: var(--color-text-light); text-transform: uppercase; font-weight: 600; display: block;">
              Overall Evaluation
            </span>
            <div style="font-size: 1.75rem; font-weight: 800; font-family: monospace; color: var(--color-text-main); line-height: 1.2;">
              <?php if ($pctValue === 'Absent'): ?>
                <span style="color: #ef4444;">ABSENT</span>
              <?php elseif ($pctValue !== null): ?>
                <?= number_format((float)$pctValue, 2) ?><span style="font-size: 1rem; color: var(--color-text-muted); font-weight: 500;">%</span>
              <?php else: ?>
                <span style="color: var(--color-text-light); font-size: 1.25rem;">N/A</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 2-Column Grid: Left Personal Demographics, Right Academic Standing Overview -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
      <!-- Card 1: Personal Demographics -->
      <div class="content-card">
        <div class="card-header-flex">
          <div>
            <h3 class="card-title">Personal Credentials</h3>
            <p class="card-subtitle">Stored in primary <code>students</code> MySQL table</p>
          </div>
          <span class="badge badge-neutral">Identity Dossier</span>
        </div>

        <div class="dossier-list" style="display: flex; flex-direction: column; gap: 1rem;">
          <div class="dossier-item" style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--color-border); padding-bottom: 0.75rem;">
            <span style="font-size: 0.82rem; color: var(--color-text-muted); font-weight: 500;">University Register No:</span>
            <span class="num-tabular font-mono" style="font-weight: 700; color: var(--color-text-main);">
              <?= htmlspecialchars($student['register_no']) ?>
            </span>
          </div>

          <div class="dossier-item" style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--color-border); padding-bottom: 0.75rem;">
            <span style="font-size: 0.82rem; color: var(--color-text-muted); font-weight: 500;">Contact Phone:</span>
            <span class="num-tabular font-mono" style="font-weight: 600; color: var(--color-text-main);">
              <?= htmlspecialchars($student['mobile_number'] ?? 'N/A') ?>
            </span>
          </div>

          <div class="dossier-item" style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--color-border); padding-bottom: 0.75rem;">
            <span style="font-size: 0.82rem; color: var(--color-text-muted); font-weight: 500;">Date of Birth:</span>
            <span class="num-tabular" style="font-weight: 600; color: var(--color-text-main);">
              <?= date('F j, Y', strtotime($student['dob'])) ?>
            </span>
          </div>

          <div class="dossier-item" style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--color-border); padding-bottom: 0.75rem;">
            <span style="font-size: 0.82rem; color: var(--color-text-muted); font-weight: 500;">
              Calculated Age:
              <span class="tag-hint" style="margin-left: 4px;">Dynamic • Not in DB</span>
            </span>
            <span class="num-tabular font-mono" style="font-weight: 700; color: #4338ca; background: #e0e7ff; padding: 0.15rem 0.5rem; border-radius: var(--radius-sm);">
              <?= (int)$dossier['age'] ?> years old
            </span>
          </div>

          <div class="dossier-item" style="display: flex; justify-content: space-between;">
            <span style="font-size: 0.82rem; color: var(--color-text-muted); font-weight: 500;">Registry Enrollment:</span>
            <span class="num-tabular" style="font-size: 0.82rem; color: var(--color-text-muted);">
              <?= !empty($student['created_at']) ? date('M j, Y, g:i A', strtotime($student['created_at'])) : 'Section 04 Dataset' ?>
            </span>
          </div>
        </div>
      </div>

      <!-- Card 2: Academic Standing Summary -->
      <div class="content-card" style="display: flex; flex-direction: column; justify-content: space-between;">
        <div>
          <div class="card-header-flex">
            <div>
              <h3 class="card-title">Academic Evaluation</h3>
              <p class="card-subtitle">Formula: <code>(Obtained / Attended Max) &times; 100</code></p>
            </div>
            <span class="badge badge-info">Semester I</span>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1.25rem;">
            <div style="background: var(--color-canvas); padding: 0.85rem; border-radius: var(--radius-md); border: 1px solid var(--color-border);">
              <span style="font-size: 0.75rem; color: var(--color-text-light); text-transform: uppercase; font-weight: 600;">Total Marks Obtained</span>
              <div style="font-size: 1.35rem; font-weight: 800; font-family: monospace; color: var(--color-text-main); margin-top: 0.2rem;">
                <?= number_format($dossier['totalObtained'], 2) ?>
              </div>
            </div>

            <div style="background: var(--color-canvas); padding: 0.85rem; border-radius: var(--radius-md); border: 1px solid var(--color-border);">
              <span style="font-size: 0.75rem; color: var(--color-text-light); text-transform: uppercase; font-weight: 600;">Attended Max Marks</span>
              <div style="font-size: 1.35rem; font-weight: 800; font-family: monospace; color: var(--color-text-main); margin-top: 0.2rem;">
                <?= (int)$dossier['totalAttendedMax'] ?>
              </div>
            </div>
          </div>

          <div style="margin-bottom: 1rem;">
            <div style="display: flex; justify-content: space-between; font-size: 0.8rem; margin-bottom: 0.35rem;">
              <span style="color: var(--color-text-muted);">Curriculum Attendance</span>
              <span style="font-weight: 600; color: var(--color-text-main);">
                Attended <?= $dossier['presentCount'] ?> / <?= $dossier['totalCount'] ?> Subjects
                <?php if ($dossier['absentCount'] > 0): ?>
                  <span style="color: #ef4444; font-weight: 600;">(<?= $dossier['absentCount'] ?> Absent)</span>
                <?php endif; ?>
              </span>
            </div>

            <!-- Progress Track -->
            <div style="width: 100%; height: 8px; background: #e2e8f0; border-radius: 9999px; overflow: hidden;">
              <?php 
                $barPct = ($pctValue !== null && $pctValue !== 'Absent') ? min(100, max(0, (float)$pctValue)) : 0;
                $barColor = ($barPct >= 70) ? '#10b981' : (($barPct >= 50) ? '#6366f1' : (($barPct >= 40) ? '#f59e0b' : '#ef4444'));
              ?>
              <div style="width: <?= $barPct ?>%; height: 100%; background: <?= $barColor ?>; transition: width 0.3s ease;"></div>
            </div>
          </div>
        </div>

        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: var(--radius-md); padding: 0.75rem 1rem; font-size: 0.78rem; color: var(--color-text-muted);">
          <strong>Rule Enforced:</strong> Absent subjects are excluded from both obtained marks and the maximum denominator. If all seven subjects are marked Absent, overall standing is recorded as "Absent" and excluded from the cohort average.
        </div>
      </div>
    </div>

    <!-- Card 3: All Seven Subject Marks Detailed Table -->
    <div class="content-card">
      <div class="card-header-flex">
        <div>
          <h3 class="card-title">Curriculum Examination Results</h3>
          <p class="card-subtitle">All seven semester courses registered in MySQL <code>subjects</code> &amp; <code>student_marks</code> tables.</p>
        </div>
        <span class="badge badge-neutral">7 Registered Courses</span>
      </div>

      <div class="table-responsive">
        <table class="marks-entry-table">
          <thead>
            <tr>
              <th class="w-12 text-center">#</th>
              <th>Course Subject Name</th>
              <th class="w-28 text-center">Maximum Marks</th>
              <th class="w-36 text-center">Attendance Status</th>
              <th class="w-36 text-right">Marks Obtained</th>
              <th class="w-36 text-right">Performance Ratio</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($dossier['marks'] as $idx => $mark): 
              $isAbsent = ($mark['attendance_status'] === 'Absent');
              $max = (int)($mark['maximum_marks'] ?? 100);
              $obtained = $mark['marks_obtained'];
            ?>
              <tr class="<?= $isAbsent ? 'row-absent' : '' ?>">
                <td class="text-center font-mono text-muted"><?= $idx + 1 ?></td>
                <td>
                  <strong style="color: var(--color-text-main); font-size: 0.88rem;">
                    <?= htmlspecialchars($mark['subject_name']) ?>
                  </strong>
                  <span class="subject-subtext">Subject ID #<?= (int)$mark['subject_id'] ?></span>
                </td>
                <td class="text-center font-mono font-bold text-muted">
                  <?= $max ?>
                </td>
                <td class="text-center">
                  <?php if ($isAbsent): ?>
                    <span class="badge-status badge-danger">
                      <span class="badge-dot"></span> Absent
                    </span>
                  <?php else: ?>
                    <span class="badge-status badge-success">
                      <span class="badge-dot"></span> Present
                    </span>
                  <?php endif; ?>
                </td>
                <td class="text-right font-mono font-bold">
                  <?php if ($isAbsent): ?>
                    <span style="color: #94a3b8; font-weight: 600;">Absent (NULL)</span>
                  <?php elseif ($obtained !== null): ?>
                    <span style="color: var(--color-text-main); font-size: 0.95rem;">
                      <?= number_format((float)$obtained, 2) ?>
                    </span>
                  <?php else: ?>
                    <span style="color: var(--color-text-light);">Not Available</span>
                  <?php endif; ?>
                </td>
                <td class="text-right font-mono text-muted">
                  <?php if ($isAbsent): ?>
                    <span style="color: #cbd5e1;">--</span>
                  <?php elseif ($obtained !== null && $max > 0): 
                    $subPct = ($obtained / $max) * 100;
                  ?>
                    <span style="font-weight: 600; color: <?= ($subPct >= 70) ? '#10b981' : (($subPct >= 50) ? '#4f46e5' : '#ef4444') ?>;">
                      <?= number_format($subPct, 1) ?>%
                    </span>
                  <?php else: ?>
                    <span style="color: #cbd5e1;">--</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr style="background: #fafbfd; font-weight: 700; border-top: 2px solid var(--color-border);">
              <td colspan="2" style="padding: 1rem 1.25rem;">Total Attended Summary</td>
              <td class="text-center font-mono"><?= (int)$dossier['totalAttendedMax'] ?></td>
              <td class="text-center">
                <span class="badge-status badge-primary">
                  <?= $dossier['presentCount'] ?> Attended
                </span>
              </td>
              <td class="text-right font-mono" style="font-size: 1rem; color: var(--color-primary);">
                <?= number_format($dossier['totalObtained'], 2) ?>
              </td>
              <td class="text-right font-mono" style="font-size: 1.05rem; color: <?= $barColor ?>;">
                <?php if ($pctValue === 'Absent'): ?>
                  Absent
                <?php elseif ($pctValue !== null): ?>
                  <?= number_format((float)$pctValue, 2) ?>%
                <?php else: ?>
                  N/A
                <?php endif; ?>
              </td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  <?php endif; ?>

<!-- Modal Sheet for Record Deletion Confirmation -->
<div class="modal-overlay" id="deleteModal" style="display: none;">
  <div class="modal-dialog">
    <div class="modal-header">
      <h3 class="modal-title" style="color: var(--color-danger); display: flex; align-items: center; gap: 0.5rem;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
          <line x1="12" y1="9" x2="12" y2="13"></line>
          <line x1="12" y1="17" x2="12.01" y2="17"></line>
        </svg>
        Confirm Record Deletion
      </h3>
      <button type="button" class="action-btn js-modal-dismiss" aria-label="Close Modal">&times;</button>
    </div>
    <div class="modal-body">
      <p style="font-size: 0.95rem; color: var(--color-text-main); margin-bottom: 0.75rem;">
        Are you certain you wish to delete the student profile for <strong id="studentDeleteName">this student</strong> (<span id="studentDeleteReg" class="font-mono"></span>)?
      </p>
      <div style="background: #fff1f2; border: 1px solid #fecdd3; padding: 0.75rem 1rem; border-radius: var(--radius-md); font-size: 0.8rem; color: #9f1239;">
        <strong>Warning:</strong> Deleting this student will cascade-remove all their examination marks and academic standing records from MySQL.
      </div>
    </div>
    <form action="delete_student.php" method="POST" id="deleteStudentForm">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
      <input type="hidden" name="id" id="deleteStudentIdInput" value="">
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary js-modal-dismiss">Cancel</button>
        <button type="submit" class="btn btn-danger">Permanently Delete</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
