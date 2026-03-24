<?php

if (php_sapi_name() === 'cli-server') {
    $path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

    // Serve static files if they exist in public
    if ($path !== '/' && file_exists(__DIR__.'/public'.$path)) {
        return false;
    }

    // Route all other requests to public/index.php
    require_once __DIR__.'/public/index.php';
}
