<?php
/**
 * api/dashboard.php
 * 
 * Dashboard Statistics API
 * 
 * Fetches key performance indicators (Total Products, Sales Today, Profit Today)
 * for the dashboard view. Returns data in JSON format.
 * 
 * Author: System
 * Date: 2026-01-05
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../includes/db.php';

try {
    // 1. Total Products
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    $totalProducts = $stmt->fetchColumn();

    // 2. Sales Today
    $stmt = $pdo->prepare("SELECT SUM(total_amount) FROM sales WHERE DATE(created_at) = CURDATE()");
    $stmt->execute();
    $salesToday = $stmt->fetchColumn() ?: 0;

    // 3. Profit Today
    $query = "
        SELECT SUM((si.unit_price - p.cost_price) * si.quantity) 
        FROM sales_items si 
        JOIN sales s ON si.sale_id = s.id 
        JOIN products p ON si.product_id = p.id 
        WHERE DATE(s.created_at) = CURDATE()
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $profitToday = $stmt->fetchColumn() ?: 0;

    http_response_code(200);
    echo json_encode(array(
        "success" => true,
        "data" => array(
            "total_products" => (int)$totalProducts,
            "sales_today" => (float)$salesToday, // Ensure float/double
            "profit_today" => (float)$profitToday
        )
    ));

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "Error generating dashboard data: " . $e->getMessage()));
}
?>
