<?php
/**
 * sales/index.php
 * 
 * Sales List Endpoint
 * 
 * Displays a list of all sales invoices.
 * Shows details like date, customer, invoice number, and total amount.
 * Allows Admins to void sales.
 * 
 * Author: System
 * Date: 2026-01-05
 */

// Start the session to manage user login state
session_start();
// Include the database connection file
require_once '../includes/db.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to the login page
    header("Location: ../login.php");
    exit;
}

// Prepare query to fetch sales data
// Selects sales details, customer name (via Left Join), and creator username (via Left Join)
$stmt = $pdo->query("
    SELECT s.*, c.name as customer_name, u.username as created_by_name 
    FROM sales s 
    LEFT JOIN customers c ON s.customer_id = c.id 
    LEFT JOIN users u ON s.created_by = u.id 
    ORDER BY s.created_at DESC
");
// Execute the query and fetch all results
$sales = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales - Inventory System</title>
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
                <li><a href="index.php" class="active"><i class="fas fa-cash-register"></i> Sales</a></li>
                <li><a href="../reports/index.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="../admin/users.php"><i class="fas fa-user-cog"></i> Users</a></li>
                <li><a href="../team.php"><i class="fas fa-users-cog"></i> Our Team</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1 class="page-title">Sales</h1>
                <div style="display: flex; gap: 10px;">
                    <button onclick="window.print()" class="btn btn-secondary btn-print"><i class="fas fa-print"></i> Print</button>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="create.php" class="btn btn-primary"><i class="fas fa-plus"></i> New Sale</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="no-print" style="width: 40px;"><input type="checkbox" id="selectAll"></th>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Invoice No</th>
                                <th>Customer</th>
                                <th>Total Amount</th>
                                <th>Created By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($sales) > 0): ?>
                                <?php foreach ($sales as $sale): ?>
                                    <tr>
                                        <td class="no-print"><input type="checkbox" class="row-checkbox"></td>
                                        <td><?php echo $sale['id']; ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($sale['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($sale['invoice_no']); ?></td>
                                        <td><?php echo htmlspecialchars($sale['customer_name'] ?? 'Walk-in'); ?></td>
                                        <td><?php echo number_format($sale['total_amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($sale['created_by_name']); ?></td>
                                        <td>
                                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                                <form method="POST" action="delete.php" style="display:inline;" onsubmit="return confirm('Are you sure? This will VOID the sale and RESTORE stock.');">
                                                    <input type="hidden" name="id" value="<?php echo $sale['id']; ?>">
                                                    <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem;"><i class="fas fa-ban"></i> Void</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center;">No sales found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script src="../js/main.js"></script>
</body>
</html>
