<?php
session_start();
require_once __DIR__ . '/../../proses/url.php';

// Clear session data
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'], $params['secure'], $params['httponly']
    );
}

session_destroy();

// Redirect back to login (app root)
redirect_rel('index.php');
exit;
?>
