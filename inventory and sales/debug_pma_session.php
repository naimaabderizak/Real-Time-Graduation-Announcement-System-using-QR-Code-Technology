<?php
session_start();
header("Content-Type: text/plain");

$path = session_save_path();
if (empty($path)) {
    $path = sys_get_temp_dir();
}

echo "Session Save Path: " . $path . "\n";

if (is_writable($path)) {
    echo "Path is writable.\n";
} else {
    echo "Path is NOT writable.\n";
}

echo "Current Session ID: " . session_id() . "\n";
echo "Cleaning up old sessions... ";

$count = 0;
// Try to clean up old session files (older than 1 hour)
if (is_dir($path)) {
    $files = glob($path . '/sess_*');
    foreach ($files as $file) {
        if (filemtime($file) < time() - 3600) {
            @unlink($file);
            $count++;
        }
    }
}
echo "Removed $count old session files.\n";
?>
