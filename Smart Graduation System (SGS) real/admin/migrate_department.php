<?php
require_once 'db.php';

try {
    $pdo->exec("ALTER TABLE students ADD COLUMN department VARCHAR(255) DEFAULT '' AFTER faculty");
    echo "Column 'department' added successfully!";
} catch (PDOException $e) {
    if ($e->getCode() == '42S21') {
        echo "Column already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>
