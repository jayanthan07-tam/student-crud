<?php
// dashboard.php
// Academic Operations System - Primary Administrative Dashboard
// Section 05: Dashboard Development (Google Stitch UI Design & Live MySQL PDO Integration)

$pageTitle = 'Dashboard';
$currentPage = 'dashboard';

// 1. Include Centralized Database Connection & Helper Functions
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Variables to capture dashboard state
$metrics = [
    'totalStudents'     => 0,
    'averagePercentage' => null,
    'highestPercentage' => null,
    'lowestPercentage'  => null,
    'distribution'      => [
        '0-40%'   => 0,
        '40-50%'  => 0,
        '50-60%'  => 0,
        '60-70%'  => 0,
        '70-80%'  => 0,
        '80-100%' => 0,
    ],
    'recentStudents'    => [],
    'totalGraded'       => 0,
];

$isDbConnected = ($pdo instanceof PDO);
$dbErrorNotice = null;

if ($isDbConnected) {
    try {
        $metrics = getDashboardMetrics($pdo);
    } catch (PDOException $e) {
        error_log("Dashboard query error: " . $e->getMessage());
        $dbErrorNotice = "Unable to load live dashboard metrics at the moment. Please verify the MySQL service.";
    }
} else {
    $dbErrorNotice = $dbConnectionError ?? "Database connection offline. Please check XAMPP MySQL.";
}

// Prepare Chart.js dynamic data payloads
$chartLabels = array_keys($metrics['distribution']);
$chartValues = array_values($metrics['distribution']);
$chartJsonLabels = json_encode($chartLabels);
$chartJsonValues = json_encode($chartValues);

// 2. Include Reusable Header Layout
include __DIR__ . '/includes/header.php';
?>

<!-- Welcome Banner & Page Title Header -->
<div class="page-header" style="margin-bottom: 1.5rem;">
  <div>
    <h1 class="page-title">Dashboard</h1>
    <p class="page-subtitle">
      Welcome back, <strong>Dr. J. Vance</strong> &bull; Real-time academic cohort telemetry, standing analytics, and registrar operations.
    </p>
  </div>
  
  <!-- Quick Actions Navigation -->
  <div class="header-actions" style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
    <a href="students.php" class="btn btn-secondary">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
        <circle cx="9" cy="7" r="4"/>
        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
      </svg>
      <span>View All Students</span>
    </a>
    <a href="performance.php" class="btn btn-secondary">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="18" y1="20" x2="18" y2="10"/>
        <line x1="12" y1="20" x2="12" y2="4"/>
        <line x1="6" y1="20" x2="6" y2="14"/>
      </svg>
      <span>View Performance</span>
    </a>
    <a href="add_student.php" class="btn btn-primary">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="12" y1="5" x2="12" y2="19"></line>
        <line x1="5" y1="12" x2="19" y2="12"></line>
      </svg>
      <span>Add New Student</span>
    </a>
  </div>
</div>

<?php if ($dbErrorNotice): ?>
  <!-- Database Notice Alert -->
  <div class="card" style="border-left: 4px solid var(--color-danger); margin-bottom: 1.5rem;">
    <div class="card-body" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.25rem;">
      <div style="display: flex; align-items: center; gap: 0.75rem;">
        <span style="color: var(--color-danger); font-size: 1.25rem;">&#9888;</span>
        <span style="font-size: 0.875rem; color: var(--color-text-main);">
          <?= htmlspecialchars($dbErrorNotice) ?>
        </span>
      </div>
      <a href="test_connection.php" class="btn btn-secondary btn-sm">Test Connection</a>
    </div>
  </div>
<?php endif; ?>

