<?php
require_once 'db.php';
try {
    $stmt = $pdo->query("SELECT student_id, full_name, faculty FROM students WHERE is_scanned = 0 LIMIT 5");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        echo "ID: " . $r['student_id'] . " | Name: " . $r['full_name'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
