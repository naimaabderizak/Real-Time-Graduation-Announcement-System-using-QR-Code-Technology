<?php
/**
 * logout.php
 * 
 * Session Termination
 * 
 * Simply destroys the current user session and redirects to the login page.
 * 
 * Author: System
 * Date: 2026-01-05
 */

// Start the session (required to access current session)
session_start();
// Unset all session variables
session_unset();
// Destroy the session completely
session_destroy();
// Redirect user to the login page
header("Location: login.php");
// Stop script execution
exit;
?>
