<?php
// PHP Development Server Router

// Check if file exists and serve it directly
if (file_exists(__DIR__ . $_SERVER['REQUEST_URI']) && !is_dir(__DIR__ . $_SERVER['REQUEST_URI'])) {
    return false;
}

// For all other requests, route through index.php
require_once __DIR__ . '/index.php';
?>