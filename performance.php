<?php
// performance.php
// Academic Operations System - Student Performance & GPA Analytics Dashboard
// Section 09: Student Performance Dashboard (Google Stitch UI Design & Dynamic MySQL PDO Integration)

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = 'Student Performance';
$currentPage = 'performance';

// 1. Sanitize query parameters for search, category filter, and sorting
$queryParams = [
    'search'   => trim($_GET['search'] ?? ''),
    'category' => trim($_GET['category'] ?? 'all'),
    'sort'     => trim($_GET['sort'] ?? 'pct_desc'),
];

// Helper to construct filter URLs
function buildPerfFilterUrl($params = []) {
    global $queryParams;
    $merged = array_merge($queryParams, $params);
    $filtered = array_filter($merged, function ($val) {
        return $val !== '' && $val !== 'all' && $val !== 'pct_desc';
    });
    return 'performance.php' . (!empty($filtered) ? '?' . http_build_query($filtered) : '');
}

$isDbConnected = (isset($pdo) && $pdo instanceof PDO);
$dbErrorNotice = !$isDbConnected ? ($dbConnectionError ?? 'Database connection offline. Please check XAMPP MySQL.') : null;

// 2. CSV Export Handler
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    if (!$isDbConnected) {
        $_SESSION['flash_error'] = 'Cannot export CSV while the database connection is offline.';
        header('Location: performance.php');
        exit;
    }

    $exportRecords = getStudentRankingList($pdo, $queryParams);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="student_performance_report_' . date('Ymd_His') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    // Output UTF-8 BOM for Microsoft Excel compatibility
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    // CSV Header row
    fputcsv($output, [
        'Rank',
        'Student Name',
        'Register Number',
        'Total Obtained Marks',
        'Applicable Maximum Marks',
        'Percentage',
        'Performance Status'
    ]);

    foreach ($exportRecords as $row) {
        $pctText = ($row['calculated_percentage'] === 'Absent') 
            ? 'Absent' 
            : (($row['calculated_percentage'] !== null) ? number_format((float)$row['calculated_percentage'], 2) . '%' : 'Not Available');

        fputcsv($output, [
            $row['rank'],
            sanitizeCsvCell($row['student_name']),
            sanitizeCsvCell($row['register_no']),
            number_format((float)$row['total_obtained'], 2),
            (int)$row['attended_maximum_marks'],
            $pctText,
            sanitizeCsvCell($row['category']['label'])
        ]);
    }

    fclose($output);
    exit;
}

// 3. Fetch summary metrics and performance ranking data
$summary = [
    'totalStudents'     => 0,
    'averagePercentage' => null,
    'highestPercentage' => null,
    'lowestPercentage'  => null,
    'highestStudent'    => null,
    'lowestStudent'     => null,
    'absentCount'       => 0,
    'validCount'        => 0,
    'distribution'      => []
];
$rankedStudents = [];
$topStudents = [];
$subjectAverages = [];

if ($isDbConnected) {
    try {
        $summary = getDashboardMetrics($pdo);
        $rankedStudents = getStudentRankingList($pdo, $queryParams);

        // 4. Compute Top 5 Students for Chart 2
        $gradedAll = getStudentRankingList($pdo, ['sort' => 'pct_desc']);
        foreach ($gradedAll as $s) {
            if ($s['numeric_pct'] >= 0 && count($topStudents) < 5) {
                $topStudents[] = [
                    'name' => $s['student_name'],
                    'pct'  => round((float)$s['numeric_pct'], 2)
                ];
            }
        }

        // 5. Compute Subject-wise average marks for Chart 3
        $subjectAverages = getSubjectWisePerformanceAverages($pdo);
    } catch (PDOException $e) {
        error_log("Performance analytics error: " . $e->getMessage());
        $dbErrorNotice = "Unable to load live performance analytics from MySQL. Please verify database connectivity.";
    }
}

