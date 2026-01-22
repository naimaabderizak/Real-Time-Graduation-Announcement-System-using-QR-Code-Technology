<?php
require_once 'db.php';
try {
    $stmt = $pdo->query("SELECT id, student_id, full_name, faculty, is_scanned, scanned_at FROM students WHERE is_scanned = 1");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Count: " . count($rows) . "\n";
    foreach ($rows as $r) {
        echo "Student: " . $r['full_name'] . " (" . $r['student_id'] . ") - Faculty: [" . $r['faculty'] . "] - Scanned At: " . ($r['scanned_at'] ?? 'NULL') . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
