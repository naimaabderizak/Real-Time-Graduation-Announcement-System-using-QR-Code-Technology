<?php
require_once 'db.php';

$new_settings = [
    'label_name_text' => 'NAME:',
    'label_faculty_text' => 'FACULTY:',
    'label_dept_text' => 'DEPT:',
    'font_family' => 'Inter',
    'animation_enabled' => '1',
    'speech_template' => '{name}. Faculty of {faculty}. Department of {dept}.',
    'layout_mode' => 'grid' // Options: 'grid' (3 cards), 'single' (1 card focused)
];

echo "Adding new settings...\n";

foreach ($new_settings as $key => $val) {
    // Check if exists first to avoid resetting if run multiple times (though INSERT IGNORE handles PK collision)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    if ($stmt->fetchColumn() == 0) {
        $insert = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $insert->execute([$key, $val]);
        echo "Added: $key\n";
    } else {
        echo "Skipped (Exists): $key\n";
    }
}

echo "Database update complete.\n";
?>