// Encode dataset payloads for Chart.js
$distributionJson = htmlspecialchars(json_encode([
    'labels' => ['0–40%', '40–50%', '50–60%', '60–70%', '70–80%', '80–100%'],
    'values' => [
        $summary['distribution']['0-40%'] ?? 0,
        $summary['distribution']['40-50%'] ?? 0,
        $summary['distribution']['50-60%'] ?? 0,
        $summary['distribution']['60-70%'] ?? 0,
        $summary['distribution']['70-80%'] ?? 0,
        $summary['distribution']['80-100%'] ?? 0,
    ]
]), ENT_QUOTES, 'UTF-8');

$topStudentsJson = htmlspecialchars(json_encode([
    'labels' => array_column($topStudents, 'name'),
    'values' => array_column($topStudents, 'pct')
]), ENT_QUOTES, 'UTF-8');

$subjectAveragesJson = htmlspecialchars(json_encode([
    'labels' => array_map(function($sub) {
        // Shorten long subject titles for clean chart axes
        $words = explode(' ', $sub['subject_name']);
        return count($words) > 3 ? implode(' ', array_slice($words, 0, 3)) . '...' : $sub['subject_name'];
    }, $subjectAverages),
    'fullLabels' => array_column($subjectAverages, 'subject_name'),
    'values' => array_column($subjectAverages, 'average_marks')
]), ENT_QUOTES, 'UTF-8');

