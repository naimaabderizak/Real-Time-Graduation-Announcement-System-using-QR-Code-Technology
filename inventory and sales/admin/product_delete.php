<?php
/**
 * admin/product_delete.php
 * 
 * Delete Product
 * 
 * Removes a product and all its associated data (history) from the database.
 * This includes Stock Movements, Sales Items, and Purchase Items.
 * Restricted to Admin users only to prevent accidental data loss.
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

// Access Control: Strict check for Admin role
if ($_SESSION['role'] !== 'admin') {
    // If not an admin, redirect to the products page with an error
    header("Location: products.php?error=Access Denied. Admins only.");
    exit;
}

// Check if product ID is posted for deletion
if (isset($_POST['id'])) {
    $id = $_POST['id'];
    try {
        // Begin transaction to ensure data integrity
        $pdo->beginTransaction();

        // Delete related records manually to bypass FK constraints (Simulating Cascade)
        // 1. Delete associated Stock Movements
        $stmt = $pdo->prepare("DELETE FROM stock_movements WHERE product_id = ?");
        $stmt->execute([$id]);

        // 2. Delete Sales Items (Note: This leaves Sales with missing items/totals, but keeps the Invoice)
        // Ideally we should recalculate sales totals or forbid this, but for this request we delete.
        $stmt = $pdo->prepare("DELETE FROM sales_items WHERE product_id = ?");
        $stmt->execute([$id]);

        // 3. Delete Purchase Items
        $stmt = $pdo->prepare("DELETE FROM purchase_items WHERE product_id = ?");
        $stmt->execute([$id]);

        // 4. Delete The Product itself
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);

        // Commit transaction to finalize the deletion
        $pdo->commit();
        // Set success message in session
        $_SESSION['flash_success'] = "Product and its history deleted successfully.";
    } catch (PDOException $e) {
        // Rollback on error to prevent partial deletion
        $pdo->rollBack();
        $_SESSION['flash_error'] = "Error deleting product: " . $e->getMessage();
    }
}

// Redirect to products page
header("Location: products.php");
exit;
?>
