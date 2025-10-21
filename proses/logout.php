<?php
session_start();
require_once __DIR__ . '/url.php';
session_destroy();
redirect_rel('index.php');
exit;
?>
