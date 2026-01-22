<?php
/**
 * api/customers.php
 * 
 * Customer API Endpoint
 * 
 * Handles CRUD operations for customers via JSON.
 * Supported Methods: GET, POST, PUT, DELETE.
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
            $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $customer]);
        } else {
            $stmt = $pdo->query("SELECT * FROM customers ORDER BY name ASC");
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $customers]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handlePost($pdo, $input) {
    try {
        if (!isset($input['name'])) {
            echo json_encode(['success' => false, 'message' => 'Customer Name is required']);
            return;
        }

        $stmt = $pdo->prepare("INSERT INTO customers (name, email, phone) VALUES (?, ?, ?)");
        $stmt->execute([
            $input['name'],
            $input['email'] ?? '',
            $input['phone'] ?? ''
        ]);

        echo json_encode(['success' => true, 'message' => 'Customer created successfully', 'id' => $pdo->lastInsertId()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handlePut($pdo, $input) {
    try {
        if (!isset($input['id'])) {
            echo json_encode(['success' => false, 'message' => 'Customer ID required']);
            return;
        }

        $fields = [];
        $params = [];

        if (isset($input['name'])) { $fields[] = "name = ?"; $params[] = $input['name']; }
        if (isset($input['email'])) { $fields[] = "email = ?"; $params[] = $input['email']; }
        if (isset($input['phone'])) { $fields[] = "phone = ?"; $params[] = $input['phone']; }

        if (empty($fields)) {
            echo json_encode(['success' => false, 'message' => 'No fields to update']);
            return;
        }

        $params[] = $input['id'];
        $sql = "UPDATE customers SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo json_encode(['success' => true, 'message' => 'Customer updated successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function handleDelete($pdo, $input) {
    try {
        $id = $_GET['id'] ?? $input['id'] ?? null;
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Customer ID required']);
            return;
        }

        $pdo->beginTransaction();

        // 1. Unlink from Sales (Set customer_id to NULL to preserve sales history)
        $stmt = $pdo->prepare("UPDATE sales SET customer_id = NULL WHERE customer_id = ?");
        $stmt->execute([$id]);

        // 2. Delete the Customer
        $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
        $stmt->execute([$id]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Customer deleted successfully']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'message' => 'Error deleting customer: ' . $e->getMessage()]);
    }
}
