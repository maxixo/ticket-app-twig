<?php
// router.php — used only for php -S (built-in server)

if (php_sapi_name() === 'cli-server') {
    $url = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . '/public' . $url['path'];

    if (is_file($file)) {
        // Let PHP serve static files (CSS, JS, images)
        return false;
    }
}

require __DIR__ . '/public/index.php';
