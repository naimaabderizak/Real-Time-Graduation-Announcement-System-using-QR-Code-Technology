<?php
/**
 * api/reports.php
 * 
 * Reports API Endpoint
 * 
 * Generates JSON data for reports including total sales, purchases, and stock value.
 * Supports date filtering via query parameters.
 * 
 * Author: System
 * Date: 2026-01-05
 */

// Set the content type of the response to JSON
header("Content-Type: application/json");
// Allow requests from any origin (CORS)
header("Access-Control-Allow-Origin: *");
// Allow specific HTTP methods: GET
header("Access-Control-Allow-Methods: GET");
// Allow specific headers in the request
header("Access-Control-Allow-Headers: Content-Type");

// Include the database connection file
require_once '../includes/db.php';

// Simple summary report logic
try {
    // Initialize the response array
    $response = [];

    // Get date filters from query parameters (if provided)
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;
    
    // Initialize WHERE clauses for sales and purchases queries
    // 1=1 is used to easily append AND conditions
    $whereSales = "WHERE 1=1";
    $wherePurchases = "WHERE 1=1";
    // Initialize parameter arrays for prepared statements
    $paramsSales = [];
    $paramsPurchases = [];

    // If a start date is provided, add it to the filters
    if ($startDate) {
        $whereSales .= " AND DATE(s.created_at) >= ?";
        $wherePurchases .= " AND DATE(created_at) >= ?";
        $paramsSales[] = $startDate;
        $paramsPurchases[] = $startDate;
    }
    // If an end date is provided, add it to the filters
    if ($endDate) {
        $whereSales .= " AND DATE(s.created_at) <= ?";
        $wherePurchases .= " AND DATE(created_at) <= ?";
        $paramsSales[] = $endDate;
        $paramsPurchases[] = $endDate;
    }

    // Calculate Total Sales Amount based on filters
    $stmt = $pdo->prepare("SELECT SUM(total_amount) as total FROM sales s $whereSales");
    $stmt->execute($paramsSales);
    $response['total_sales_value'] = (float)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    // Calculate Total Purchases Amount based on filters
    $stmt = $pdo->prepare("SELECT SUM(total_amount) as total FROM purchases $wherePurchases");
    $stmt->execute($paramsPurchases);
    $response['total_purchases_value'] = (float)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    // Calculate Total Sales Count (number of transactions) based on filters
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM sales s $whereSales");
    $stmt->execute($paramsSales);
    $response['total_sales_count'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);

    // Calculate Total Products Count (Snapshot, usually not filtered by date)
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $response['total_products'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    // Calculate Current Stock Value (sum of stock_qty * cost_price for all products)
    $stmt = $pdo->query("SELECT SUM(stock_qty * cost_price) as value FROM products");
    $response['stock_value'] = (float)($stmt->fetch(PDO::FETCH_ASSOC)['value'] ?? 0);

    // Fetch Detailed Sales list based on filters
    // Joins with customers table to get customer names
    $sqlDetails = "SELECT s.id, s.invoice_no, s.total_amount as amount, s.created_at as date, c.name as customer_name 
                   FROM sales s 
                   LEFT JOIN customers c ON s.customer_id = c.id 
                   $whereSales 
                   ORDER BY s.created_at DESC";
    $stmt = $pdo->prepare($sqlDetails);
    $stmt->execute($paramsSales);
    $detailedSales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the detailed sales data (ensure correct types)
    $response['detailed_sales'] = array_map(function($sale) {
        $sale['amount'] = (float)$sale['amount'];
        $sale['id'] = (int)$sale['id'];
        return $sale;
    }, $detailedSales);

    // Return the response as JSON
    echo json_encode(['success' => true, 'data' => $response]);

} catch (Exception $e) {
    // Handle any exceptions and return the error message
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
