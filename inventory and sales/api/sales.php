<?php
/**
 * api/sales.php
 * 
 * Sales API Endpoint
 * 
 * Handles creation and retrieval of sales records (POS).
 * Automatically updates stock quantities and records stock movements.
 * 
 * Author: System
 * Date: 2026-01-05
 */

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../includes/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        handleGet($pdo);
        break;
    case 'POST':
        handlePost($pdo, $input);
        break;
    case 'DELETE':
        handleDelete($pdo, $input);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        break;
}

function handleDelete($pdo, $input) {
    try {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Sale ID required']);
            return;
        }

        $pdo->beginTransaction();

        // 1. Get items to reverse stock
        $stmt_items = $pdo->prepare("SELECT product_id, quantity FROM sales_items WHERE sale_id = ?");
        $stmt_items->execute([$id]);
        $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

        $stmt_stock = $pdo->prepare("UPDATE products SET stock_qty = stock_qty + ? WHERE id = ?");
        $stmt_move = $pdo->prepare("INSERT INTO stock_movements (product_id, change_qty, reason, created_by) VALUES (?, ?, ?, ?)");

        $user_id = $input['user_id'] ?? 1;

        foreach ($items as $item) {
            $stmt_stock->execute([$item['quantity'], $item['product_id']]);
            $stmt_move->execute([$item['product_id'], $item['quantity'], "Void Sale #$id", $user_id]);
        }

        // 2. Delete Sale (Cascades to items)
        $stmt_del = $pdo->prepare("DELETE FROM sales WHERE id = ?");
        $stmt_del->execute([$id]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Sale voided successfully']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleGet($pdo) {
    try {
        if (isset($_GET['id'])) {
            $stmt = $pdo->prepare("SELECT s.*, c.name as customer_name, u.username as created_by_name 
                                   FROM sales s 
                                   LEFT JOIN customers c ON s.customer_id = c.id 
                                   LEFT JOIN users u ON s.created_by = u.id 
                                   WHERE s.id = ?");
            $stmt->execute([$_GET['id']]);
            $sale = $stmt->fetch(PDO::FETCH_ASSOC);

            // Fetch items
            $stmtItems = $pdo->prepare("SELECT si.*, p.name as product_name 
                                        FROM sales_items si 
                                        JOIN products p ON si.product_id = p.id 
                                        WHERE si.sale_id = ?");
            $stmtItems->execute([$sale['id']]);
            $sale['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'data' => $sale]);
        } else {
            $stmt = $pdo->query("SELECT s.*, c.name as customer_name 
                                 FROM sales s 
                                 LEFT JOIN customers c ON s.customer_id = c.id 
                                 ORDER BY s.created_at DESC");
            $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $sales]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handlePost($pdo, $input) {
    try {
        if (empty($input['items'])) {
            echo json_encode(['success' => false, 'message' => 'No items in sale']);
            return;
        }
        
        // Use a default user ID if session not set (for API testing) or fetch from input
        // In real app, user_id should come from token or hidden field
        $user_id = $input['user_id'] ?? 1; // Fallback to admin if not sent

        $customer_id = !empty($input['customer_id']) ? $input['customer_id'] : null;
        $invoice_no = 'INV-' . time();
        $items = $input['items'];

        $pdo->beginTransaction();

        $total_amount = 0;
        $items_to_process = [];

        // 1. Validate Stock & Calculate
        foreach ($items as $item) {
            $pid = $item['product_id'];
            $qty = $item['quantity'];

            if ($qty > 0) {
                $stmt_p = $pdo->prepare("SELECT selling_price, stock_qty FROM products WHERE id = ?");
                $stmt_p->execute([$pid]);
                $prod = $stmt_p->fetch(PDO::FETCH_ASSOC);

                if (!$prod) {
                    throw new Exception("Product ID $pid not found");
                }

                if ($prod['stock_qty'] < $qty) {
                    throw new Exception("Insufficient stock for Product ID $pid. Available: " . $prod['stock_qty']);
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

        // 2. Create Sale
        $stmt = $pdo->prepare("INSERT INTO sales (customer_id, invoice_no, total_amount, created_by) VALUES (?, ?, ?, ?)");
        $stmt->execute([$customer_id, $invoice_no, $total_amount, $user_id]);
        $sale_id = $pdo->lastInsertId();

        // 3. Process Items
        $stmt_item = $pdo->prepare("INSERT INTO sales_items (sale_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
        $stmt_stock = $pdo->prepare("UPDATE products SET stock_qty = stock_qty - ? WHERE id = ?");
        $stmt_move = $pdo->prepare("INSERT INTO stock_movements (product_id, change_qty, reason, created_by) VALUES (?, ?, ?, ?)");

        foreach ($items_to_process as $item) {
            // Insert Item
            $stmt_item->execute([$sale_id, $item['pid'], $item['qty'], $item['price'], $item['total']]);

            // Update Stock
            $stmt_stock->execute([$item['qty'], $item['pid']]);

            // Log Movement
            $stmt_move->execute([$item['pid'], -$item['qty'], "Sale #$sale_id", $user_id]);
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Sale recorded successfully', 'invoice_no' => $invoice_no]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
