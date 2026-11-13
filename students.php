<?php
// students.php
// Academic Operations System - Master Student Directory
// Section 06: Student Management Page (Google Stitch UI Design & Dynamic MySQL PDO Integration)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Student Management';
$currentPage = 'students';

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// 1. Include Centralized Database Connection & Helper Functions
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$csrfToken = generateCsrfToken();

// Safe extraction and sanitization of query parameters
$queryParams = [
    'search'    => trim($_GET['search'] ?? ''),
    'pct_range' => trim($_GET['pct_range'] ?? ''),
    'age'       => trim($_GET['age'] ?? ''),
    'sort'      => trim($_GET['sort'] ?? 'id_desc'),
    'page'      => max(1, (int)($_GET['page'] ?? 1)),
    'limit'     => in_array((int)($_GET['limit'] ?? 10), [10, 20, 50]) ? (int)$_GET['limit'] : 10,
];

// Fallback state variables
$summary = [
    'totalStudents'     => 0,
    'averagePercentage' => null,
    'highestPercentage' => null,
    'lowestPercentage'  => null,
    'totalGraded'       => 0,
];

$paginationData = [
    'students'      => [],
    'totalRecords'  => 0,
    'currentPage'   => 1,
    'totalPages'    => 1,
    'limit'         => 10,
    'offset'        => 0,
    'search'        => '',
    'pct_range'     => '',
    'age'           => '',
    'sort'          => 'id_desc',
];

$dbErrorNotice = null;

if ($pdo instanceof PDO) {
    try {
        // Fetch cohort aggregate statistics for the 4 summary cards
        $summary = getDashboardMetrics($pdo);
        
        // Fetch filtered, sorted, and paginated records
        $paginationData = getPaginatedStudents($pdo, $queryParams);
    } catch (PDOException $e) {
        error_log("Students page database error: " . $e->getMessage());
        $dbErrorNotice = "Unable to load student records from MySQL. Please check the database connection and schema.";
    }
} else {
    $dbErrorNotice = $dbConnectionError ?? "Database connection offline. Please check XAMPP MySQL.";
}

// Helper to build preserved query strings for pagination and sorting
function buildFilterUrl(array $overrides = []) {
    global $queryParams;
    $merged = array_merge($queryParams, $overrides);
    // Remove empty parameters for cleaner URLs
    $cleaned = array_filter($merged, function ($val) {
        return $val !== '' && $val !== null;
    });
    return 'students.php?' . http_build_query($cleaned);
}

// 2. Include Reusable Header Layout
include __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumbs Navigation -->
<nav class="breadcrumb-nav" aria-label="Breadcrumb" style="margin-bottom: 0.75rem;">
  <ol style="display: flex; align-items: center; gap: 0.5rem; list-style: none; padding: 0; font-size: 0.8rem; color: var(--color-text-light);">
    <li>
      <a href="dashboard.php" style="color: var(--color-text-muted); text-decoration: none; font-weight: 500;">Dashboard</a>
    </li>
    <li style="color: var(--color-border);">&rsaquo;</li>
    <li style="color: var(--color-primary); font-weight: 600;" aria-current="page">Students</li>
  </ol>
</nav>

<!-- Page Header with Primary Action -->
<div class="page-header" style="margin-bottom: 1.5rem;">
  <div>
    <h1 class="page-title">Student Management</h1>
    <p class="page-subtitle">Centralized registrar roster, academic progress audit, demographic ledger, and records management.</p>
  </div>
  <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
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

<?php if ($flashSuccess): ?>
  <!-- Flash Success Notification -->
  <div class="card" style="border-left: 4px solid var(--color-success); margin-bottom: 1.5rem; background: #f0fdf4;">
    <div class="card-body" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.25rem;">
      <div style="display: flex; align-items: center; gap: 0.75rem;">
        <span style="color: var(--color-success); font-size: 1.25rem;">&#10004;</span>
        <span style="font-size: 0.875rem; color: #166534; font-weight: 600;">
          <?= htmlspecialchars($flashSuccess) ?>
        </span>
      </div>
      <a href="students.php" class="action-btn" aria-label="Dismiss">&times;</a>
    </div>
  </div>
<?php endif; ?>

