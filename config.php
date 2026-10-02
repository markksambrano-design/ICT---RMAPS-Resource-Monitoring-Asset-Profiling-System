<?php
if (session_status() === PHP_SESSION_NONE) {
    // Set custom session save path BEFORE starting session
    $sessionPath = __DIR__ . DIRECTORY_SEPARATOR . 'tmp';
    if (!is_dir($sessionPath)) {
        mkdir($sessionPath, 0777, true);
    }
    session_save_path($sessionPath);
    session_start();
}

// Prevent browser caching for all protected pages
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include "config/db.php";
?>

