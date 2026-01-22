<?php
/**
 * api/users.php
 * 
 * User API Endpoint
 * 
 * Handles CRUD operations for system users (Admin/Staff).
 * Includes security checks to prevent users from deleting themselves.
 * 
 * Author: System
 * Date: 2026-01-05
 */

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

session_start();

require_once '../includes/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        $stmt = $pdo->query("SELECT id, username, role, created_at FROM users");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'POST':
        if (empty($input['username']) || empty($input['password'])) {
            echo json_encode(['success' => false, 'message' => 'Username and Password required']);
            return;
        }
        try {
            // Check if username exists first to avoid duplicate entry error 1062
            $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $check->execute([$input['username']]);
            if ($check->fetch()) {
                 echo json_encode(['success' => false, 'message' => 'Username already exists']);
                 return;
            }

            $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $pass = password_hash($input['password'], PASSWORD_DEFAULT); 
            $stmt->execute([$input['username'], $pass, $input['role'] ?? 'staff']);
            echo json_encode(['success' => true, 'message' => 'User created']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'PUT':
        try {
            if (empty($input['id'])) { 
                echo json_encode(['success' => false, 'message' => 'ID required']); 
                exit;
            }
            
            $sql = "UPDATE users SET username = ?, role = ? WHERE id = ?";
            $params = [$input['username'], $input['role'], $input['id']];
            
            if (!empty($input['password'])) {
                $sql = "UPDATE users SET username = ?, role = ?, password = ? WHERE id = ?";
                $params = [$input['username'], $input['role'], password_hash($input['password'], PASSWORD_DEFAULT), $input['id']];
            }

            $stmt = $pdo->prepare($sql);
            if ($stmt->execute($params)) {
                echo json_encode(['success' => true, 'message' => 'User updated']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'DELETE':
        try {
            if (empty($input['id'])) { 
                echo json_encode(['success' => false, 'message' => 'ID required']); 
                exit;
            }
            $id = $input['id'];

            if ($id == $_SESSION['user_id'] ?? 0) {
                 echo json_encode(['success' => false, 'message' => 'Cannot delete yourself']); 
                 exit;
            }
            
            $pdo->beginTransaction();
            $pdo->exec("SET FOREIGN_KEY_CHECKS=0");

            // Unlink user from related records (preserve history, remove user reference)
            $pdo->prepare("UPDATE purchases SET created_by = NULL WHERE created_by = ?")->execute([$id]);
            $pdo->prepare("UPDATE sales SET created_by = NULL WHERE created_by = ?")->execute([$id]);
            $pdo->prepare("UPDATE stock_movements SET created_by = NULL WHERE created_by = ?")->execute([$id]);
            
            // Delete User
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
            $pdo->commit();
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Delete failed']);
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            try { $pdo->exec("SET FOREIGN_KEY_CHECKS=1"); } catch (Exception $ex) {}
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
}
