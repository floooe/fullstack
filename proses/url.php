<?php
// URL helper to generate paths relative to the app root directory
// App root (filesystem): <repo>/Project Full-Stack/Project Full-Stack

function __app_root_fs() {
    static $path = null;
    if ($path === null) {
        $repoRoot = dirname(__DIR__); // <repo>
        $path = realpath($repoRoot . DIRECTORY_SEPARATOR . 'Project Full-Stack' . DIRECTORY_SEPARATOR . 'Project Full-Stack');
    }
    return $path ?: '';
}

function __current_dir_fs() {
    $script = $_SERVER['SCRIPT_FILENAME'] ?? __FILE__;
    return realpath(dirname($script)) ?: '';
}

function __relpath($from, $to) {
    $from = str_replace('\\', '/', realpath($from));
    $to   = str_replace('\\', '/', realpath($to));
    if ($from === false || $to === false) return '';
    $fromParts = explode('/', rtrim($from, '/'));
    $toParts   = explode('/', rtrim($to, '/'));
    // Find common prefix length
    $len = min(count($fromParts), count($toParts));
    $i = 0;
    while ($i < $len && $fromParts[$i] === $toParts[$i]) { $i++; }
    $fromRemain = array_slice($fromParts, $i);
    $toRemain   = array_slice($toParts, $i);
    $up = str_repeat('../', max(0, count($fromRemain)));
    $down = implode('/', $toRemain);
    $rel = $up . $down;
    return $rel === '' ? './' : $rel . '/';
}

// Returns a relative URL from current script to app root + $path (can include ..)
function url_from_app($pathWithinApp) {
    $curDir = __current_dir_fs();
    $appRoot = __app_root_fs();
    if ($curDir === '' || $appRoot === '') return ltrim($pathWithinApp, '/');
    $prefix = __relpath($curDir, $appRoot); // e.g. ../../Project Full-Stack/Project Full-Stack/
    $url = $prefix . ltrim($pathWithinApp, '/');
    return str_replace('\\', '/', $url);
}

function redirect_rel($pathWithinApp) {
    $url = url_from_app($pathWithinApp);
    header('Location: ' . $url);
    exit;
}

?>

