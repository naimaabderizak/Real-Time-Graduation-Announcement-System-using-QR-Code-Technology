<?php
/**
 * api/login.php
 * 
 * API Authentication Endpoint
 * 
 * Authenticates a user via username and password.
 * Returns a success message and user details if credentials are valid.
 * 
 * Author: System
 * Date: 2026-01-05
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../includes/db.php';

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->username) && !empty($data->password)) {
    $username = htmlspecialchars(strip_tags($data->username));
    $password = $data->password;

    $query = "SELECT id, username, password, role FROM users WHERE username = :username LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":username", $username);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check password (using password_verify as strictly recommended, assuming hashes in DB)
        if (password_verify($password, $row['password'])) {
             http_response_code(200);
             echo json_encode(array(
                "success" => true,
                "message" => "Login successful.",
                "user" => array(
                    "id" => (int)$row['id'], // Ensure int type
                    "username" => $row['username'],
                    "role" => $row['role']
                )
            ));
        } else {
             http_response_code(401);
             echo json_encode(array("success" => false, "message" => "Invalid password."));
        }
    } else {
        http_response_code(401);
        echo json_encode(array("success" => false, "message" => "User not found."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("success" => false, "message" => "Incomplete data."));
}
?>