<?php if ($flashError): ?>
  <!-- Flash Error Notification -->
  <div class="card" style="border-left: 4px solid var(--color-danger); margin-bottom: 1.5rem; background: #fff1f2;">
    <div class="card-body" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.25rem;">
      <div style="display: flex; align-items: center; gap: 0.75rem;">
        <span style="color: var(--color-danger); font-size: 1.25rem;">&#9888;</span>
        <span style="font-size: 0.875rem; color: #9f1239; font-weight: 600;">
          <?= htmlspecialchars($flashError) ?>
        </span>
      </div>
      <a href="students.php" class="action-btn" aria-label="Dismiss">&times;</a>
    </div>
  </div>
<?php endif; ?>

<?php if ($dbErrorNotice): ?>
  <!-- Error Notification -->
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

<!-- 4 Summary Cards (Dynamic MySQL Aggregates) -->
<div class="metrics-grid" style="margin-bottom: 1.5rem;">
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
      <?= (int)$summary['totalStudents'] ?>
    </div>
    <div class="metric-trend">
      <span class="trend-badge positive">Enrolled</span>
      <span>cohort registry size</span>
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
      <?php if ($summary['averagePercentage'] !== null): ?>
        <?= number_format((float)$summary['averagePercentage'], 2) ?><span style="font-size: 1.25rem; font-weight: 500; color: var(--color-text-muted);">%</span>
      <?php else: ?>
        <span style="font-size: 1.35rem; color: var(--color-text-light);">N/A</span>
      <?php endif; ?>
    </div>
    <div class="metric-trend">
      <span class="trend-badge neutral">
        <?= $summary['totalGraded'] ?> Graded
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
      <?php if ($summary['highestPercentage'] !== null): ?>
        <?= number_format((float)$summary['highestPercentage'], 2) ?><span style="font-size: 1.25rem; font-weight: 500; color: var(--color-text-muted);">%</span>
      <?php else: ?>
        <span style="font-size: 1.35rem; color: var(--color-text-light);">N/A</span>
      <?php endif; ?>
    </div>
    <div class="metric-trend">
      <span class="trend-badge positive">Valedictorian</span>
      <span>cohort high mark</span>
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
      <?php if ($summary['lowestPercentage'] !== null): ?>
        <?= number_format((float)$summary['lowestPercentage'], 2) ?><span style="font-size: 1.25rem; font-weight: 500; color: var(--color-text-muted);">%</span>
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

