<?php
/**
 * api/suppliers.php
 * 
 * Supplier API Endpoint
 * 
 * Handles CRUD operations for suppliers via JSON.
 * 
 * Author: System
 * Date: 2026-01-05
 */

// Set the content type of the response to JSON
header("Content-Type: application/json");
// Allow requests from any origin (CORS)
header("Access-Control-Allow-Origin: *");
// Allow specific HTTP methods: GET, POST, PUT, DELETE
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
// Allow specific headers in the request
header("Access-Control-Allow-Headers: Content-Type");

// Include the database connection file
require_once '../includes/db.php';

// Get the HTTP request method used (GET, POST, PUT, DELETE)
$method = $_SERVER['REQUEST_METHOD'];
// Decode the JSON input from the request body
$input = json_decode(file_get_contents('php://input'), true);

// Switch statement to handle different HTTP methods
switch ($method) {
    case 'GET':
        // Handle GET requests (retrieve data)
        handleGet($pdo);
        break;
    case 'POST':
        // Handle POST requests (create data)
        handlePost($pdo, $input);
        break;
    case 'PUT':
        // Handle PUT requests (update data)
        handlePut($pdo, $input);
        break;
    case 'DELETE':
        // Handle DELETE requests (delete data)
        handleDelete($pdo, $input);
        break;
    default:
        // Return an error for unsupported request methods
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        break;
}

// Function to handle GET requests
function handleGet($pdo) {
    try {
        // Check if a specific ID is provided in the query string
        if (isset($_GET['id'])) {
            // Prepare a SQL statement to select a supplier by ID
            $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
            // Execute the statement with the provided ID
            $stmt->execute([$_GET['id']]);
            // Fetch the result as an associative array
            $supplier = $stmt->fetch(PDO::FETCH_ASSOC);
            // Return the supplier data as JSON
            echo json_encode(['success' => true, 'data' => $supplier]);
        } else {
            // If no ID is provided, select all suppliers ordered by name
            $stmt = $pdo->query("SELECT * FROM suppliers ORDER BY name ASC");
            // Fetch all results as an associative array
            $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Return the list of suppliers as JSON
            echo json_encode(['success' => true, 'data' => $suppliers]);
        }
    } catch (Exception $e) {
        // Handle any exceptions and return the error message
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Function to handle POST requests
function handlePost($pdo, $input) {
    try {
        // Validate that the supplier name is provided
        if (!isset($input['name'])) {
            // Return an error if the name is missing
            echo json_encode(['success' => false, 'message' => 'Supplier Name is required']);
            return;
        }

        // Prepare a SQL statement to insert a new supplier
        $stmt = $pdo->prepare("INSERT INTO suppliers (name, contact_person, email, phone, address) VALUES (?, ?, ?, ?, ?)");
        // Execute the statement with the input data, using empty strings for optional fields if not provided
        $stmt->execute([
            $input['name'],
            $input['contact_person'] ?? '',
            $input['email'] ?? '',
            $input['phone'] ?? '',
            $input['address'] ?? ''
        ]);

        // Return a success message and the ID of the newly created supplier
        echo json_encode(['success' => true, 'message' => 'Supplier created successfully', 'id' => $pdo->lastInsertId()]);
    } catch (Exception $e) {
        // Handle any exceptions and return the error message
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Function to handle PUT requests
function handlePut($pdo, $input) {
    try {
        // Validate that the supplier ID is provided
        if (!isset($input['id'])) {
            // Return an error if the ID is missing
            echo json_encode(['success' => false, 'message' => 'Supplier ID required']);
            return;
        }

        // Initialize arrays to hold the fields to update and their values
        $fields = [];
        $params = [];

        // Check if each field is set in the input and add it to the update list if so
        if (isset($input['name'])) { $fields[] = "name = ?"; $params[] = $input['name']; }
        if (isset($input['contact_person'])) { $fields[] = "contact_person = ?"; $params[] = $input['contact_person']; }
        if (isset($input['email'])) { $fields[] = "email = ?"; $params[] = $input['email']; }
        if (isset($input['phone'])) { $fields[] = "phone = ?"; $params[] = $input['phone']; }
        if (isset($input['address'])) { $fields[] = "address = ?"; $params[] = $input['address']; }

        // If no fields are provided to update, return an error
        if (empty($fields)) {
            echo json_encode(['success' => false, 'message' => 'No fields to update']);
            return;
        }

        // Add the ID to the parameters for the WHERE clause
        $params[] = $input['id'];
        // Construct the SQL UPDATE statement
        $sql = "UPDATE suppliers SET " . implode(", ", $fields) . " WHERE id = ?";
        // Prepare the SQL statement
        $stmt = $pdo->prepare($sql);
        // Execute the statement with the parameters
        $stmt->execute($params);

        // Return a success message
        echo json_encode(['success' => true, 'message' => 'Supplier updated successfully']);
    } catch (Exception $e) {
        // Handle any exceptions and return the error message
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Function to handle DELETE requests
function handleDelete($pdo, $input) {
    try {
        // Get the ID from the query string or the input body
        $id = $_GET['id'] ?? $input['id'] ?? null;
        // Validate that the ID is provided
        if (!$id) {
            // Return an error if the ID is missing
            echo json_encode(['success' => false, 'message' => 'Supplier ID required']);
            return;
        }

        // Prepare a SQL statement to delete the supplier
        $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
        // Execute the statement with the ID
        $stmt->execute([$id]);

        // Return a success message
        echo json_encode(['success' => true, 'message' => 'Supplier deleted successfully']);
    } catch (Exception $e) {
        // Handle any exceptions (e.g., foreign key constraints) and return an error message
        echo json_encode(['success' => false, 'message' => 'Cannot delete supplier: linked data exists.']);
    }
}
?>
