<?php
// index.php
// Main project entry point for XAMPP
// Redirects clean visitor requests to the primary dashboard view

header("Location: dashboard.php");
exit();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="refresh" content="0;url=dashboard.php">
  <title>Academic Operations System</title>
</head>
<body>
  <p>Redirecting to <a href="dashboard.php">Dashboard</a>...</p>
</body>
</html>
