<?php
// includes/header.php
// Reusable HTML head and top navigation header
// Configurable: $pageTitle (string), $currentPage (string)

if (!isset($pageTitle)) {
    $pageTitle = 'Academic Operations System';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> | Academic Operations System</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <!-- Chart.js for Interactive Analytical Visualizations -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
</head>
<body>

<div class="app-container">
  <!-- Reusable Sidebar Navigation -->
  <?php include __DIR__ . '/sidebar.php'; ?>

  <!-- Main Content Wrapper -->
  <div class="main-wrapper">
    <!-- Topbar / App Header -->
    <header class="topbar">
      <div class="topbar-left">
        <button type="button" class="mobile-menu-btn" id="mobileMenuToggle" aria-label="Toggle Navigation">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
          </svg>
        </button>
        
        <div class="search-box">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          </svg>
          <input type="text" id="globalQuickSearch" placeholder="Search student ID, register no, or name...">
        </div>
      </div>

      <div class="topbar-right">
        <div class="date-indicator" id="currentDateIndicator">
          <?= date('D, M j, Y') ?>
        </div>

        <!-- Notification Bell Icon (Google Stitch UI) -->
        <div class="notification-wrapper" id="notificationDropdownWrap">
          <button type="button" class="notification-btn" id="notificationToggleBtn" aria-label="View system notifications" title="System Notifications">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
              <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
            </svg>
            <span class="notification-badge" aria-label="3 new notifications">3</span>
          </button>
          
          <div class="notification-dropdown" id="notificationDropdownMenu">
            <div class="dropdown-header">
              <span>Notifications & Telemetry</span>
              <span class="badge-status active">3 New</span>
            </div>
            <ul class="dropdown-list">
              <li>
                <div class="notif-dot success"></div>
                <div>
                  <div class="notif-text">Section 04 dataset validated</div>
                  <div class="notif-time">28 student records mapped</div>
                </div>
              </li>
              <li>
                <div class="notif-dot info"></div>
                <div>
                  <div class="notif-text">MySQL PDO connection healthy</div>
                  <div class="notif-time">Database: student_management</div>
                </div>
              </li>
              <li>
                <div class="notif-dot warning"></div>
                <div>
                  <div class="notif-text">Academic standing audit active</div>
                  <div class="notif-time">Calculation view loaded</div>
                </div>
              </li>
            </ul>
          </div>
        </div>

        <!-- User Profile Section -->
        <div class="user-profile">
          <div class="user-avatar">DR</div>
          <div class="user-info">
            <span class="user-name">Dr. J. Vance</span>
            <span class="user-role">Lead University Registrar</span>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Page Content Starts Here -->
    <main class="page-content">
