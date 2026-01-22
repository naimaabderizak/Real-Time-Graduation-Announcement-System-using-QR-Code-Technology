<?php
/**
 * sales/delete.php
 * 
 * Void Sale
 * 
 * Voids a sale record and reverses the stock changes.
 * Restores the quantity of items back to the product inventory.
 * Restricted to Admin users.
 * 
 * Author: System
 * Date: 2026-01-05
 */

session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if (isset($_POST['id'])) {
    $sale_id = $_POST['id'];
    
    try {
        $pdo->beginTransaction();

        // 1. Get items to reverse stock
        $stmt_items = $pdo->prepare("SELECT product_id, quantity FROM sales_items WHERE sale_id = ?");
        $stmt_items->execute([$sale_id]);
        $items = $stmt_items->fetchAll();

        $stmt_stock = $pdo->prepare("UPDATE products SET stock_qty = stock_qty + ? WHERE id = ?");
        $stmt_move = $pdo->prepare("INSERT INTO stock_movements (product_id, change_qty, reason, created_by) VALUES (?, ?, ?, ?)");

        foreach ($items as $item) {
            // Reverse Stock (Add back)
            $stmt_stock->execute([$item['quantity'], $item['product_id']]);
            
            // Log Movement
            $stmt_move->execute([$item['product_id'], $item['quantity'], "Void Sale #$sale_id", $_SESSION['user_id']]);
        }

        // 2. Delete Sale (Cascades to items)
        $stmt_del = $pdo->prepare("DELETE FROM sales WHERE id = ?");
        $stmt_del->execute([$sale_id]);

        $pdo->commit();
        $_SESSION['flash_success'] = "Sale voided and stock restored successfully.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['flash_error'] = "Error voiding sale: " . $e->getMessage();
    }
}

header("Location: index.php");
exit;
?>
