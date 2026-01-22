<?php
require_once 'db.php';
try {
    $stmt = $pdo->prepare("UPDATE students SET is_scanned = 0, scanned_at = NULL");
    $stmt->execute();
    echo "Success: All students have been reset to 'Remaining' status.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
