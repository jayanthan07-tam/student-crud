<?php
// test_connection.php
// Database Connectivity Diagnostic Page
// Academic Operations System - Student CRUD Application
// Section 03: PHP Database Connection Verification

$pageTitle = 'Database Connection Test';
$currentPage = 'test_connection';

// 1. Require Centralized Database Configuration
require_once __DIR__ . '/config/database.php';

// Variables to capture diagnostic results
$isConnected = false;
$diagnostics = [];
$tablesFound = [];
$errorMessage = null;

// 2. Perform Safe Connection Diagnostic
if (isset($pdo) && $pdo instanceof PDO) {
    try {
        // Query database metadata
        $stmt = $pdo->query("SELECT DATABASE() AS current_db, VERSION() AS mysql_ver, @@character_set_connection AS charset_name");
        $meta = $stmt->fetch();
        
        $isConnected = true;
        $diagnostics = [
            'Database Host'         => DB_HOST . ':' . DB_PORT,
            'Connected Database'    => $meta['current_db'] ?? DB_NAME,
            'MySQL Version'         => $meta['mysql_ver'] ?? 'Unknown',
            'Connection Charset'    => $meta['charset_name'] ?? DB_CHARSET,
            'PDO Error Mode'        => 'PDO::ERRMODE_EXCEPTION (Active)',
            'Default Fetch Mode'    => 'PDO::FETCH_ASSOC (Associative)',
            'Emulate Prepares'      => 'Disabled (Native Prepared Statements)',
        ];

        // Audit existing tables created in Section 02
        $tableStmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        while ($row = $tableStmt->fetch(PDO::FETCH_NUM)) {
            $tablesFound[] = $row[0];
        }

    } catch (PDOException $e) {
        $isConnected = false;
        error_log("Diagnostics Query Error: " . $e->getMessage());
        $errorMessage = "Connected to MySQL engine, but failed to query database schema. Verify privileges for user '" . DB_USER . "'.";
    }
} else {
    $isConnected = false;
    $errorMessage = $dbConnectionError ?? "Could not establish a connection to MySQL server on host '" . DB_HOST . "'.";
}

// 3. Include Reusable Header Layout
include __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Database Connection Verification</h1>
    <p class="page-subtitle">Section 03 &bull; PHP Data Objects (PDO) handshake and schema audit.</p>
  </div>
  <div>
    <a href="dashboard.php" class="btn btn-secondary">
      &larr; Return to Dashboard
    </a>
  </div>
</div>

<?php if ($isConnected): ?>
  <!-- SUCCESS STATE: Google Stitch UI Card -->
  <div class="card" style="border-top: 4px solid var(--color-success); max-width: 860px;">
    <div class="card-header" style="background: #f0fdf4;">
      <div style="display: flex; align-items: center; gap: 0.75rem;">
        <div style="width: 36px; height: 36px; border-radius: 50%; background: #dcfce7; color: #15803d; display: flex; align-items: center; justify-content: center;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
        </div>
        <div>
          <h2 class="card-title" style="color: #166534; font-size: 1.1rem;">MySQL Connection Established Successfully</h2>
          <p class="card-subtitle" style="color: #15803d;">PDO driver initialized with UTF-8 encoding and strict exceptions</p>
        </div>
      </div>
      <span class="badge-status active">
        <span class="badge-dot"></span> Live / Connected
      </span>
    </div>

    <div class="card-body">
      <h3 style="font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--color-text-muted); margin-bottom: 1rem;">
        PDO Driver Configuration & Runtime Environment
      </h3>

      <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 2rem;">
        <?php foreach ($diagnostics as $label => $val): ?>
          <div style="background: var(--color-canvas); padding: 0.85rem 1rem; border-radius: var(--radius-md); border: 1px solid var(--color-border);">
            <div style="font-size: 0.75rem; color: var(--color-text-light); text-transform: uppercase; font-weight: 600; letter-spacing: 0.03em;">
              <?= htmlspecialchars($label) ?>
            </div>
            <div style="font-size: 0.95rem; font-weight: 600; color: var(--color-text-main); margin-top: 0.15rem;" class="num-tabular">
              <?= htmlspecialchars($val) ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Section 02 Tables Audit -->
      <h3 style="font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--color-text-muted); margin-bottom: 0.75rem;">
        Section 02 Tables Audit (Schema: <code><?= htmlspecialchars(DB_NAME) ?></code>)
      </h3>

      <div style="background: var(--color-canvas); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 1rem; margin-bottom: 1.5rem;">
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
          <?php 
          $requiredTables = ['students', 'subjects', 'student_marks'];
          foreach ($requiredTables as $reqTable): 
            $exists = in_array($reqTable, $tablesFound);
          ?>
            <div style="display: flex; align-items: center; gap: 0.5rem; background: #ffffff; padding: 0.5rem 0.85rem; border-radius: var(--radius-sm); border: 1px solid <?= $exists ? '#bbf7d0' : '#fecaca' ?>;">
              <?php if ($exists): ?>
                <span style="color: #16a34a; font-weight: bold;">&check;</span>
                <span style="font-size: 0.85rem; font-weight: 600; color: var(--color-text-main); font-family: monospace;"><?= $reqTable ?></span>
                <span style="font-size: 0.7rem; color: #16a34a; background: #f0fdf4; padding: 1px 6px; border-radius: 9999px;">Verified</span>
              <?php else: ?>
                <span style="color: #dc2626; font-weight: bold;">&times;</span>
                <span style="font-size: 0.85rem; font-weight: 600; color: #dc2626; font-family: monospace;"><?= $reqTable ?></span>
                <span style="font-size: 0.7rem; color: #dc2626; background: #fef2f2; padding: 1px 6px; border-radius: 9999px;">Missing</span>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
        <a href="dashboard.php" class="btn btn-secondary">Go to Dashboard</a>
        <a href="students.php" class="btn btn-primary">Open Students Directory &rarr;</a>
      </div>
    </div>
  </div>

