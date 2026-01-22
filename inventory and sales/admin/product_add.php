<?php
/**
 * admin/product_add.php
 * 
 * Add New Product
 * 
 * Form to creating a new inventory item.
 * Also handles the initial stock entry by creating a record in `stock_movements`.
 * Restricted to Admin users only.
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

// Access Control: Admin only
if ($_SESSION['role'] !== 'admin') {
    header("Location: products.php?error=Access Denied. Admins only.");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $cost_price = floatval($_POST['cost_price']);
    $selling_price = floatval($_POST['selling_price']);
    $stock_qty = intval($_POST['stock_qty']);
    $min_stock_level = intval($_POST['min_stock_level']);

    if (empty($name)) {
        $error = "Product name is required.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO products (name, description, cost_price, selling_price, stock_qty, min_stock_level) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $cost_price, $selling_price, $stock_qty, $min_stock_level]);
            $success = "Product added successfully!";
            
            // Log initial stock movement if quantity > 0
            if ($stock_qty > 0) {
                $product_id = $pdo->lastInsertId();
                $stmt = $pdo->prepare("INSERT INTO stock_movements (product_id, change_qty, reason, created_by) VALUES (?, ?, 'Initial Stock', ?)");
                $stmt->execute([$product_id, $stock_qty, $_SESSION['user_id']]);
            }
        } catch (PDOException $e) {
            $error = "Error adding product: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Inventory System</title>
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
                <li><a href="../purchases/index.php"><i class="fas fa-shopping-cart"></i> Purchases</a></li>
                <li><a href="../sales/index.php"><i class="fas fa-cash-register"></i> Sales</a></li>
                <li><a href="../reports/index.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1 class="page-title">Add New Product</h1>
                <a href="products.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Back to List</a>
            </div>

            <div class="card" style="max-width: 800px; margin: 0 auto;">
                <?php if ($error): ?>
                    <div class="alert alert-danger" style="background: #fee2e2; color: #b91c1c; padding: 1rem; margin-bottom: 1rem; border-radius: 6px;">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success" style="background: #d1fae5; color: #065f46; padding: 1rem; margin-bottom: 1rem; border-radius: 6px;">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="name" class="form-label">Product Name *</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label for="cost_price" class="form-label">Cost Price</label>
                            <input type="number" id="cost_price" name="cost_price" class="form-control" step="0.01" min="0" value="0.00" required>
                        </div>
                        <div class="form-group">
                            <label for="selling_price" class="form-label">Selling Price</label>
                            <input type="number" id="selling_price" name="selling_price" class="form-control" step="0.01" min="0" value="0.00" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label for="stock_qty" class="form-label">Initial Stock Quantity</label>
                            <input type="number" id="stock_qty" name="stock_qty" class="form-control" min="0" value="0" required>
                        </div>
                        <div class="form-group">
                            <label for="min_stock_level" class="form-label">Minimum Stock Level</label>
                            <input type="number" id="min_stock_level" name="min_stock_level" class="form-control" min="0" value="10" required>
                        </div>
                    </div>

                    <div class="text-right mt-4">
                        <button type="submit" class="btn btn-primary">Save Product</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
