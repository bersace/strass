<?php

// Contournement de bug dans le serveur embarqué de PHP
$_SERVER['SCRIPT_NAME'] = substr($_SERVER['SCRIPT_FILENAME'], strlen($_SERVER['DOCUMENT_ROOT']));

// Ne servir que les requêtes PATH_INFO
if (file_exists('./'.$_SERVER['REQUEST_URI'])) return false;

include_once 'index.php';
