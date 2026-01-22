<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: students.php?msg=Student_deleted");
    } catch (PDOException $e) {
        header("Location: students.php?msg=Error_occurred");
    }
} else {
    header("Location: students.php");
}
exit();
?>
