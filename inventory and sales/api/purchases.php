<?php
/**
 * api/purchases.php
 * 
 * Purchase API Endpoint
 * 
 * Handles creation and retrieval of purchase records.
 * Automatically updates product stock quantities upon purchase creation.
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
            echo json_encode(['success' => false, 'message' => 'Purchase ID required']);
            return;
        }

        $pdo->beginTransaction();

        // 1. Get items to reduce stock
        $stmt_items = $pdo->prepare("SELECT product_id, quantity FROM purchase_items WHERE purchase_id = ?");
        $stmt_items->execute([$id]);
        $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

        $stmt_stock = $pdo->prepare("UPDATE products SET stock_qty = stock_qty - ? WHERE id = ?");
        $stmt_move = $pdo->prepare("INSERT INTO stock_movements (product_id, change_qty, reason, created_by) VALUES (?, ?, ?, ?)");

        $user_id = $input['user_id'] ?? 1;

        foreach ($items as $item) {
            $stmt_stock->execute([$item['quantity'], $item['product_id']]);
            $stmt_move->execute([$item['product_id'], -$item['quantity'], "Return Purchase #$id", $user_id]);
        }

        // 2. Delete Purchase (Cascades to items)
        $stmt_del = $pdo->prepare("DELETE FROM purchases WHERE id = ?");
        $stmt_del->execute([$id]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Purchase returned and removed successfully']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleGet($pdo) {
    try {
        if (isset($_GET['id'])) {
            $stmt = $pdo->prepare("SELECT p.*, s.name as supplier_name, u.username as created_by_name 
                                   FROM purchases p 
                                   LEFT JOIN suppliers s ON p.supplier_id = s.id 
                                   LEFT JOIN users u ON p.created_by = u.id 
                                   WHERE p.id = ?");
            $stmt->execute([$_GET['id']]);
            $purchase = $stmt->fetch(PDO::FETCH_ASSOC);

            // Fetch items
            $stmtItems = $pdo->prepare("SELECT pi.*, pr.name as product_name 
                                        FROM purchase_items pi 
                                        JOIN products pr ON pi.product_id = pr.id 
                                        WHERE pi.purchase_id = ?");
            $stmtItems->execute([$purchase['id']]);
            $purchase['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'data' => $purchase]);
        } else {
            $stmt = $pdo->query("SELECT p.*, s.name as supplier_name 
                                 FROM purchases p 
                                 LEFT JOIN suppliers s ON p.supplier_id = s.id 
                                 ORDER BY p.created_at DESC");
            $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $purchases]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handlePost($pdo, $input) {
    try {
        if (empty($input['items'])) {
            echo json_encode(['success' => false, 'message' => 'No items in purchase']);
            return;
        }

        $user_id = $input['user_id'] ?? 1;
        $supplier_id = !empty($input['supplier_id']) ? $input['supplier_id'] : null;
        $invoice_no = 'PUR-' . time();
        $items = $input['items'];

        $pdo->beginTransaction();

        $total_amount = 0;
        $items_to_process = [];

        foreach ($items as $item) {
            $pid = $item['product_id'];
            $qty = $item['quantity'];
            $cost = $item['unit_cost']; // Cost price from input

            if ($qty > 0) {
                $line_total = $qty * $cost;
                $total_amount += $line_total;

                $items_to_process[] = [
                    'pid' => $pid,
                    'qty' => $qty,
                    'cost' => $cost,
                    'total' => $line_total
                ];
            }
        }

        // Create Purchase
        $stmt = $pdo->prepare("INSERT INTO purchases (supplier_id, reference_no, total_amount, created_by) VALUES (?, ?, ?, ?)");
        $stmt->execute([$supplier_id, $invoice_no, $total_amount, $user_id]);
        $purchase_id = $pdo->lastInsertId();

        // Process Items
        $stmt_item = $pdo->prepare("INSERT INTO purchase_items (purchase_id, product_id, quantity, unit_cost, total_cost) VALUES (?, ?, ?, ?, ?)");
        $stmt_stock = $pdo->prepare("UPDATE products SET stock_qty = stock_qty + ?, cost_price = ? WHERE id = ?");
        $stmt_move = $pdo->prepare("INSERT INTO stock_movements (product_id, change_qty, reason, created_by) VALUES (?, ?, ?, ?)");

        foreach ($items_to_process as $item) {
            // Insert Item
            $stmt_item->execute([$purchase_id, $item['pid'], $item['qty'], $item['cost'], $item['total']]);

            // Update Stock & Cost Price
            // Note: Updating cost price to latest purchase price is a simple strategy. 
            // Weighted average would be better but keeping it simple for now.
            $stmt_stock->execute([$item['qty'], $item['cost'], $item['pid']]);

            // Log Movement
            $stmt_move->execute([$item['pid'], $item['qty'], "Purchase #$purchase_id", $user_id]);
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Purchase recorded successfully', 'invoice_no' => $invoice_no]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
