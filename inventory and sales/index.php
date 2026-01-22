<?php
/**
 * index.php
 * 
 * Dashboard (Home Page)
 * 
 * This is the main landing page of the application after login. 
 * It displays key business metrics (KPIs) such as Total Products, Sales Today, 
 * and Today's Profit. It also provides the main navigation sidebar.
 * 
 * Author: System
 * Date: 2026-01-05
 */

// Start session to manage user login state
session_start();
// Include database connection
require_once 'includes/db.php';

// Authenticate user
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 1. Calculate Total Products
// Query to count all rows in products table
$stmt = $pdo->query("SELECT COUNT(*) FROM products");
$totalProducts = $stmt->fetchColumn();

// 2. Calculate Sales Today
// Query to sum total_amount from sales where created_at date is today
$stmt = $pdo->prepare("SELECT SUM(total_amount) FROM sales WHERE DATE(created_at) = CURDATE()");
$stmt->execute();
$salesToday = $stmt->fetchColumn() ?: 0; // Default to 0 if null

// 3. Calculate Profit Today
// Profit = (Selling Price - Cost Price) * Quantity
// Note: Using current cost price from products table which might not be historically accurate but suffices for simple estimation
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Inventory System</title>
    <link rel="stylesheet" href="css/style.css">
    <!-- FontAwesome for icons (optional but recommended for premium look) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar Navigation -->
        <!-- Contains links to all modules of the system -->
        <nav class="sidebar">
            <div class="sidebar-header">
                Inventory Sys
            </div>
            <ul class="sidebar-menu">
                <li><a href="index.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="admin/products.php"><i class="fas fa-box"></i> Products</a></li>
                <li><a href="admin/suppliers.php"><i class="fas fa-truck"></i> Suppliers</a></li>
                <li><a href="admin/customers.php"><i class="fas fa-users"></i> Customers</a></li>
                <li><a href="purchases/index.php"><i class="fas fa-shopping-cart"></i> Purchases</a></li>
                <li><a href="sales/index.php"><i class="fas fa-cash-register"></i> Sales</a></li>
                <li><a href="reports/index.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="admin/users.php"><i class="fas fa-user-cog"></i> Users</a></li>
                <li><a href="team.php"><i class="fas fa-users-cog"></i> Our Team</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1 class="page-title">Dashboard</h1>
                <div class="user-info">
                    <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></strong></span>
                </div>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger" style="background: #fee2e2; color: #b91c1c; padding: 1rem; margin-bottom: 2rem; border-radius: 6px;">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <h2>Welcome to the Inventory & Sales Management System</h2>
                <p class="mt-4">Use the sidebar to navigate through the system.</p>
            </div>
            
            <!-- Quick Stats (Placeholder) -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
                <div class="card" style="border-left: 4px solid var(--primary-color);">
                    <h3>Products</h3>
                    <p style="font-size: 2rem; font-weight: bold;"><?php echo number_format($totalProducts); ?></p>
                </div>
                <div class="card" style="border-left: 4px solid var(--secondary-color);">
                    <h3>Sales Today</h3>
                    <p style="font-size: 2rem; font-weight: bold;">$<?php echo number_format($salesToday, 2); ?></p>
                </div>
                <div class="card" style="border-left: 4px solid var(--warning-color);">
                    <h3>Today's Profit</h3>
                    <p style="font-size: 2rem; font-weight: bold; color: <?php echo ($profitToday >= 0) ? 'green' : 'red'; ?>;">
                        $<?php echo number_format($profitToday, 2); ?>
                    </p>
                </div>
            </div>

            <!-- System Console (Replacing debug_me.php) -->
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <div class="card" style="margin-top: 30px; background: #f8fafc; border: 1px dashed #cbd5e1;">
                <h3 style="color: #64748b;"><i class="fas fa-terminal"></i> System Console</h3>
                <div style="font-family: monospace; font-size: 0.85rem; margin-top: 10px; color: #475569;">
                    <p><strong>Session ID:</strong> <?php echo session_id(); ?></p>
                    <p><strong>User:</strong> <?php echo $_SESSION['username']; ?> (ID: <?php echo $_SESSION['user_id']; ?>)</p>
                    <p><strong>Role:</strong> <?php echo $_SESSION['role']; ?></p>
                    <p><strong>Environment:</strong> Apache/8080 (XAMPP)</p>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
