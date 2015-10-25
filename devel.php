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
    default:
        return 'text/plain';
    }
}

function try_file($path) {
    if (!file_exists($path))
        return false;

    $info = pathinfo($path);
    $ext = @$info['extension'] ?: null;
    header('Content-Type: ' . content_type($ext));
    readfile($path);
    return true;
}

// Contournement de bug dans le serveur embarqué de PHP
$_SERVER['SCRIPT_NAME'] = substr($_SERVER['SCRIPT_FILENAME'], strlen($_SERVER['DOCUMENT_ROOT']));
$uri = urldecode($_SERVER["REQUEST_URI"]);

if (preg_match('/\.(?:css|gif|ico|jpeg|jpg|js|png|ttf)$/', $uri)) {
    // Fichiers /static/
    if (try_file('.' . $uri) === true) return true;

    // Fichiers /data/
    $root = getenv('STRASS_ROOT') or 'htdocs';
    if (try_file($root . $uri) === true) return true;

    header("HTTP/1.0 404 Not Found");
    error_log("Pas trouvé : " . $uri);
    return true;
}

/* Passer la main à index.php. */
return false;