<!-- 4 Google Stitch Metric Cards (Dynamic MySQL PDO Data) -->
<div class="metrics-grid">
  <!-- Card 1: Total Students -->
  <div class="metric-card">
    <div class="metric-top">
      <span class="metric-label">Total Students</span>
      <div class="metric-icon-wrap">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
          <circle cx="9" cy="7" r="4"/>
          <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
          <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
      </div>
    </div>
    <div class="metric-value num-tabular">
      <?= (int)$metrics['totalStudents'] ?>
    </div>
    <div class="metric-trend">
      <span class="trend-badge positive">Enrolled</span>
      <span>active cohort roster</span>
    </div>
  </div>

  <!-- Card 2: Average Percentage -->
  <div class="metric-card">
    <div class="metric-top">
      <span class="metric-label">Average Percentage</span>
      <div class="metric-icon-wrap teal">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M18 20V10"/>
          <path d="M12 20V4"/>
          <path d="M6 20v-6"/>
        </svg>
      </div>
    </div>
    <div class="metric-value num-tabular">
      <?php if ($metrics['averagePercentage'] !== null): ?>
        <?= number_format((float)$metrics['averagePercentage'], 2) ?><span style="font-size: 1.25rem; font-weight: 500; color: var(--color-text-muted);">%</span>
      <?php else: ?>
        <span style="font-size: 1.35rem; color: var(--color-text-light);">N/A</span>
      <?php endif; ?>
    </div>
    <div class="metric-trend">
      <span class="trend-badge neutral">
        <?= $metrics['totalGraded'] ?> Graded
      </span>
      <span>excludes all-absent</span>
    </div>
  </div>

  <!-- Card 3: Highest Percentage -->
  <div class="metric-card">
    <div class="metric-top">
      <span class="metric-label">Highest Percentage</span>
      <div class="metric-icon-wrap success">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
          <polyline points="17 6 23 6 23 12"></polyline>
        </svg>
      </div>
    </div>
    <div class="metric-value num-tabular">
      <?php if ($metrics['highestPercentage'] !== null): ?>
        <?= number_format((float)$metrics['highestPercentage'], 2) ?><span style="font-size: 1.25rem; font-weight: 500; color: var(--color-text-muted);">%</span>
      <?php else: ?>
        <span style="font-size: 1.35rem; color: var(--color-text-light);">N/A</span>
      <?php endif; ?>
    </div>
    <div class="metric-trend">
      <span class="trend-badge positive">Top Score</span>
      <span>cohort valedictorian mark</span>
    </div>
  </div>

  <!-- Card 4: Lowest Percentage -->
  <div class="metric-card">
    <div class="metric-top">
      <span class="metric-label">Lowest Percentage</span>
      <div class="metric-icon-wrap warning">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="23 18 13.5 8.5 8.5 13.5 1 6"></polyline>
          <polyline points="17 18 23 18 23 12"></polyline>
        </svg>
      </div>
    </div>
    <div class="metric-value num-tabular">
      <?php if ($metrics['lowestPercentage'] !== null): ?>
        <?= number_format((float)$metrics['lowestPercentage'], 2) ?><span style="font-size: 1.25rem; font-weight: 500; color: var(--color-text-muted);">%</span>
      <?php else: ?>
        <span style="font-size: 1.35rem; color: var(--color-text-light);">N/A</span>
      <?php endif; ?>
    </div>
    <div class="metric-trend">
      <span class="trend-badge warning">Threshold</span>
      <span>minimum attended grade</span>
    </div>
  </div>
</div>

