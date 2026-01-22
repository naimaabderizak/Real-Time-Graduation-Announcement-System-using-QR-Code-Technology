<?php
/**
 * debug_me.php
 * 
 * Session Debugging Utility
 * 
 * helper file to check current session variables like User ID, Role, etc.
 * Useful for troubleshooting permission issues.
 * 
 * Author: System
 * Date: 2026-01-05
 */

session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Debugger</title>
    <style>body { font-family: monospace; padding: 2rem; }</style>
</head>
<body>
    <h1>Debug Session Info</h1>
    <p><strong>User ID:</strong> <?php echo $_SESSION['user_id'] ?? 'Not Set'; ?></p>
    <p><strong>Username:</strong> <?php echo $_SESSION['username'] ?? 'Not Set'; ?></p>
    <p><strong>Role:</strong> <?php echo $_SESSION['role'] ?? 'Not Set (Missing!)'; ?></p>
    
    <hr>
    <p>If Role is "Not Set", "staff", or anything other than "admin", you cannot access the User page.</p>
    <p><a href="logout.php">Log Out</a> | <a href="index.php">Back to Dashboard</a></p>
</body>
</html>
