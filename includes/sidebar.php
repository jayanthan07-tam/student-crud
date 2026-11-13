<?php
// includes/sidebar.php
// Reusable sidebar navigation component
// Dynamically highlights current active menu item based on $currentPage variable

if (!isset($currentPage)) {
    $currentPage = 'dashboard';
}

// Fetch dynamic student count for sidebar badge if database connection exists
if (!isset($sidebarStudentCount)) {
    if (isset($pdo) && $pdo instanceof PDO) {
        try {
            $countStmt = $pdo->query("SELECT COUNT(*) FROM students");
            $sidebarStudentCount = (int)$countStmt->fetchColumn();
        } catch (Exception $e) {
            $sidebarStudentCount = 0;
        }
    } else {
        $sidebarStudentCount = 0;
    }
}
?>
<!-- Sidebar Navigation Rail -->
<aside class="sidebar" id="appSidebar">
  <div class="sidebar-header">
    <a href="dashboard.php" class="brand-link">
      <div class="brand-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
          <path d="M6 12v5c3 3 9 3 12 0v-5"/>
        </svg>
      </div>
      <div class="brand-text">
        <h1>Academic Ops</h1>
        <span>Registrar Portal</span>
      </div>
    </a>
  </div>

  <div class="sidebar-nav">
    <div class="nav-section-title">Core Administration</div>
    <ul class="nav-list">
      <li>
        <a href="dashboard.php" class="nav-link <?= ($currentPage === 'dashboard') ? 'active' : '' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <rect x="3" y="3" width="7" height="7" rx="1"/>
            <rect x="14" y="3" width="7" height="7" rx="1"/>
            <rect x="14" y="14" width="7" height="7" rx="1"/>
            <rect x="3" y="14" width="7" height="7" rx="1"/>
          </svg>
          <span>Dashboard</span>
        </a>
      </li>
      <li>
        <a href="students.php" class="nav-link <?= ($currentPage === 'students') ? 'active' : '' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
          </svg>
          <span>Students Directory</span>
          <span class="nav-badge"><?= $sidebarStudentCount ?></span>
        </a>
      </li>
      <li>
        <a href="add_student.php" class="nav-link <?= ($currentPage === 'add_student') ? 'active' : '' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="16"/>
            <line x1="8" y1="12" x2="16" y2="12"/>
          </svg>
          <span>Enroll New Student</span>
        </a>
      </li>
      <li>
        <a href="performance.php" class="nav-link <?= ($currentPage === 'performance') ? 'active' : '' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <line x1="18" y1="20" x2="18" y2="10"/>
            <line x1="12" y1="20" x2="12" y2="4"/>
            <line x1="6" y1="20" x2="6" y2="14"/>
          </svg>
          <span>GPA & Standing</span>
        </a>
      </li>
    </ul>

    <div class="nav-section-title">Operations & Records</div>
    <ul class="nav-list">
      <li>
        <a href="test_connection.php" class="nav-link <?= ($currentPage === 'test_connection') ? 'active' : '' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <ellipse cx="12" cy="5" rx="9" ry="3"/>
            <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/>
            <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
          </svg>
          <span>DB Connection Test</span>
          <span class="nav-badge" style="background: #f0fdf4; color: #15803d; font-weight: 700;">PDO</span>
        </a>
      </li>
      <li>
        <a href="performance.php?category=low_performance" class="nav-link <?= ($currentPage === 'performance' && ($_GET['category'] ?? '') === 'low_performance') ? 'active' : '' ?>">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          <span>Academic Warnings</span>
          <span class="nav-badge" style="background: #fffbeb; color: #b45309;">Audit</span>
        </a>
      </li>
    </ul>
  </div>

  <div class="sidebar-footer">
    <div class="system-status-chip">
      <span class="status-dot"></span>
      <div>
        <div style="font-weight: 600; color: var(--color-text-main); font-size: 0.8rem;">XAMPP Core PHP</div>
        <div style="font-size: 0.7rem; color: var(--color-text-light);">MySQL PDO &bull; Production Ready</div>
      </div>
    </div>
  </div>
</aside>