<!-- Directory Master Card -->
<div class="card">
  <!-- Search & Filter Controls Toolbar -->
  <div class="card-header" style="flex-direction: column; align-items: stretch; gap: 1rem; background: #fafbfd;">
    <form method="GET" action="students.php" id="studentFilterForm" style="display: flex; flex-direction: column; gap: 1rem; width: 100%;">
      <!-- Top Row: Full-width search bar -->
      <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
        <div class="search-box" style="flex: 1; min-width: 280px;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          <input 
            type="text" 
            name="search" 
            value="<?= htmlspecialchars($queryParams['search']) ?>" 
            placeholder="Search by student name, register number, or mobile number..."
            autocomplete="off"
          >
        </div>
        
        <button type="submit" class="btn btn-primary" style="height: 38px;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          Search
        </button>

        <?php if (!empty($queryParams['search']) || !empty($queryParams['pct_range']) || !empty($queryParams['age']) || $queryParams['sort'] !== 'id_desc'): ?>
          <a href="students.php" class="btn btn-secondary" style="height: 38px;" title="Clear all active filters">
            &times; Reset Filters
          </a>
        <?php endif; ?>
      </div>

      <!-- Bottom Row: Filter Selects & Sorting Options -->
      <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center; justify-content: space-between;">
        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
          <!-- Percentage Range Filter -->
          <div style="display: flex; align-items: center; gap: 0.4rem;">
            <label for="pctRangeFilter" style="font-size: 0.75rem; font-weight: 600; color: var(--color-text-muted); text-transform: uppercase;">
              Percentage:
            </label>
            <select name="pct_range" id="pctRangeFilter" class="form-control" onchange="this.form.submit()" style="height: 36px; font-size: 0.8rem; width: 175px;">
              <option value="" <?= ($queryParams['pct_range'] === '') ? 'selected' : '' ?>>All Percentages</option>
              <option value="distinction" <?= ($queryParams['pct_range'] === 'distinction') ? 'selected' : '' ?>>Distinction (70%+)</option>
              <option value="first_class" <?= ($queryParams['pct_range'] === 'first_class') ? 'selected' : '' ?>>First Class (60–69%)</option>
              <option value="second_class" <?= ($queryParams['pct_range'] === 'second_class') ? 'selected' : '' ?>>Second Class (50–59%)</option>
              <option value="pass" <?= ($queryParams['pct_range'] === 'pass') ? 'selected' : '' ?>>Pass Class (40–49%)</option>
              <option value="below_40" <?= ($queryParams['pct_range'] === 'below_40') ? 'selected' : '' ?>>Academic Review (&lt; 40%)</option>
              <option value="absent" <?= ($queryParams['pct_range'] === 'absent') ? 'selected' : '' ?>>Absent</option>
              <option value="missing" <?= ($queryParams['pct_range'] === 'missing') ? 'selected' : '' ?>>Not Available</option>
            </select>
          </div>

          <!-- Age Filter -->
          <div style="display: flex; align-items: center; gap: 0.4rem;">
            <label for="ageFilter" style="font-size: 0.75rem; font-weight: 600; color: var(--color-text-muted); text-transform: uppercase;">
              Age:
            </label>
            <select name="age" id="ageFilter" class="form-control" onchange="this.form.submit()" style="height: 36px; font-size: 0.8rem; width: 140px;">
              <option value="" <?= ($queryParams['age'] === '') ? 'selected' : '' ?>>All Ages</option>
              <option value="under_18" <?= ($queryParams['age'] === 'under_18') ? 'selected' : '' ?>>Under 18 yrs</option>
              <option value="18" <?= ($queryParams['age'] === '18') ? 'selected' : '' ?>>18 years</option>
              <option value="19" <?= ($queryParams['age'] === '19') ? 'selected' : '' ?>>19 years</option>
              <option value="20_plus" <?= ($queryParams['age'] === '20_plus') ? 'selected' : '' ?>>20+ years</option>
            </select>
          </div>

          <!-- Sorting Filter -->
          <div style="display: flex; align-items: center; gap: 0.4rem;">
            <label for="sortFilter" style="font-size: 0.75rem; font-weight: 600; color: var(--color-text-muted); text-transform: uppercase;">
              Sort By:
            </label>
            <select name="sort" id="sortFilter" class="form-control" onchange="this.form.submit()" style="height: 36px; font-size: 0.8rem; width: 180px;">
              <option value="id_desc" <?= ($queryParams['sort'] === 'id_desc') ? 'selected' : '' ?>>Newest Enrolled First</option>
              <option value="name_asc" <?= ($queryParams['sort'] === 'name_asc') ? 'selected' : '' ?>>Student Name (A–Z)</option>
              <option value="name_desc" <?= ($queryParams['sort'] === 'name_desc') ? 'selected' : '' ?>>Student Name (Z–A)</option>
              <option value="pct_desc" <?= ($queryParams['sort'] === 'pct_desc') ? 'selected' : '' ?>>Percentage (High to Low)</option>
              <option value="pct_asc" <?= ($queryParams['sort'] === 'pct_asc') ? 'selected' : '' ?>>Percentage (Low to High)</option>
              <option value="reg_asc" <?= ($queryParams['sort'] === 'reg_asc') ? 'selected' : '' ?>>Register No (Ascending)</option>
              <option value="reg_desc" <?= ($queryParams['sort'] === 'reg_desc') ? 'selected' : '' ?>>Register No (Descending)</option>
            </select>
          </div>
        </div>

        <!-- Records Per Page Selector -->
        <div style="display: flex; align-items: center; gap: 0.4rem;">
          <label for="limitSelector" style="font-size: 0.75rem; font-weight: 600; color: var(--color-text-muted); text-transform: uppercase;">
            Per Page:
          </label>
          <select name="limit" id="limitSelector" class="form-control" onchange="this.form.submit()" style="height: 36px; font-size: 0.8rem; width: 85px;">
            <option value="10" <?= ($queryParams['limit'] === 10) ? 'selected' : '' ?>>10</option>
            <option value="20" <?= ($queryParams['limit'] === 20) ? 'selected' : '' ?>>20</option>
            <option value="50" <?= ($queryParams['limit'] === 50) ? 'selected' : '' ?>>50</option>
          </select>
        </div>
      </div>
    </form>
  </div>

  <!-- High-Contrast CRUD Table -->
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Student Name</th>
          <th>Register Number</th>
          <th>Mobile Number</th>
          <th>Date of Birth</th>
          <th>Age</th>
          <th>Percentage</th>
          <th style="text-align: right;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($paginationData['students'])): ?>
          <?php foreach ($paginationData['students'] as $student): 
            $badge = formatPercentageBadge($student['calculated_percentage']);
            $initials = getStudentInitials($student['student_name']);
          ?>
            <tr>
              <!-- 1. Student Name & Avatar -->
              <td>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                  <div class="user-avatar" style="width: 36px; height: 36px; font-size: 0.85rem; background: #e0e7ff; color: #4338ca; flex-shrink: 0;">
                    <?= htmlspecialchars($initials) ?>
                  </div>
                  <div>
                    <a href="view_student.php?id=<?= (int)$student['id'] ?>" style="color: var(--color-text-main); font-weight: 600; text-decoration: none;" class="hover-underline">
                      <?= htmlspecialchars($student['student_name']) ?>
                    </a>
                    <div style="font-size: 0.75rem; color: var(--color-text-light);">
                      B.Tech CSBS &bull; Record #<?= (int)$student['id'] ?>
                    </div>
                  </div>
                </div>
              </td>

              <!-- 2. Register Number -->
              <td>
                <span class="num-tabular font-mono" style="font-weight: 600; color: var(--color-text-muted);">
                  <?= htmlspecialchars($student['register_no']) ?>
                </span>
              </td>

              <!-- 3. Mobile Number -->
              <td>
                <span class="num-tabular font-mono" style="color: var(--color-text-main);">
                  <?= htmlspecialchars($student['mobile_number'] ?? 'N/A') ?>
                </span>
              </td>

              <!-- 4. Date of Birth -->
              <td>
                <span class="num-tabular">
                  <?= date('M j, Y', strtotime($student['dob'])) ?>
                </span>
              </td>

              <!-- 5. Dynamic Age (Calculated from DOB via DateTime) -->
              <td>
                <span class="num-tabular font-mono" style="font-weight: 500;">
                  <?= (int)$student['age'] ?> yrs
                </span>
              </td>

              <!-- 6. Percentage with Badge & Mini Progress Bar -->
              <td>
                <div style="display: flex; flex-direction: column; gap: 0.35rem; min-width: 110px;">
                  <span class="badge-status <?= htmlspecialchars($badge['class']) ?>">
                    <span class="badge-dot"></span>
                    <?= htmlspecialchars($badge['text']) ?>
                  </span>
                  <?php if ($badge['numeric'] !== null): ?>
                    <div style="width: 100%; height: 4px; background: #f1f5f9; border-radius: 9999px; overflow: hidden;">
                      <div style="width: <?= min(100, max(0, $badge['numeric'])) ?>%; height: 100%; background: <?= ($badge['numeric'] >= 70) ? '#10b981' : (($badge['numeric'] >= 50) ? '#6366f1' : (($badge['numeric'] >= 40) ? '#f59e0b' : '#ef4444')) ?>;"></div>
                    </div>
                  <?php endif; ?>
                </div>
              </td>

              <!-- 7. Row Actions (View, Edit, Delete Modal) -->
              <td style="text-align: right;">
                <div style="display: flex; gap: 0.35rem; justify-content: flex-end;">
                  <!-- View Action -->
                  <a href="view_student.php?id=<?= (int)$student['id'] ?>" class="btn btn-secondary btn-sm" title="View Student Dossier">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                      <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    <span>View</span>
                  </a>

                  <!-- Edit Action -->
                  <a href="edit_student.php?id=<?= (int)$student['id'] ?>" class="btn btn-secondary btn-sm" title="Edit Student Record">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                    <span>Edit</span>
                  </a>

                  <!-- Delete Trigger Modal Button -->
                  <button 
                    type="button" 
                    class="btn btn-secondary btn-sm js-delete-trigger" 
                    style="color: var(--color-danger); border-color: #fee2e2;" 
                    data-id="<?= (int)$student['id'] ?>"
                    data-name="<?= htmlspecialchars($student['student_name'], ENT_QUOTES, 'UTF-8') ?>"
                    data-reg="<?= htmlspecialchars($student['register_no'], ENT_QUOTES, 'UTF-8') ?>"
                    title="Delete Student Record"
                  >
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <polyline points="3 6 5 6 21 6"></polyline>
                      <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                    <span>Delete</span>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <!-- No Records Found State -->
          <tr>
            <td colspan="7" style="text-align: center; padding: 4rem 1.5rem; color: var(--color-text-light);">
              <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.75rem; max-width: 440px; margin: 0 auto;">
                <div style="width: 52px; height: 52px; border-radius: 50%; background: var(--color-surface-dim); color: var(--color-primary); display: flex; align-items: center; justify-content: center;">
                  <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                  </svg>
                </div>
                <div>
                  <h3 style="font-weight: 700; color: var(--color-text-main); font-size: 1.05rem; margin-bottom: 0.25rem;">
                    No Students Matched Your Criteria
                  </h3>
                  <p style="font-size: 0.85rem; color: var(--color-text-muted); line-height: 1.5;">
                    <?php if (!empty($queryParams['search']) || !empty($queryParams['pct_range']) || !empty($queryParams['age'])): ?>
                      No records matched the search keyword <code><?= htmlspecialchars($queryParams['search']) ?></code> or active filters. Try resetting the filters.
                    <?php else: ?>
                      There are currently no students in the database. You can import Section 04 dataset via phpMyAdmin or enroll a new student.
                    <?php endif; ?>
                  </p>
                </div>
                <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                  <a href="students.php" class="btn btn-secondary btn-sm">Reset All Filters</a>
                  <a href="add_student.php" class="btn btn-primary btn-sm">+ Enroll New Student</a>
                </div>
              </div>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination Footer Bar -->
  <div class="card-footer" style="padding: 1rem 1.5rem; background: #fafbfd; border-top: 1px solid var(--color-border); display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 1rem;">
    <!-- Record Range & Current Page Indicator -->
    <div style="font-size: 0.85rem; color: var(--color-text-muted);">
      <?php if ($paginationData['totalRecords'] > 0): 
        $startRecord = $paginationData['offset'] + 1;
        $endRecord = min($paginationData['totalRecords'], $paginationData['offset'] + $paginationData['limit']);
      ?>
        Showing <strong class="num-tabular" style="color: var(--color-text-main);"><?= $startRecord ?></strong> to 
        <strong class="num-tabular" style="color: var(--color-text-main);"><?= $endRecord ?></strong> of 
        <strong class="num-tabular" style="color: var(--color-text-main);"><?= $paginationData['totalRecords'] ?></strong> records &bull; 
        Page <span class="num-tabular" style="font-weight: 600;"><?= $paginationData['currentPage'] ?></span> of <span class="num-tabular" style="font-weight: 600;"><?= $paginationData['totalPages'] ?></span>
      <?php else: ?>
        Showing <strong>0</strong> records
      <?php endif; ?>
    </div>

    <!-- Pagination Controls (Previous, Numbers, Next) -->
    <?php if ($paginationData['totalPages'] > 1): ?>
      <div style="display: flex; align-items: center; gap: 0.35rem;" class="pagination-nav">
        <!-- Previous Button -->
        <?php if ($paginationData['currentPage'] > 1): ?>
          <a href="<?= buildFilterUrl(['page' => $paginationData['currentPage'] - 1]) ?>" class="btn btn-secondary btn-sm" aria-label="Previous Page">
            &larr; Prev
          </a>
        <?php else: ?>
          <button class="btn btn-secondary btn-sm" disabled style="opacity: 0.5; cursor: not-allowed;">
            &larr; Prev
          </button>
        <?php endif; ?>

        <!-- Page Number Links -->
        <?php for ($p = 1; $p <= $paginationData['totalPages']; $p++): ?>
          <?php if ($p === $paginationData['currentPage']): ?>
            <span class="btn btn-primary btn-sm num-tabular" style="min-width: 34px; justify-content: center; pointer-events: none;">
              <?= $p ?>
            </span>
          <?php else: ?>
            <a href="<?= buildFilterUrl(['page' => $p]) ?>" class="btn btn-secondary btn-sm num-tabular" style="min-width: 34px; justify-content: center;">
              <?= $p ?>
            </a>
          <?php endif; ?>
        <?php endfor; ?>

        <!-- Next Button -->
        <?php if ($paginationData['currentPage'] < $paginationData['totalPages']): ?>
          <a href="<?= buildFilterUrl(['page' => $paginationData['currentPage'] + 1]) ?>" class="btn btn-secondary btn-sm" aria-label="Next Page">
            Next &rarr;
          </a>
        <?php else: ?>
          <button class="btn btn-secondary btn-sm" disabled style="opacity: 0.5; cursor: not-allowed;">
            Next &rarr;
          </button>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Modal Sheet for Delete Confirmation (Level 3 Elevation) -->
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

<?php
// 3. Include Reusable Footer Layout
include __DIR__ . '/includes/footer.php';
?>