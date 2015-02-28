<?php

function content_type($uri) {
  $path = pathinfo($_SERVER['REQUEST_URI']);
  switch($path['extension']) {
  case 'css':
    return 'text/css';
  case 'png':
    return 'image/png';
  case 'jpeg';
  case 'jpg':
    return 'image/jpeg';
  default:
    return 'text/plain';
  }
}

// Contournement de bug dans le serveur embarqué de PHP
$_SERVER['SCRIPT_NAME'] = substr($_SERVER['SCRIPT_FILENAME'], strlen($_SERVER['DOCUMENT_ROOT']));

// Fichiers /static/
if (file_exists('./'.$_SERVER['REQUEST_URI'])) return false;

// Fichiers /data/
$root = getenv('STRASS_ROOT') or 'htdocs/';
if (file_exists($root.$_SERVER['REQUEST_URI'])) {
  header('Content-Type: '.content_type($_SERVER['REQUEST_URI']));
  readfile($root.$_SERVER['REQUEST_URI']);
  return;
}

if (strpos($_SERVER['REQUEST_URI'], 'favicon.ico')) return false;

// Le reste
include_once 'index.php';
