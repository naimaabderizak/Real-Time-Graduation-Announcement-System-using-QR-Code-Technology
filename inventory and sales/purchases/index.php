<?php
/**
 * purchases/index.php
 * 
 * Purchases List Endpoint
 * 
 * Displays a list of all purchase orders.
 * Shows details like date, supplier, reference number, total amount, and status.
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

// Fetch purchases with supplier name
$stmt = $pdo->query("
    SELECT p.*, s.name as supplier_name, u.username as created_by_name 
    FROM purchases p 
    JOIN suppliers s ON p.supplier_id = s.id 
    LEFT JOIN users u ON p.created_by = u.id 
    ORDER BY p.created_at DESC
");
$purchases = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchases - Inventory System</title>
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
                <li><a href="index.php" class="active"><i class="fas fa-shopping-cart"></i> Purchases</a></li>
                <li><a href="../sales/index.php"><i class="fas fa-cash-register"></i> Sales</a></li>
                <li><a href="../reports/index.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="../admin/users.php"><i class="fas fa-user-cog"></i> Users</a></li>
                <li><a href="../team.php"><i class="fas fa-users-cog"></i> Our Team</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1 class="page-title">Purchase Orders</h1>
                <a href="create.php" class="btn btn-primary"><i class="fas fa-plus"></i> New Purchase</a>
            </div>

            <div class="card">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Supplier</th>
                                <th>Ref No</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Created By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($purchases) > 0): ?>
                                <?php foreach ($purchases as $purchase): ?>
                                    <tr>
                                        <td><?php echo $purchase['id']; ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($purchase['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($purchase['supplier_name']); ?></td>
                                        <td><?php echo htmlspecialchars($purchase['reference_no']); ?></td>
                                        <td><?php echo number_format($purchase['total_amount'], 2); ?></td>
                                        <td>
                                            <span style="padding: 4px 8px; border-radius: 4px; background: #d1fae5; color: #065f46; font-size: 0.875rem;">
                                                <?php echo ucfirst($purchase['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($purchase['created_by_name']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center;">No purchases found.</td>
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
