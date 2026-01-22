<?php
/**
 * includes/db.php
 * 
 * Database Connection Setup
 * 
 * Establishes the connection to the MySQL database using PDO.
 * Included in pages that need database access.
 * 
 * Author: System
 * Date: 2026-01-05
 */

// Database configuration
// Hostname for the database server (usually 'localhost' for local development)
$host = 'localhost';
// Name of the database to connect to
$db_name = 'inventory_system';
// Database username
$username = 'root';
// Database password (default is empty for XAMPP)
$password = '';

try {
    // Create a new PDO instance to establish the database connection
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    // Set the PDO error mode to Exception, so errors throw exceptions
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set the default fetch mode to Associative Array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // If connection fails, stop execution and print the error message
    die("Connection failed: " . $e->getMessage());
}
?>
