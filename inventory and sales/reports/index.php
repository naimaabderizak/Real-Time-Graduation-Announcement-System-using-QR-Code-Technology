<?php
/**
 * reports/index.php
 * 
 * Reports Dashboard
 * 
 * Displays summary statistics for Sales and Purchases.
 * Provides a date filter to customize the reporting period.
 * Lists detailed sales transactions within the selected date range.
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

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Sales Report
$stmt_sales = $pdo->prepare("
    SELECT COUNT(*) as total_count, SUM(total_amount) as total_value 
    FROM sales 
    WHERE DATE(created_at) BETWEEN ? AND ?
");
$stmt_sales->execute([$start_date, $end_date]);
$sales_stats = $stmt_sales->fetch();

// Purchases Report
$stmt_purchases = $pdo->prepare("
    SELECT COUNT(*) as total_count, SUM(total_amount) as total_value 
    FROM purchases 
    WHERE DATE(created_at) BETWEEN ? AND ?
");
$stmt_purchases->execute([$start_date, $end_date]);
$purchases_stats = $stmt_purchases->fetch();

// Detailed Sales
$stmt_sales_list = $pdo->prepare("
    SELECT s.*, c.name as customer_name 
    FROM sales s 
    LEFT JOIN customers c ON s.customer_id = c.id 
    WHERE DATE(s.created_at) BETWEEN ? AND ? 
    ORDER BY s.created_at DESC
");
$stmt_sales_list->execute([$start_date, $end_date]);
$sales_list = $stmt_sales_list->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Inventory System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                Inventory Sys
            </div>
            <ul class="sidebar-menu">
                <li><a href="../index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="../admin/products.php"><i class="fas fa-box"></i> Products</a></li>
                <li><a href="../admin/suppliers.php"><i class="fas fa-truck"></i> Suppliers</a></li>
                <li><a href="../admin/customers.php"><i class="fas fa-users"></i> Customers</a></li>
                <li><a href="../purchases/index.php"><i class="fas fa-shopping-cart"></i> Purchases</a></li>
                <li><a href="../sales/index.php"><i class="fas fa-cash-register"></i> Sales</a></li>
                <li><a href="index.php" class="active"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="../admin/users.php"><i class="fas fa-user-cog"></i> Users</a></li>
                <li><a href="../team.php"><i class="fas fa-users-cog"></i> Our Team</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1 class="page-title">Reports</h1>
                <button onclick="window.print()" class="btn btn-secondary btn-print"><i class="fas fa-print"></i> Print</button>
            </div>

            <div class="card">
                <form method="GET" action="" style="display: flex; gap: 20px; align-items: flex-end;">
                    <div class="form-group" style="margin-bottom: 0; flex: 1;">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="form-group" style="margin-bottom: 0; flex: 1;">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
                </form>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div class="card" style="border-left: 4px solid var(--secondary-color);">
                    <h3>Total Sales</h3>
                    <p style="font-size: 2rem; font-weight: bold;">
                        <?php echo number_format($sales_stats['total_value'] ?? 0, 2); ?>
                    </p>
                    <p style="color: #6b7280;"><?php echo $sales_stats['total_count']; ?> transactions</p>
                </div>
                <div class="card" style="border-left: 4px solid var(--danger-color);">
                    <h3>Total Purchases</h3>
                    <p style="font-size: 2rem; font-weight: bold;">
                        <?php echo number_format($purchases_stats['total_value'] ?? 0, 2); ?>
                    </p>
                    <p style="color: #6b7280;"><?php echo $purchases_stats['total_count']; ?> transactions</p>
                </div>
            </div>

            <div class="card">
                <h3>Sales Details</h3>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Invoice</th>
                                <th>Customer</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($sales_list) > 0): ?>
                                <?php foreach ($sales_list as $sale): ?>
                                    <tr>
                                        <td><?php echo date('Y-m-d H:i', strtotime($sale['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($sale['invoice_no']); ?></td>
                                        <td><?php echo htmlspecialchars($sale['customer_name'] ?? 'Walk-in'); ?></td>
                                        <td><?php echo number_format($sale['total_amount'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center;">No sales found in this period.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
