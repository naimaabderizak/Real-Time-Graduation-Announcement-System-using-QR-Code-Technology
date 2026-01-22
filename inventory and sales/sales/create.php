<?php
/**
 * sales/create.php
 * 
 * Create Sales Invoice
 * 
 * Point of Sale (POS) interface.
 * Allows selecting a customer and adding multiple products using dynamic JavaScript.
 * Validates stock availability before processing.
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
    header("Location: index.php?error=Access Denied. Admins only.");
    exit;
}

// Fetch customers and products for dropdowns
$customers = $pdo->query("SELECT * FROM customers ORDER BY name ASC")->fetchAll();
$products = $pdo->query("SELECT * FROM products WHERE stock_qty > 0 ORDER BY name ASC")->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_id = !empty($_POST['customer_id']) ? $_POST['customer_id'] : null;
    $invoice_no = 'INV-' . time(); // Simple auto-gen
    $product_ids = $_POST['product_id']; // Array
    $quantities = $_POST['quantity']; // Array
    
    if (empty($product_ids)) {
        $error = "Please select at least one product.";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Calculate Total & Verify Stock
            $total_amount = 0;
            $items_to_process = [];

            for ($i = 0; $i < count($product_ids); $i++) {
                $pid = $product_ids[$i];
                $qty = $quantities[$i];
                
                if ($qty > 0) {
                    // Get current price and stock
                    $stmt_p = $pdo->prepare("SELECT selling_price, stock_qty FROM products WHERE id = ?");
                    $stmt_p->execute([$pid]);
                    $prod = $stmt_p->fetch();
                    
                    if ($prod['stock_qty'] < $qty) {
                        throw new Exception("Insufficient stock for product ID: $pid. Available: " . $prod['stock_qty']);
                    }
                    
                    $price = $prod['selling_price'];
                    $line_total = $qty * $price;
                    $total_amount += $line_total;
                    
                    $items_to_process[] = [
                        'pid' => $pid,
                        'qty' => $qty,
                        'price' => $price,
                        'total' => $line_total
                    ];
                }
            }

            // 2. Create Sale Record
            $stmt = $pdo->prepare("INSERT INTO sales (customer_id, invoice_no, total_amount, created_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$customer_id, $invoice_no, $total_amount, $_SESSION['user_id']]);
            $sale_id = $pdo->lastInsertId();

            // 3. Process Items and Update Stock
            $stmt_item = $pdo->prepare("INSERT INTO sales_items (sale_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
            $stmt_stock = $pdo->prepare("UPDATE products SET stock_qty = stock_qty - ? WHERE id = ?");
            $stmt_move = $pdo->prepare("INSERT INTO stock_movements (product_id, change_qty, reason, created_by) VALUES (?, ?, ?, ?)");

            foreach ($items_to_process as $item) {
                // Insert Item
                $stmt_item->execute([$sale_id, $item['pid'], $item['qty'], $item['price'], $item['total']]);

                // Update Product Stock (Decrement)
                $stmt_stock->execute([$item['qty'], $item['pid']]);

                // Log Movement (Negative quantity for stock out)
                $stmt_move->execute([$item['pid'], -$item['qty'], "Sale #$sale_id", $_SESSION['user_id']]);
            }

            $pdo->commit();
            $success = "Sale recorded successfully! Invoice: $invoice_no";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error recording sale: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Sale - Inventory System</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        // Pass PHP products to JS
        const products = <?php echo json_encode($products); ?>;

        function addItem() {
            const container = document.getElementById('items-container');
            const row = document.createElement('div');
            row.className = 'item-row';
            row.style.display = 'grid';
            row.style.gridTemplateColumns = '3fr 1fr 1fr 1fr auto';
            row.style.gap = '10px';
            row.style.marginBottom = '10px';
            
            let options = '<option value="">Select Product</option>';
            products.forEach(p => {
                options += `<option value="${p.id}" data-price="${p.selling_price}" data-stock="${p.stock_qty}">${p.name} (Stock: ${p.stock_qty})</option>`;
            });

            row.innerHTML = `
                <select name="product_id[]" class="form-control" required onchange="updatePrice(this)">
                    ${options}
                </select>
                <input type="number" name="quantity[]" class="form-control" placeholder="Qty" min="1" required onchange="calculateTotal()">
                <input type="number" name="unit_price[]" class="form-control" placeholder="Price" step="0.01" readonly>
                <input type="text" class="form-control line-total" placeholder="Total" readonly>
                <button type="button" class="btn btn-danger" onclick="this.parentElement.remove(); calculateTotal()"><i class="fas fa-trash"></i></button>
            `;
            container.appendChild(row);
        }

        function updatePrice(select) {
            const option = select.options[select.selectedIndex];
            const price = option.getAttribute('data-price');
            const row = select.parentElement;
            row.querySelector('input[name="unit_price[]"]').value = price;
            calculateTotal();
        }

        function calculateTotal() {
            let total = 0;
            const rows = document.querySelectorAll('.item-row');
            rows.forEach(row => {
                const qty = parseFloat(row.querySelector('input[name="quantity[]"]').value) || 0;
                const price = parseFloat(row.querySelector('input[name="unit_price[]"]').value) || 0;
                const lineTotal = qty * price;
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
                <li><a href="index.php" class="active"><i class="fas fa-cash-register"></i> Sales</a></li>
                <li><a href="../reports/index.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header">
                <h1 class="page-title">New Sales Invoice</h1>
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
                    <div class="form-group" style="max-width: 400px; margin-bottom: 20px;">
                        <label for="customer_id" class="form-label">Customer</label>
                        <select id="customer_id" name="customer_id" class="form-control">
                            <option value="">Walk-in Customer</option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
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
                        <button type="submit" class="btn btn-primary">Complete Sale</button>
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
