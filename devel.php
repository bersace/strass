<?php
/* Mini serveur de static pour le développement. */

set_time_limit(5);

function content_type($extension) {
    switch($extension) {
    case 'ttf':
        return 'application/x-font-ttf';
    case 'css':
        return 'text/css';
    case 'png':
        return 'image/png';
    case 'jpeg';
    case 'jpg':
        return 'image/jpeg';
    case 'js':
        return 'application/javascript';
    case 'pdf':
        return 'application/pdf';
    default:
        return 'text/plain';
    }
}

function try_file($path) {
    if (!file_exists($path)) {
        header("HTTP/1.0 404 Not Found");
        error_log("Pas trouvé : " . $path);
        return;
    }

    $info = pathinfo($path);
    $ext = @$info['extension'] ?: null;
    header('Content-Type: ' . content_type($ext));
    readfile($path);
}

$data = '/data/';
$static = '/static/';
$path = urldecode($_SERVER["REQUEST_URI"]);

/* Détecter les fichier static /data/ ou /static/ */
if (strncmp($_SERVER['REQUEST_URI'], $data, strlen($data)) === 0) {
    $root = getenv('STRASS_ROOT') or $_SERVER['DOCUMENT_ROOT'] . '/htdocs';
    try_file($root . $path);
}
else if (strncmp($_SERVER['REQUEST_URI'], $static, strlen($static)) === 0) {
    try_file( $_SERVER['DOCUMENT_ROOT'] . $path);
}
else {
    include 'index.php';
}

/* Ne jamais passer la main au serveur PHP, c'est trop
 * buggué. https://bugs.php.net/bug.php?id=61286 , etc. */
return true;