include __DIR__ . '/includes/header.php';
?>

  <!-- Top Navigation & Breadcrumbs -->
  <div class="page-header">
    <div class="breadcrumb">
      <a href="dashboard.php">Dashboard</a>
      <span class="breadcrumb-separator">/</span>
      <span class="breadcrumb-current">Student Performance</span>
    </div>

    <div class="header-action-row">
      <div>
        <h1 class="page-title">Student Performance Analytics</h1>
        <p class="page-subtitle">Scholastic ranking ledger, competency band distribution, and curriculum course benchmarks.</p>
      </div>

      <div class="header-btn-group no-print">
        <!-- Export to CSV -->
        <a 
          href="performance.php?export=csv<?= !empty($queryParams['search']) ? '&search=' . urlencode($queryParams['search']) : '' ?><?= !empty($queryParams['category']) && $queryParams['category'] !== 'all' ? '&category=' . urlencode($queryParams['category']) : '' ?><?= !empty($queryParams['sort']) ? '&sort=' . urlencode($queryParams['sort']) : '' ?>" 
          class="btn btn-outline"
          title="Download current filtered table as CSV"
        >
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="7 10 12 15 17 10"></polyline>
            <line x1="12" y1="15" x2="12" y2="3"></line>
          </svg>
          Export CSV
        </a>

        <!-- Print Performance Report -->
        <button type="button" onclick="window.print()" class="btn btn-secondary" title="Print Academic Performance Report">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="6 9 6 2 18 2 18 9"></polyline>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
            <rect x="6" y="14" width="12" height="8"></rect>
          </svg>
          Print Report
        </button>

        <a href="students.php" class="btn btn-primary">
          Manage Students
        </a>
      </div>
    </div>
  </div>

  <?php if (!empty($dbErrorNotice)): ?>
    <div class="form-alert alert-danger">
      <span class="alert-icon">&excl;</span>
      <div>
        <strong>Database Connection Offline:</strong> <?= htmlspecialchars($dbErrorNotice) ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- 1. Performance Summary Cards (Excludes All-Absent Students from Percentage) -->
  <div class="metrics-grid" style="margin-bottom: 1.5rem;">
    <!-- Card 1: Total Students -->
    <div class="metric-card">
      <div class="metric-top">
        <span class="metric-label">Total Cohort</span>
        <div class="metric-icon-wrap">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
          </svg>
        </div>
      </div>
      <div class="metric-value num-tabular"><?= (int)$summary['totalStudents'] ?></div>
      <div class="metric-trend">
        <span class="trend-badge neutral"><?= (int)$summary['totalGraded'] ?> Evaluated</span>
        <span>enrolled students</span>
      </div>
    </div>

    <!-- Card 2: Average Percentage -->
    <div class="metric-card">
      <div class="metric-top">
        <span class="metric-label">Average Percentage</span>
        <div class="metric-icon-wrap teal">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="20" x2="18" y2="10"></line>
            <line x1="12" y1="20" x2="12" y2="4"></line>
            <line x1="6" y1="20" x2="6" y2="14"></line>
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
        <span class="trend-badge positive">Excludes Absent</span>
        <span>cohort academic mean</span>
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

  <!-- 2. Three Interactive Chart.js Performance Charts -->
  <div class="charts-three-grid no-print" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 300px), 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
    <!-- Chart 1: Percentage Distribution -->
    <div class="content-card">
      <div class="card-header-flex">
        <div>
          <h3 class="card-title">Score Distribution</h3>
          <p class="card-subtitle">Cohort students grouped by grade tiers</p>
        </div>
        <span class="badge badge-neutral">Band Analysis</span>
      </div>
      <div class="chart-container" style="position: relative; height: 260px; width: 100%;">
        <canvas id="performanceDistributionChart" data-chart="<?= $distributionJson ?>"></canvas>
      </div>
    </div>

    <!-- Chart 2: Top 5 Students -->
    <div class="content-card">
      <div class="card-header-flex">
        <div>
          <h3 class="card-title">Top 5 Academic Leaders</h3>
          <p class="card-subtitle">Highest recorded cohort percentages</p>
        </div>
        <span class="badge badge-success">Honor Roll</span>
      </div>
      <div class="chart-container" style="position: relative; height: 260px; width: 100%;">
        <canvas id="topStudentsChart" data-chart="<?= $topStudentsJson ?>"></canvas>
      </div>
    </div>

    <!-- Chart 3: Subject-wise Averages -->
    <div class="content-card">
      <div class="card-header-flex">
        <div>
          <h3 class="card-title">Subject-wise Averages</h3>
          <p class="card-subtitle">Average marks across 7 registered courses</p>
        </div>
        <span class="badge badge-info">Curriculum</span>
      </div>
      <div class="chart-container" style="position: relative; height: 260px; width: 100%;">
        <canvas id="subjectAveragesChart" data-chart="<?= $subjectAveragesJson ?>"></canvas>
      </div>
    </div>
  </div>

  <!-- 3. Search and Category Filter Toolbar -->
  <div class="content-card no-print" style="margin-bottom: 1.5rem; padding: 1.25rem 1.5rem;">
    <form method="GET" action="performance.php" id="perfFilterForm" style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end;">
      <!-- Search Input -->
      <div style="flex: 2; min-width: 220px;">
        <label for="search" class="form-label" style="font-size: 0.75rem; text-transform: uppercase; color: var(--color-text-muted); font-weight: 700;">
          Search Student
        </label>
        <div style="position: relative;">
          <input
            type="text"
            id="search"
            name="search"
            class="form-control"
            placeholder="Search by name or register number..."
            value="<?= htmlspecialchars($queryParams['search']) ?>"
            style="padding-left: 2.25rem;"
          >
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--color-text-light);">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
        </div>
      </div>

      <!-- Performance Category Filter -->
      <div style="flex: 1.5; min-width: 180px;">
        <label for="category" class="form-label" style="font-size: 0.75rem; text-transform: uppercase; color: var(--color-text-muted); font-weight: 700;">
          Performance Category
        </label>
        <select name="category" id="category" class="form-control" onchange="this.form.submit()">
          <option value="all" <?= $queryParams['category'] === 'all' ? 'selected' : '' ?>>All Categories</option>
          <option value="excellent" <?= $queryParams['category'] === 'excellent' ? 'selected' : '' ?>>80–100%: Excellent</option>
          <option value="very_good" <?= $queryParams['category'] === 'very_good' ? 'selected' : '' ?>>70–79.99%: Very Good</option>
          <option value="good" <?= $queryParams['category'] === 'good' ? 'selected' : '' ?>>60–69.99%: Good</option>
          <option value="average" <?= $queryParams['category'] === 'average' ? 'selected' : '' ?>>50–59.99%: Average</option>
          <option value="needs_improvement" <?= $queryParams['category'] === 'needs_improvement' ? 'selected' : '' ?>>40–49.99%: Needs Improvement</option>
          <option value="low_performance" <?= $queryParams['category'] === 'low_performance' ? 'selected' : '' ?>>Below 40%: Low Performance</option>
          <option value="absent" <?= $queryParams['category'] === 'absent' ? 'selected' : '' ?>>All Subjects Absent</option>
          <option value="incomplete" <?= $queryParams['category'] === 'incomplete' ? 'selected' : '' ?>>Not Available / Incomplete</option>
        </select>
      </div>

      <!-- Sort Filter -->
      <div style="flex: 1.2; min-width: 160px;">
        <label for="sort" class="form-label" style="font-size: 0.75rem; text-transform: uppercase; color: var(--color-text-muted); font-weight: 700;">
          Sort Order
        </label>
        <select name="sort" id="sort" class="form-control" onchange="this.form.submit()">
          <option value="pct_desc" <?= $queryParams['sort'] === 'pct_desc' ? 'selected' : '' ?>>Rank: Highest to Lowest</option>
          <option value="pct_asc" <?= $queryParams['sort'] === 'pct_asc' ? 'selected' : '' ?>>Rank: Lowest to Highest</option>
          <option value="rank_asc" <?= $queryParams['sort'] === 'rank_asc' ? 'selected' : '' ?>>By Rank Number (1 to N)</option>
          <option value="name_asc" <?= $queryParams['sort'] === 'name_asc' ? 'selected' : '' ?>>Student Name (A–Z)</option>
          <option value="name_desc" <?= $queryParams['sort'] === 'name_desc' ? 'selected' : '' ?>>Student Name (Z–A)</option>
        </select>
      </div>

      <!-- Action Buttons -->
      <div style="display: flex; gap: 0.5rem;">
        <button type="submit" class="btn btn-primary" style="height: 38px;">Filter</button>
        <?php if (!empty($queryParams['search']) || ($queryParams['category'] !== 'all') || ($queryParams['sort'] !== 'pct_desc')): ?>
          <a href="performance.php" class="btn btn-secondary" style="height: 38px;" title="Reset filters">Reset</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- 4. Student Competition Ranking Table -->
  <div class="content-card">
    <div class="card-header-flex">
      <div>
        <h2 class="card-title">Student Competition Ranking Ledger</h2>
        <p class="card-subtitle">
          Competition rank ordering (equal percentages share rank, next rank skips; e.g. 1, 2, 2, 4). Showing <?= count($rankedStudents) ?> records.
        </p>
      </div>
      <span class="badge badge-info">
        <?= count($rankedStudents) ?> Displayed
      </span>
    </div>

    <div class="table-responsive">
      <table class="data-table" id="performanceRankingTable">
        <thead>
          <tr>
            <th class="w-16 text-center">Rank</th>
            <th>Student Name</th>
            <th class="w-36">Register Number</th>
            <th class="w-36 text-right">Total Marks</th>
            <th class="w-32 text-center">Attended Max</th>
            <th class="w-36 text-right">Percentage</th>
            <th class="w-40 text-center">Performance Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($rankedStudents)): ?>
            <?php foreach ($rankedStudents as $st): 
              $initials = getStudentInitials($st['student_name']);
              $pct = $st['calculated_percentage'];
              $isAbsent = ($pct === 'Absent');
              $category = $st['category'];
              $rank = $st['rank'];
            ?>
              <tr class="<?= $isAbsent ? 'row-absent' : '' ?>">
                <!-- 1. Rank (Competition format with gold/silver/bronze badges) -->
                <td class="text-center font-mono">
                  <?php if ($rank === 1): ?>
                    <span class="rank-badge rank-1" title="1st Place (Valedictorian)">1</span>
                  <?php elseif ($rank === 2): ?>
                    <span class="rank-badge rank-2" title="2nd Place">2</span>
                  <?php elseif ($rank === 3): ?>
                    <span class="rank-badge rank-3" title="3rd Place">3</span>
                  <?php elseif (is_numeric($rank)): ?>
                    <span class="rank-badge rank-neutral"><?= $rank ?></span>
                  <?php else: ?>
                    <span class="text-muted" title="Unranked (Absent/Incomplete)">-</span>
                  <?php endif; ?>
                </td>

                <!-- 2. Student Name & Initial Avatar -->
                <td>
                  <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div class="user-avatar" style="width: 34px; height: 34px; font-size: 0.82rem; background: #e0e7ff; color: #4338ca; flex-shrink: 0;">
                      <?= htmlspecialchars($initials) ?>
                    </div>
                    <div>
                      <a href="view_student.php?id=<?= (int)$st['id'] ?>" class="student-name hover-underline">
                        <?= htmlspecialchars($st['student_name']) ?>
                      </a>
                      <span class="block text-[11px] text-muted">
                        Record ID #<?= (int)$st['id'] ?> &bull; <?= (int)$st['age'] ?> yrs
                      </span>
                    </div>
                  </div>
                </td>

                <!-- 3. Register Number -->
                <td>
                  <span class="num-tabular font-mono" style="font-weight: 600; color: var(--color-text-muted);">
                    <?= htmlspecialchars($st['register_no']) ?>
                  </span>
                </td>

                <!-- 4. Total Obtained Marks -->
                <td class="text-right font-mono font-bold">
                  <?php if ($isAbsent): ?>
                    <span style="color: #94a3b8; font-weight: normal;">0.00</span>
                  <?php else: ?>
                    <?= number_format((float)$st['total_obtained'], 2) ?>
                  <?php endif; ?>
                </td>

                <!-- 5. Applicable Maximum Marks -->
                <td class="text-center font-mono text-muted">
                  <?= (int)$st['attended_maximum_marks'] ?>
                </td>

                <!-- 6. Percentage with Color Progress -->
                <td class="text-right">
                  <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.25rem;">
                    <span class="font-mono font-bold" style="font-size: 0.95rem; color: var(--color-text-main);">
                      <?php if ($isAbsent): ?>
                        <span style="color: #ef4444;">ABSENT</span>
                      <?php elseif ($pct !== null): ?>
                        <?= number_format((float)$pct, 2) ?>%
                      <?php else: ?>
                        <span style="color: var(--color-text-light);">Not Available</span>
                      <?php endif; ?>
                    </span>
                    <?php if ($pct !== null && $pct !== 'Absent'): 
                      $barPct = min(100, max(0, (float)$pct));
                      $barColor = ($barPct >= 70) ? '#10b981' : (($barPct >= 50) ? '#6366f1' : (($barPct >= 40) ? '#f59e0b' : '#ef4444'));
                    ?>
                      <div style="width: 80px; height: 3.5px; background: #e2e8f0; border-radius: 9999px; overflow: hidden;">
                        <div style="width: <?= $barPct ?>%; height: 100%; background: <?= $barColor ?>;"></div>
                      </div>
                    <?php endif; ?>
                  </div>
                </td>

                <!-- 7. Performance Status Badge -->
                <td class="text-center">
                  <span class="badge-status <?= htmlspecialchars($category['class']) ?>">
                    <span class="badge-dot"></span>
                    <?= htmlspecialchars($category['label']) ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" style="text-align: center; padding: 4rem 1.5rem; color: var(--color-text-light);">
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.75rem; max-width: 420px; margin: 0 auto;">
                  <div style="width: 48px; height: 48px; border-radius: 50%; background: var(--color-canvas); color: var(--color-primary); display: flex; align-items: center; justify-content: center;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="11" cy="11" r="8"></circle>
                      <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                  </div>
                  <h3 style="font-weight: 700; color: var(--color-text-main); font-size: 1rem;">No Students Matched Filter Criteria</h3>
                  <p style="font-size: 0.82rem; color: var(--color-text-muted); line-height: 1.5;">
                    No student records matched the search term <code><?= htmlspecialchars($queryParams['search']) ?></code> or selected category.
                  </p>
                  <a href="performance.php" class="btn btn-secondary btn-sm">Reset Filters</a>
                </div>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Table Footer Summary -->
    <div style="padding: 1rem 1.5rem; background: #fafbfd; border-top: 1px solid var(--color-border); display: flex; justify-content: space-between; align-items: center; font-size: 0.82rem; color: var(--color-text-muted);">
      <div>
        Showing <strong class="num-tabular text-main"><?= count($rankedStudents) ?></strong> students &bull; Competition rank ties appropriately skip to preserve mathematical placement.
      </div>
      <div>
        Calculated dynamically via <code>(Total Obtained / Attended Max) &times; 100</code>
      </div>
    </div>
  </div>

<?php include __DIR__ . '/includes/footer.php'; ?>
