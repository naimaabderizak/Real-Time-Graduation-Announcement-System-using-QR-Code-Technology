<?php
/**
 * purchases/create.php
 * 
 * Create Purchase Order
 * 
 * Form to creating a new purchase order.
 * Allows selecting a supplier and adding multiple products with quantities and costs.
 * Updates stock levels upon submission.
 * 
 * Author: System
 * Date: 2026-01-05
 */

// Start the session to enforce user authentication
session_start();
// Include database connection
require_once '../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login if not authenticated
    header("Location: ../login.php");
    exit;
}

// Fetch all suppliers from the database to populate the dropdown
$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY name ASC")->fetchAll();
// Fetch all products to populate the product dropdowns
$products = $pdo->query("SELECT * FROM products ORDER BY name ASC")->fetchAll();

// Initialize error and success message variables
$error = '';
$success = '';

// Check if the form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve form data
    $supplier_id = $_POST['supplier_id'];
    $reference_no = $_POST['reference_no'];
    $product_ids = $_POST['product_id']; // Array of product IDs
    $quantities = $_POST['quantity']; // Array of quantities
    $unit_costs = $_POST['unit_cost']; // Array of unit costs

    // Validate that a supplier and at least one product are selected
    if (empty($supplier_id) || empty($product_ids)) {
        $error = "Please select a supplier and at least one product.";
    } else {
        try {
            // Start a database transaction
            $pdo->beginTransaction();

            // 1. Create Purchase Order Record
            $total_amount = 0;
            // Calculate total amount for the purchase
            for ($i = 0; $i < count($product_ids); $i++) {
                $total_amount += $quantities[$i] * $unit_costs[$i];
            }

            // Insert into purchases table
            $stmt = $pdo->prepare("INSERT INTO purchases (supplier_id, reference_no, total_amount, status, created_by) VALUES (?, ?, ?, 'received', ?)");
            $stmt->execute([$supplier_id, $reference_no, $total_amount, $_SESSION['user_id']]);
            // Get the ID of the new purchase
            $purchase_id = $pdo->lastInsertId();

            // 2. Process Items and Update Stock Levels
            $stmt_item = $pdo->prepare("INSERT INTO purchase_items (purchase_id, product_id, quantity, unit_cost, total_cost) VALUES (?, ?, ?, ?, ?)");
            // Prepare statement to update product stock quantity and cost price
            $stmt_stock = $pdo->prepare("UPDATE products SET stock_qty = stock_qty + ?, cost_price = ? WHERE id = ?");
            // Prepare statement to log the stock movement
            $stmt_move = $pdo->prepare("INSERT INTO stock_movements (product_id, change_qty, reason, created_by) VALUES (?, ?, ?, ?)");

            // Loop through each product item
            for ($i = 0; $i < count($product_ids); $i++) {
                $pid = $product_ids[$i];
                $qty = $quantities[$i];
                $cost = $unit_costs[$i];
                $line_total = $qty * $cost;

                // Only process if quantity is valid
                if ($qty > 0) {
                    // Insert Purchase Item
                    $stmt_item->execute([$purchase_id, $pid, $qty, $cost, $line_total]);

                    // Update Product Stock & Cost Price
                    $stmt_stock->execute([$qty, $cost, $pid]);

                    // Log Stock Movement
                    $stmt_move->execute([$pid, $qty, "Purchase #$purchase_id", $_SESSION['user_id']]);
                }
            }

            // Commit the transaction
            $pdo->commit();
            $success = "Purchase order created and stock updated successfully!";
        } catch (PDOException $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            $error = "Error creating purchase: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Purchase - Inventory System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        function addItem() {
            const container = document.getElementById('items-container');
            const row = document.createElement('div');
            row.className = 'item-row';
            row.style.display = 'grid';
            row.style.gridTemplateColumns = '3fr 1fr 1fr 1fr auto';
            row.style.gap = '10px';
            row.style.marginBottom = '10px';
            
            row.innerHTML = `
                <select name="product_id[]" class="form-control" required>
                    <option value="">Select Product</option>
                    <?php foreach ($products as $p): ?>
                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="quantity[]" class="form-control" placeholder="Qty" min="1" required onchange="calculateTotal()">
                <input type="number" name="unit_cost[]" class="form-control" placeholder="Cost" step="0.01" min="0" required onchange="calculateTotal()">
                <input type="text" class="form-control line-total" placeholder="Total" readonly>
                <button type="button" class="btn btn-danger" onclick="this.parentElement.remove(); calculateTotal()"><i class="fas fa-trash"></i></button>
            `;
            container.appendChild(row);
        }

        function calculateTotal() {
            let total = 0;
            const rows = document.querySelectorAll('.item-row');
            rows.forEach(row => {
                const qty = parseFloat(row.querySelector('input[name="quantity[]"]').value) || 0;
                const cost = parseFloat(row.querySelector('input[name="unit_cost[]"]').value) || 0;
                const lineTotal = qty * cost;
                row.querySelector('.line-total').value = lineTotal.toFixed(2);
                total += lineTotal;
            });
            document.getElementById('grand-total').innerText = total.toFixed(2);
        }
    </script>
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
                <li><a href="index.php" class="active"><i class="fas fa-shopping-cart"></i> Purchases</a></li>
                <li><a href="../sales/index.php"><i class="fas fa-cash-register"></i> Sales</a></li>
                <li><a href="../reports/index.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1 class="page-title">Receive Stock (Purchase Order)</h1>
                <a href="index.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Back to List</a>
            </div>

            <div class="card">
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
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div class="form-group">
                            <label for="supplier_id" class="form-label">Supplier *</label>
                            <select id="supplier_id" name="supplier_id" class="form-control" required>
                                <option value="">Select Supplier</option>
                                <?php foreach ($suppliers as $s): ?>
                                    <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="reference_no" class="form-label">Reference No (Optional)</label>
                            <input type="text" id="reference_no" name="reference_no" class="form-control">
                        </div>
                    </div>

                    <h3 class="mb-4">Items</h3>
                    <div id="items-container">
                        <!-- Items will be added here -->
                    </div>
                    
                    <button type="button" class="btn btn-success mb-4" onclick="addItem()"><i class="fas fa-plus"></i> Add Item</button>

                    <div style="text-align: right; font-size: 1.2rem; font-weight: bold; margin-bottom: 20px;">
                        Grand Total: <span id="grand-total">0.00</span>
                    </div>

                    <div class="text-right">
                        <button type="submit" class="btn btn-primary">Save Purchase & Update Stock</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
    <script>
        // Add one empty row by default
        addItem();
    </script>
</body>
</html>
