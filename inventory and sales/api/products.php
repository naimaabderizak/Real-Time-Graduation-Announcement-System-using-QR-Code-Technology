<?php
/**
 * api/products.php
 * 
 * Product API Endpoint
 * 
 * Handles CRUD operations for products.
 * Includes logic for managing stock movements and related data deletion.
 * 
 * Author: System
 * Date: 2026-01-05
 */

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
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
    case 'PUT':
        handlePut($pdo, $input);
        break;
    case 'DELETE':
        handleDelete($pdo, $input);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        break;
}

function handleGet($pdo) {
    try {
        if (isset($_GET['id'])) {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $product]);
        } else {
            $stmt = $pdo->query("SELECT * FROM products ORDER BY name ASC");
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $products]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handlePost($pdo, $input) {
    try {
        if (!isset($input['name'], $input['cost_price'], $input['selling_price'])) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }

        $stmt = $pdo->prepare("INSERT INTO products (name, description, cost_price, selling_price, stock_qty, min_stock_level) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $input['name'],
            $input['description'] ?? '',
            $input['cost_price'],
            $input['selling_price'],
            $input['stock_qty'] ?? 0,
            $input['min_stock_level'] ?? 10
        ]);

        echo json_encode(['success' => true, 'message' => 'Product created successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handlePut($pdo, $input) {
    try {
        if (!isset($input['id'])) {
            echo json_encode(['success' => false, 'message' => 'Product ID required']);
            return;
        }

        $fields = [];
        $params = [];

        if (isset($input['name'])) { $fields[] = "name = ?"; $params[] = $input['name']; }
        if (isset($input['description'])) { $fields[] = "description = ?"; $params[] = $input['description']; }
        if (isset($input['cost_price'])) { $fields[] = "cost_price = ?"; $params[] = $input['cost_price']; }
        if (isset($input['selling_price'])) { $fields[] = "selling_price = ?"; $params[] = $input['selling_price']; }
        if (isset($input['stock_qty'])) { $fields[] = "stock_qty = ?"; $params[] = $input['stock_qty']; }
        if (isset($input['min_stock_level'])) { $fields[] = "min_stock_level = ?"; $params[] = $input['min_stock_level']; }

        if (empty($fields)) {
            echo json_encode(['success' => false, 'message' => 'No fields to update']);
            return;
        }

        $params[] = $input['id'];
        $sql = "UPDATE products SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleDelete($pdo, $input) {
    try {
        // Support ID from URL query param for DELETE too, or body
        $id = $_GET['id'] ?? $input['id'] ?? null;
        
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Product ID required']);
            return;
        }

        $pdo->beginTransaction();

        // Disable Foreign Key Checks temporarily to allow deletion
        $pdo->exec("SET FOREIGN_KEY_CHECKS=0");

        // Delete related records manually to avoid orphans where possible
        // 1. Stock Movements
        $stmt = $pdo->prepare("DELETE FROM stock_movements WHERE product_id = ?");
        $stmt->execute([$id]);

        // 2. Sales Items
        $stmt = $pdo->prepare("DELETE FROM sales_items WHERE product_id = ?");
        $stmt->execute([$id]);

        // 3. Purchase Items
        $stmt = $pdo->prepare("DELETE FROM purchase_items WHERE product_id = ?");
        $stmt->execute([$id]);

        // 4. The Product
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);

        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
        $pdo->commit();

        echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
    } catch (Exception $e) {
        $pdo->rollBack();
        try { $pdo->exec("SET FOREIGN_KEY_CHECKS=1"); } catch (Exception $ex) {}
        echo json_encode(['success' => false, 'message' => 'Error deleting product: ' . $e->getMessage()]);
    }
}
