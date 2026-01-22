<?php
/**
 * admin/supplier_delete.php
 * 
 * Delete Supplier
 * 
 * Deletes a supplier and cascades the deletion to their Purchase History.
 * This is a destructive action that removes historical purchase data.
 * 
 * Author: System
 * Date: 2026-01-05
 */

// Start the session to manage user authentication
session_start();
// Include the database connection file
require_once '../includes/db.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to the login page
    header("Location: ../login.php");
    exit;
}

// Access Control: Admin only check
if ($_SESSION['role'] !== 'admin') {
    // If not an admin, redirect to the suppliers list with an error
    header("Location: suppliers.php?error=Access Denied. Admins only.");
    exit;
}

// Check if the supplier ID is provided via POST
if (isset($_POST['id'])) {
    $id = $_POST['id'];
    try {
        // Start a database transaction
        $pdo->beginTransaction();

        // 1. Find all purchases made by this supplier
        $stmt = $pdo->prepare("SELECT id FROM purchases WHERE supplier_id = ?");
        $stmt->execute([$id]);
        $purchases = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // If purchases exist, delete their related items and the purchases themselves
        if ($purchases) {
            // 2. Delete Purchase Items for these purchases
            // Create a placeholder string for the IN clause (e.g., "?,?,?")
            $placeholders = implode(',', array_fill(0, count($purchases), '?'));
            $stmt_items = $pdo->prepare("DELETE FROM purchase_items WHERE purchase_id IN ($placeholders)");
            $stmt_items->execute($purchases);

            // 3. Delete the Purchases records
            $stmt_purchases = $pdo->prepare("DELETE FROM purchases WHERE supplier_id = ?");
            $stmt_purchases->execute([$id]);
        }

        // 4. Delete the Supplier record itself
        $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
        $stmt->execute([$id]);

        // Commit the transaction to save changes
        $pdo->commit();
        // Set a success message in the session
        $_SESSION['flash_success'] = "Supplier and related purchase history deleted successfully.";
    } catch (PDOException $e) {
        // If an error occurs, roll back the transaction
        $pdo->rollBack();
        // Set an error message in the session
        $_SESSION['flash_error'] = "Error deleting supplier: " . $e->getMessage();
    }
}

// Redirect back to the suppliers list page
header("Location: suppliers.php");
exit;
?>