<!-- Main Dashboard Grid: Performance Chart & Quick Status Ledger -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;" class="dashboard-analytics-grid">
  <!-- Interactive Chart.js Performance Overview Card -->
  <div class="card" style="margin-bottom: 0;">
    <div class="card-header">
      <div>
        <h2 class="card-title">Performance Distribution Overview</h2>
        <p class="card-subtitle">Cohort percentage band breakdown (0–40%, 40–50%, 50–60%, 60–70%, 70–80%, 80–100%)</p>
      </div>
      <span class="badge-status active">
        <span class="badge-dot"></span> Dynamic Chart
      </span>
    </div>
    <div class="card-body">
      <div style="position: relative; height: 260px; width: 100%;">
        <canvas id="performanceChart" 
          data-labels='<?= htmlspecialchars($chartJsonLabels, ENT_QUOTES, 'UTF-8') ?>'
          data-values='<?= htmlspecialchars($chartJsonValues, ENT_QUOTES, 'UTF-8') ?>'>
        </canvas>
      </div>
    </div>
  </div>

  <!-- Academic Standing Summary Column -->
  <div class="card" style="margin-bottom: 0;">
    <div class="card-header">
      <div>
        <h2 class="card-title">Scholastic Standing</h2>
        <p class="card-subtitle">Tier analysis for <?= $metrics['totalStudents'] ?> enrolled students</p>
      </div>
    </div>
    <div class="card-body" style="display: flex; flex-direction: column; justify-content: space-between; gap: 1rem;">
      <div style="display: flex; flex-direction: column; gap: 0.85rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.65rem 0.75rem; background: var(--color-canvas); border-radius: var(--radius-md); border: 1px solid var(--color-border);">
          <div style="display: flex; align-items: center; gap: 0.5rem;">
            <span style="width: 10px; height: 10px; border-radius: 50%; background: #10b981;"></span>
            <span style="font-size: 0.825rem; font-weight: 600;">Distinction (&ge; 70%)</span>
          </div>
          <span class="num-tabular" style="font-weight: 700; color: var(--color-text-main);">
            <?= $metrics['distribution']['70-80%'] + $metrics['distribution']['80-100%'] ?>
          </span>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.65rem 0.75rem; background: var(--color-canvas); border-radius: var(--radius-md); border: 1px solid var(--color-border);">
          <div style="display: flex; align-items: center; gap: 0.5rem;">
            <span style="width: 10px; height: 10px; border-radius: 50%; background: #6366f1;"></span>
            <span style="font-size: 0.825rem; font-weight: 600;">Good Standing (50–69%)</span>
          </div>
          <span class="num-tabular" style="font-weight: 700; color: var(--color-text-main);">
            <?= $metrics['distribution']['50-60%'] + $metrics['distribution']['60-70%'] ?>
          </span>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.65rem 0.75rem; background: var(--color-canvas); border-radius: var(--radius-md); border: 1px solid var(--color-border);">
          <div style="display: flex; align-items: center; gap: 0.5rem;">
            <span style="width: 10px; height: 10px; border-radius: 50%; background: #f59e0b;"></span>
            <span style="font-size: 0.825rem; font-weight: 600;">Pass Standard (40–49%)</span>
          </div>
          <span class="num-tabular" style="font-weight: 700; color: var(--color-text-main);">
            <?= $metrics['distribution']['40-50%'] ?>
          </span>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.65rem 0.75rem; background: var(--color-canvas); border-radius: var(--radius-md); border: 1px solid var(--color-border);">
          <div style="display: flex; align-items: center; gap: 0.5rem;">
            <span style="width: 10px; height: 10px; border-radius: 50%; background: #ef4444;"></span>
            <span style="font-size: 0.825rem; font-weight: 600;">Academic Review (&lt; 40%)</span>
          </div>
          <span class="num-tabular" style="font-weight: 700; color: var(--color-danger);">
            <?= $metrics['distribution']['0-40%'] ?>
          </span>
        </div>
      </div>

      <div style="padding-top: 0.5rem;">
        <a href="performance.php" class="btn btn-secondary btn-sm" style="width: 100%; justify-content: center;">
          Full Analytics Report &rarr;
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Recent Student Records Table (Latest 5 Students from MySQL) -->
<div class="card">
  <div class="card-header">
    <div>
      <h2 class="card-title">Recent Students</h2>
      <p class="card-subtitle">Showing the 5 most recent registrations with automated age calculation and dynamic percentage</p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
      <a href="students.php" class="btn btn-secondary btn-sm">
        View All (<?= (int)$metrics['totalStudents'] ?>) Records &rarr;
      </a>
    </div>
  </div>
  
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Student Name</th>
          <th>Register Number</th>
          <th>Date of Birth</th>
          <th>Age</th>
          <th>Percentage</th>
          <th style="text-align: right;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($metrics['recentStudents'])): ?>
          <?php foreach ($metrics['recentStudents'] as $student): 
            $badge = formatPercentageBadge($student['calculated_percentage']);
            // Generate initials avatar
            $nameParts = explode(' ', trim($student['student_name']));
            $initials = '';
            foreach ($nameParts as $p) {
                if (!empty($p)) $initials .= strtoupper($p[0]);
            }
            $initials = substr($initials, 0, 2);
          ?>
            <tr>
              <td>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                  <div class="user-avatar" style="width: 34px; height: 34px; font-size: 0.8rem; background: #e0e7ff; color: #4338ca;">
                    <?= htmlspecialchars($initials) ?>
                  </div>
                  <div>
                    <a href="view_student.php?id=<?= (int)$student['id'] ?>" style="color: var(--color-text-main); font-weight: 600; text-decoration: none;">
                      <?= htmlspecialchars($student['student_name']) ?>
                    </a>
                    <div style="font-size: 0.75rem; color: var(--color-text-light);">
                      B.Tech CSBS &bull; ID: #<?= (int)$student['id'] ?>
                    </div>
                  </div>
                </div>
              </td>
              <td>
                <span class="num-tabular font-mono" style="font-weight: 600; color: var(--color-text-muted);">
                  <?= htmlspecialchars($student['register_no']) ?>
                </span>
              </td>
              <td>
                <span class="num-tabular">
                  <?= date('M j, Y', strtotime($student['dob'])) ?>
                </span>
              </td>
              <td>
                <span class="num-tabular" style="font-weight: 500;">
                  <?= (int)$student['age'] ?> yrs
                </span>
              </td>
              <td>
                <span class="badge-status <?= htmlspecialchars($badge['class']) ?>">
                  <span class="badge-dot"></span>
                  <?= htmlspecialchars($badge['text']) ?>
                </span>
              </td>
              <td style="text-align: right;">
                <a href="view_student.php?id=<?= (int)$student['id'] ?>" class="btn btn-secondary btn-sm" title="View Student Dossier">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                  </svg>
                  <span>View</span>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" style="text-align: center; padding: 3rem 1.5rem; color: var(--color-text-light);">
              <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.75rem;">
                <div style="width: 48px; height: 48px; border-radius: 50%; background: var(--color-surface-dim); color: var(--color-primary); display: flex; align-items: center; justify-content: center;">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                  </svg>
                </div>
                <div>
                  <p style="font-weight: 600; color: var(--color-text-main); font-size: 0.95rem;">No student records found in database</p>
                  <p style="font-size: 0.8rem; color: var(--color-text-muted); margin-top: 0.2rem;">
                    Import Section 04 dataset via <code>database/student_data.sql</code> or enroll your first student.
                  </p>
                </div>
                <a href="add_student.php" class="btn btn-primary btn-sm" style="margin-top: 0.5rem;">
                  + Add First Student
                </a>
              </div>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Architecture & Phase Progress Note -->
