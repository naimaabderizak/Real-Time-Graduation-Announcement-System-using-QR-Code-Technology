<?php
/**
 * admin/products.php
 * 
 * Product Management Dashboard
 * 
 * Displays the main inventory list. Shows current stock levels, prices, and status.
 * Visual indicators (red text) highlight low stock items based on min_stock_level.
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

// Fetch products
$stmt = $pdo->query("SELECT * FROM products ORDER BY name ASC");
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Inventory System</title>
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
                <li><a href="products.php" class="active"><i class="fas fa-box"></i> Products</a></li>
                <li><a href="suppliers.php"><i class="fas fa-truck"></i> Suppliers</a></li>
                <li><a href="customers.php"><i class="fas fa-users"></i> Customers</a></li>
                <li><a href="../purchases/index.php"><i class="fas fa-shopping-cart"></i> Purchases</a></li>
                <li><a href="../sales/index.php"><i class="fas fa-cash-register"></i> Sales</a></li>
                <li><a href="../reports/index.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="users.php"><i class="fas fa-user-cog"></i> Users</a></li>
                <li><a href="../team.php"><i class="fas fa-users-cog"></i> Our Team</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1 class="page-title">Product Management</h1>
                <div style="display: flex; gap: 10px;">
                    <button onclick="window.print()" class="btn btn-secondary btn-print"><i class="fas fa-print"></i> Print</button>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <a href="product_add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Product</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <?php if (isset($_SESSION['flash_success'])): ?>
                    <div class="alert alert-success" style="background: #d1fae5; color: #065f46; padding: 1rem; margin-bottom: 1rem; border-radius: 6px;">
                        <?php echo $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['flash_error'])): ?>
                    <div class="alert alert-danger" style="background: #fee2e2; color: #b91c1c; padding: 1rem; margin-bottom: 1rem; border-radius: 6px;">
                        <?php echo $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?>
                    </div>
                <?php endif; ?>

                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="no-print" style="width: 40px;"><input type="checkbox" id="selectAll"></th>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Cost Price</th>
                                <th>Selling Price</th>
                                <th>Stock Qty</th>
                                <th>Min Stock</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($products) > 0): ?>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td class="no-print"><input type="checkbox" class="row-checkbox"></td>
                                        <td><?php echo $product['id']; ?></td>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['description']); ?></td>
                                        <td><?php echo number_format($product['cost_price'], 2); ?></td>
                                        <td><?php echo number_format($product['selling_price'], 2); ?></td>
                                        <td>
                                            <?php 
                                            $stockClass = ($product['stock_qty'] <= $product['min_stock_level']) ? 'text-danger' : '';
                                            echo "<span class='$stockClass'>" . $product['stock_qty'] . "</span>";
                                            ?>
                                        </td>
                                        <td><?php echo $product['min_stock_level']; ?></td>
                                        <td>
                                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                                <a href="product_edit.php?id=<?php echo $product['id']; ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 0.8rem;"><i class="fas fa-edit"></i></a>
                                                <form method="POST" action="product_delete.php" style="display:inline;" onsubmit="return confirm('Are you sure? This will delete all related history.');">
                                                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                                    <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem;"><i class="fas fa-trash"></i></button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center;">No products found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <style>
        .text-danger { color: var(--danger-color); font-weight: bold; }
    </style>
    <script src="../js/main.js"></script>
</body>
</html>
