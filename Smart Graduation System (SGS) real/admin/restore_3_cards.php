<?php
require_once 'db.php';

try {
    $stmt = $pdo->prepare("UPDATE settings SET setting_value = '3' WHERE setting_key = 'layout_mode'");
    $stmt->execute();
    echo "Layout mode restored to 3-Cards successfully.\n";
} catch (PDOException $e) {
    echo "Error updating layout mode: " . $e->getMessage() . "\n";
}
?>