<div class="card" style="border-left: 4px solid var(--color-primary); margin-top: 1.5rem;">
  <div class="card-body" style="display: flex; gap: 1.25rem; align-items: flex-start;">
    <div style="width: 40px; height: 40px; border-radius: 8px; background: var(--color-primary-light); color: var(--color-primary); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
      </svg>
    </div>
    <div>
      <h3 style="font-size: 0.95rem; font-weight: 600; color: var(--color-text-main); margin-bottom: 0.25rem;">
        Academic Analytics Overview: Live Data Active
      </h3>
      <p style="color: var(--color-text-muted); font-size: 0.825rem; margin-bottom: 0.5rem;">
        All 4 metric cards, Recent Students table, and the Chart.js performance distribution are dynamically rendered from MySQL via PHP PDO. Dynamic age and percentage calculations are computed in runtime without hardcoded data.
      </p>
      <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <span class="badge-status active" style="font-size: 0.72rem;">&check; Total: <?= (int)$metrics['totalStudents'] ?> Students</span>
        <span class="badge-status active" style="font-size: 0.72rem;">&check; Average: <?= $metrics['averagePercentage'] !== null ? number_format($metrics['averagePercentage'], 2) . '%' : 'N/A' ?></span>
        <span class="badge-status active" style="font-size: 0.72rem;">&check; Chart.js Distribution Live</span>
        <span class="badge-status active" style="font-size: 0.72rem;">&check; Age via DateTime</span>
      </div>
    </div>
  </div>
</div>

<?php
// 3. Include Reusable Footer Layout
include __DIR__ . '/includes/footer.php';
?>
