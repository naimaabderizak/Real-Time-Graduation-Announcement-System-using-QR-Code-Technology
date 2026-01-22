<?php
/**
 * admin/customer_delete.php
 * 
 * Delete Customer
 * 
 * Handles the deletion of a customer record.
 * IMPORTANT: It unlinks related sales (sets customer_id to NULL) before deleting 
 * to preserve sales history and satisfy foreign key constraints.
 * 
 * Author: System
 * Date: 2026-01-05
 */

// Start the session
session_start();
// Include database connection
require_once '../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Access Control: Admin only
// Ensure only admins can delete customers
if ($_SESSION['role'] !== 'admin') {
    header("Location: customers.php?error=Access Denied. Admins only.");
    exit;
}

// Check if customer ID is provided in POST data
if (isset($_POST['id'])) {
    $id = $_POST['id'];
    try {
        // Start transaction for atomic operation
        $pdo->beginTransaction();

        // 1. Unlink from Sales (Set customer_id to NULL to preserve sales history)
        // This ensures sales records are not deleted when a customer is removed
        $stmt = $pdo->prepare("UPDATE sales SET customer_id = NULL WHERE customer_id = ?");
        $stmt->execute([$id]);

        // 2. Delete the Customer record
        $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
        $stmt->execute([$id]);

        // Commit the transaction
        $pdo->commit();
        $_SESSION['flash_success'] = "Customer deleted successfully.";
    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['flash_error'] = "Error deleting customer: " . $e->getMessage();
    }
}

// Redirect back to customers list
header("Location: customers.php");
exit;
?>
