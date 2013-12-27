<?php

// Contournement de bug dans le serveur embarqué de PHP
$_SERVER['SCRIPT_NAME'] = substr($_SERVER['SCRIPT_FILENAME'], strlen($_SERVER['DOCUMENT_ROOT']));

// Ne servir que index.php
if (basename($_SERVER['SCRIPT_FILENAME']) != 'index.php') return false;

include_once $_SERVER['SCRIPT_FILENAME'];
