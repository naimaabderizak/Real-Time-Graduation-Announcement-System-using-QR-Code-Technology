<?php
require_once 'db.php';

$new_settings = [
    'header_bg_color' => '#ffffff',
    'footer_bg_color' => '#002855',
    'header_logo_height' => '120px',
    'footer_logo_height' => '60px',
    'name_font_size' => '2.5rem',
    'faculty_font_size' => '1.8rem',
    'header_bg_image' => '',
    'footer_bg_image' => '',
    'stage_bg_image' => '',
    'tiled_logo' => '',
    'tiled_logo_size' => '80px',
    'tiled_logo_opacity' => '0.1',
    'tiled_logo_gap' => '100px'
];

foreach ($new_settings as $key => $value) {
    // Check if key exists
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

echo "Migration complete!";
?>