<?php else: ?>
  <!-- ERROR STATE: Safe, user-friendly guidance without credential leakage -->
  <div class="card" style="border-top: 4px solid var(--color-danger); max-width: 800px;">
    <div class="card-header" style="background: #fef2f2;">
      <div style="display: flex; align-items: center; gap: 0.75rem;">
        <div style="width: 36px; height: 36px; border-radius: 50%; background: #fee2e2; color: #b91c1c; display: flex; align-items: center; justify-content: center;">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </div>
        <div>
          <h2 class="card-title" style="color: #991b1b; font-size: 1.1rem;">Database Connection Offline</h2>
          <p class="card-subtitle" style="color: #b91c1c;">PHP PDO could not connect to MySQL server</p>
        </div>
      </div>
      <span class="badge-status" style="background: #fee2e2; color: #991b1b;">
        <span class="badge-dot" style="background: #dc2626;"></span> Disconnected
      </span>
    </div>

    <div class="card-body">
      <div style="background: #fff1f2; border: 1px solid #fecdd3; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; font-size: 0.875rem; color: #9f1239;">
        <strong>Diagnostic Summary:</strong> <?= htmlspecialchars($errorMessage) ?>
      </div>

      <h3 style="font-size: 0.9rem; font-weight: 700; color: var(--color-text-main); margin-bottom: 0.75rem;">
        Recommended Troubleshooting Steps in XAMPP:
      </h3>

      <ol style="margin-left: 1.5rem; font-size: 0.875rem; color: var(--color-text-muted); line-height: 1.7; margin-bottom: 1.5rem;">
        <li>
          <strong>Check MySQL Service in XAMPP:</strong> Open the XAMPP Control Panel and verify that the <strong>MySQL</strong> module is started (highlighted green with port <code>3306</code>).
        </li>
        <li>
          <strong>Verify Database Name:</strong> Open <a href="http://localhost/phpmyadmin/" target="_blank" style="color: var(--color-primary); font-weight: 600;">http://localhost/phpmyadmin/</a> and verify that a database named <code>student_management</code> exists.
        </li>
        <li>
          <strong>Import Schema from Section 02:</strong> If the database has not been created yet, import the provided SQL file located at <code>database/student_management.sql</code> into phpMyAdmin.
        </li>
      </ol>

      <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
        <a href="test_connection.php" class="btn btn-primary">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="23 4 23 10 17 10"></polyline>
            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
          </svg>
          Retry Connection Test
        </a>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php
// 4. Include Reusable Footer Layout
include __DIR__ . '/includes/footer.php';
?>
