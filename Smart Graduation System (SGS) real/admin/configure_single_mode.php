<?php
require_once 'db.php';

try {
    $stmt = $pdo->prepare("UPDATE settings SET setting_value = '1' WHERE setting_key = 'layout_mode'");
    $stmt->execute();
    echo "Layout mode updated to Single Card successfully.\n";
} catch (PDOException $e) {
    echo "Error updating layout mode: " . $e->getMessage() . "\n";
}
?>
