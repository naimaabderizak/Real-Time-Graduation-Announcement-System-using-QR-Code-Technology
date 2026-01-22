<?php
require_once 'db.php';

$new_settings = [
    'dept_font_size' => '1.0rem',
    'name_text_color' => '#ffffff',
    'faculty_text_color' => '#ffffff',
    'dept_text_color' => '#ffffff',
    'label_text_color' => '#C5A047',
    'label_font_weight' => '800'
];

foreach ($new_settings as $key => $value) {
    // Check if exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    if ($stmt->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->execute([$key, $value]);
        echo "Added setting: $key\n";
    } else {
        echo "Setting already exists: $key\n";
    }
}
?>
